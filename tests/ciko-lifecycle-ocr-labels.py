"""Independent pure regression: OCR prefix stripping must retain item names."""
import ast
from pathlib import Path
import re

source = Path(__file__).resolve().parents[1] / "scripts" / "lifecycle-ocr.py"
tree = ast.parse(source.read_text(encoding="utf-8"))
patterns = []
for node in ast.walk(tree):
    if isinstance(node, ast.Assign) and any(isinstance(t, ast.Name) and t.id == "normalized" for t in node.targets):
        call = node.value.func.value  # re.sub(...).strip()
        patterns.append(call.args[0].value)
assert len(patterns) == 1, "normalization pattern must be reviewed if extraction changes"
pattern = patterns[0]
cases = {"AC":"ac", "Bantal":"bantal", "Bedcover":"bedcover", "TV":"tv", "Lemari":"lemari", "1. AC":"ac", "2) Bantal":"bantal", "a. Bedcover":"bedcover", "b) AC":"ac", "10 AC":"ac"}
for label, expected in cases.items():
    actual = re.sub(pattern, "", label.lower()).strip()
    assert actual == expected, f"{label!r}: {actual!r} != {expected!r}"
print(f"PASS: {len(cases)} OCR item-label prefix regressions (no OCR/model invoked)")
