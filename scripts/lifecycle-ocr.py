"""Local-only OCR suggestions for signed facility scans; never an approval engine.

Usage: bundled-python scripts/lifecycle-ocr.py --input private-file.pdf
Writes JSON to stdout, without network requests or document data in error logs.
"""
from __future__ import annotations

import argparse
import json
import os
from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / ".tools" / "ocr-python"))
os.environ.setdefault("OMP_NUM_THREADS", "2")


def extract(path: Path) -> dict:
    import numpy as np
    from PIL import Image, ImageOps
    from rapidocr_onnxruntime import RapidOCR

    if not path.is_file() or path.stat().st_size > 10 * 1024 * 1024:
        raise ValueError("invalid_file")
    if path.suffix.lower() not in {".pdf", ".png", ".jpg", ".jpeg"}:
        raise ValueError("invalid_type")
    images = []
    if path.suffix.lower() == ".pdf":
        import pypdfium2 as pdfium
        document = pdfium.PdfDocument(str(path))
        if not 1 <= len(document) <= 6:
            raise ValueError("page_limit")
        try:
            for page in document:
                bitmap = page.render(scale=2)
                images.append(bitmap.to_pil().convert("RGB").copy())
                bitmap.close()
                page.close()
        finally:
            document.close()
    else:
        with Image.open(path) as source:
            if source.width * source.height > 40000000:
                raise ValueError("pixel_limit")
            images.append(ImageOps.exif_transpose(source).convert("RGB"))

    engine = RapidOCR(intra_op_num_threads=2, inter_op_num_threads=2)
    pages = []
    pixel_pages = []
    for number, image in enumerate(images, 1):
        image.thumbnail((2400, 3200))
        pixel_pages.append(np.array(image))
        result, _ = engine(np.array(image))
        lines = []
        for box, text, confidence in result or []:
            lines.append({"text": str(text), "confidence": round(float(confidence), 4),
                          "box": [[round(float(x), 1), round(float(y), 1)] for x, y in box]})
        pages.append({"page": number, "width": image.width, "height": image.height, "lines": lines})
    text = "\n".join(line["text"] for page in pages for line in page["lines"])
    fields = {}
    room = re.search(r"(?:nomor|no\.?|kode)\s*kamar\s*[:\-]?\s*([A-Z]?-?\d{2,4})\b", text, re.I)
    if room:
        fields["room_code"] = room.group(1)
    date = re.search(r"\b(20\d{2})[-/](0?[1-9]|1[0-2])[-/]([0-2]?\d|3[01])\b", text)
    if date:
        fields["inspection_date"] = f"{date[1]}-{int(date[2]):02d}-{int(date[3]):02d}"
        fields["period_key"] = fields["inspection_date"][:7]

    known_items = {"ac", "tv", "kulkas", "lemari", "rak sepatu", "dipan", "kasur", "bantal",
                   "sprei set", "bedcover", "kunci kamar", "pintu kamar", "wastafel", "shower",
                   "closet", "tempat sampah", "cermin", "keset", "lampu"}
    items = []
    seen = set()
    for page, pixels in zip(pages, pixel_pages):
        good_headers = [line for line in page['lines'] if line['text'].strip().lower() == 'baik']
        good_headers.sort(key=lambda line: line['box'][0][0])
        columns = []
        if len(good_headers) >= 2:
            columns = [sum(point[0] for point in line['box']) / 4 for line in good_headers[:2]]
        for line in page["lines"]:
            normalized = re.sub(r"^\s*(?:\d+[.)]?\s+|[a-z][.)]\s*)", "", line["text"].lower()).strip()
            # Suggestions identify printed labels, not the handwritten truth of a checkbox.
            compact = re.sub(r'\s+', '', normalized)
            item = next((name for name in sorted(known_items, key=len, reverse=True)
                         if compact == name.replace(' ', '') or normalized.startswith(name + ' ')), None)
            if item and item not in seen:
                seen.add(item)
                condition = 'uninspected'
                mark_scores = []
                if len(columns) == 2:
                    y = sum(point[1] for point in line['box']) / 4
                    half_height = max(6, int((line['box'][2][1] - line['box'][0][1]) * .32))
                    half_width = max(12, int((columns[1] - columns[0]) * .28))
                    for x in columns:
                        patch = pixels[max(0,int(y)-half_height):int(y)+half_height,
                                       max(0,int(x)-half_width):int(x)+half_width].astype('int16')
                        if patch.size == 0:
                            mark_scores.append(0)
                            continue
                        # Ignore green watermark; only suggest dark or blue ink marks in cell interiors.
                        dark = (patch.max(axis=2) < 155) & ((patch.max(axis=2)-patch.min(axis=2)) < 60)
                        blue = (patch[:,:,2] > patch[:,:,0]+35) & (patch[:,:,0] < 160)
                        mark_scores.append(int((dark | blue).sum()))
                    threshold = max(14, int(half_height * half_width * .025))
                    if mark_scores[0] > threshold and mark_scores[1] <= threshold:
                        condition = 'good'
                    elif mark_scores[1] > threshold and mark_scores[0] <= threshold:
                        condition = 'needs_repair'
                items.append({"item_name": item.upper() if item in {"ac", "tv"} else item.title(),
                              "standard_qty": None, "actual_qty": None,
                              "condition_status": condition, "notes": "",
                              "mark_scores": mark_scores,
                              "confidence": line["confidence"], "requires_review": True})
    return {"ok": True, "engine": "RapidOCR ONNX (local)", "requires_review": True,
            "pages": pages, "text": text, "suggested_fields": fields, "items": items,
            "warnings": ["Centang, jumlah, tulisan tangan dan tanda tangan harus diverifikasi admin; OCR tidak menentukan tanggung jawab kerusakan."]}


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--input", required=True)
    args = parser.parse_args()
    try:
        payload = extract(Path(args.input).resolve())
    except Exception as error:
        payload = {"ok": False, "engine": "RapidOCR ONNX (local)", "requires_review": True,
                   "error": type(error).__name__,
                   "message": "Pembacaan lokal belum berhasil. Periksa scan dan gunakan verifikasi manual."}
    sys.stdout.reconfigure(encoding="utf-8")
    print(json.dumps(payload, ensure_ascii=False))
    sys.exit(0 if payload["ok"] else 1)
