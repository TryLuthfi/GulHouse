"""Actual local OCR regression, including checkbox suggestions and mandatory review."""
from pathlib import Path
import importlib.util
import sys

root = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('lifecycle_ocr', root / 'scripts/lifecycle-ocr.py')
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
result = module.extract(root / '.e2e/lifecycle/zeyn/ocr-checkboxes.pdf')
items = {row['item_name']: row for row in result['items']}
checks = {
    'real local OCR engine completed': result['ok'] and 'RapidOCR' in result['engine'],
    'good checkbox suggested': items['AC']['condition_status'] == 'good',
    'bad checkbox suggested for review': items['TV']['condition_status'] == 'needs_repair',
    'blank cell stays uninspected': items['Cermin']['condition_status'] == 'uninspected',
    'all suggestions require human review': result['requires_review'] and all(i['requires_review'] for i in result['items']),
    'actual quantity not invented': all(i['actual_qty'] is None for i in result['items']),
}
if len(sys.argv) > 1:
    blank = module.extract(Path(sys.argv[1]))
    checks['original form19 labels recognized'] = len(blank['items']) == 19
    checks['original blank form no invented condition'] = all(i['condition_status'] == 'uninspected' for i in blank['items'])
for name, passed in checks.items():
    print(('PASS ' if passed else 'FAIL ') + name)
assert all(checks.values())
print(f'{len(checks)}/{len(checks)} actual local OCR checks PASS')
