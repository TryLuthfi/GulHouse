"""Generate explicitly synthetic facility documents for real browser and OCR tests."""
from pathlib import Path
from reportlab.pdfgen import canvas

target = Path(__file__).resolve().parents[1] / '.e2e/lifecycle/zeyn'
target.mkdir(parents=True, exist_ok=True)
for stage in ['check_in', 'routine', 'check_out']:
    pdf = canvas.Canvas(str(target / (stage + '.pdf')))
    pdf.setFont('Helvetica-Bold', 18)
    pdf.drawString(50, 790, 'SYNTHETIC FACILITY INSPECTION - ' + stage)
    pdf.setFont('Helvetica', 12)
    pdf.drawString(50, 755, 'Test only - no real resident identity or signature.')
    pdf.drawString(50, 720, 'AC: Good   TV: Good   Kulkas: Good')
    for y, role in [(680, 'Resident'), (650, 'Manager'), (620, 'Supervisor')]:
        pdf.drawString(50, y, role + ': SYNTHETIC acknowledgement')
    pdf.save()

pdf = canvas.Canvas(str(target / 'ocr-checkboxes.pdf'))
pdf.setFont('Helvetica-Bold', 16)
pdf.drawString(50, 790, 'SYNTHETIC CHECKLIST FASILITAS')
pdf.setFont('Helvetica', 12)
pdf.drawString(50, 750, 'Nomor Kamar: A-205')
pdf.drawString(50, 730, 'Tanggal: 2026-10-19')
pdf.drawCentredString(350, 700, 'Baik')
pdf.drawCentredString(450, 720, 'Tidak')
pdf.drawCentredString(450, 700, 'Baik')
for y, name in [(650, 'AC'), (620, 'TV'), (590, 'Cermin')]:
    pdf.drawString(50, y, name)
    pdf.line(40, y-10, 510, y-10)
    pdf.line(40, y+20, 510, y+20)
pdf.setStrokeColorRGB(0, 0, .7)
pdf.setLineWidth(2)
for x, y in [(350, 650), (450, 620)]:
    pdf.line(x-8, y+5, x-2, y)
    pdf.line(x-2, y, x+10, y+14)
pdf.save()
print('Synthetic facility and OCR fixtures generated.')
