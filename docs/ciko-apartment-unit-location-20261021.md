# CIKO — One APT master with per-unit locations

This final audit supersedes the earlier split-building and grouped-building presentation audits. Current requirement: one APT property, five existing apartment units, each with its own city and address.

## Verdict
PASS for the local migration, model behavior and public HTTP scope; no confirmed Critical/Major defect. 202 independent checks pass:21 synthetic merge cases,140 real post-merge baseline checks,17 synthetic master/public-model location cases,24 HTTP/source checks.

## Data preservation
CIKO independently captured the pre-merge44-table baseline, all84 rooms and all8 properties. Post-merge checks confirm all42 other tables have identical row counts and hashes. Every original room ID and existing non-property room field, including timestamps, is preserved. Only roomIDs128–132 move back to shared APT propertyid4, with additive unit_city/unit_address columns. Only the four child property IDs owned by the preceding migration are removed; other properties remain unchanged and APT is restored active. Five apartment units point to it.

Local backups and execution are ZEYN-owned; CIKO performed read-only real-data verification. Independent test schemas were disposable and cleaned after success.

## Behavioral checks
- Merge copies child city/address into each unit, preserves related tenant/contract/bill/payment/history rows, and is idempotent.
- Conflicting locations, arbitrary direct property references, extra/missing/wrong-type units, changed child identity and invalid ownership manifest reject with full rollback.
- Master model persists/searches/clears per-unit city/address. Missing new fields preserve prior values.
- Two The Nest units with the same type but different synthetic locations produce separate cards, locations and unique URLs; no MAX aggregation blends the cities.
- Blank unit locations do not inherit the shared APT location. House cards continue to use property-level city/address.
- Public landing has one APT filter and four priced unit cards. Taman Anggrek's existing null price remains unchanged.
- Four unit-specific detail routes and six generic/temporary historical aliases resolve. Summary explains that locations follow each unit. Master template exposes city120/address2000 limits.

Evidence: .e2e/apartment-unit-location-20261021/ciko-before.json, ciko-fixture-results.json, ciko-readback-results.json, ciko-model-results.json, ciko-http-results.json.

## Boundaries
CIKO did not operate an authenticated browser, submit real business forms, edit production or deploy. Browser visual/edit-field evidence belongs to ZEYN separately. All explicit location-save experiments used an owned synthetic schema. No city/address values were guessed for real units.
