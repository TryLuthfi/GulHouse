# CIKO independent apartment split audit

> Historical audit only. This split was subsequently reversed locally. See `apartment-unit-location-20261021.md` for the final one-APT design and new verification.

Scope: local GulHouse data separation of the existing shared APT property into The Nest, Mejestic, PIK 2 and Taman Anggrek. No production changes by CIKO.

Verdict: PASS for migration helper, local data preservation, and public HTTP compatibility. 89 independent checks passed: 16 synthetic helper cases, 58 real readback checks, 15 public HTTP checks.

Before migration, CIKO independently saved hashes/counts for all44 local tables and the five apartment room records. The applied migration preserves room IDs128–132 and every inspected non-property room field, including prices, status, room type and timestamps. All42 tables outside gh_rooms/gh_properties retain exact row counts and hashes, including tenants, stays, contracts, payments, bills, events and history. 65 bills attached to apartment rooms remain untouched.

Four new active apartment properties exist with independent city/address fields left blank. Shared APT propertyid4 remains in the database, inactive and empty. No guessed cities or addresses were inserted.

Synthetic fixture exercises seeded related stay/contract/bill/payment/history records, idempotent rerun, and rollback rejection for extra/missing/wrong-type units, ambiguous property expenses, and inactive target collision. Initial harness expense-column typo was corrected; this was not an implementation defect. Synthetic schemas are owned test fixtures, not real business records.

Actual public HTTP confirms readable names for the three priced apartment buildings, canonical detail links, and compatibility of old apt-the-nest/apt-mejestic/apt-pik-2 links. Taman Anggrek retains its null rental price and is not unexpectedly advertised. Shared APT is absent from public room cards.

Evidence: .e2e/apartment-split-20261021/ciko-before.json, ciko-fixture-results.json, ciko-readback-results.json, ciko-http-results.json.

Limits: CIKO did not operate the authenticated browser, edit location fields, submit business forms, or deploy online. Root ZEYN separately verified the master page and editable city/address fields. Source master forms and existing persistence path support those fields.

## APT category clarification

After the public presentation update, 19 focused HTTP/source checks pass: one shared APT filter, no per-building apartment filter buttons, three priced cards grouped under APT, distinct actual building keys in data-property-code, friendly building names and individual city elements. House filters remain separate. Summary type chips display APT. The older HTTP regression harness now reads actual data-property-code rather than the grouped data-property attribute; its15 checks still pass. The58 real baseline preservation checks were rerun successfully without database writes. This presentation update does not undo the four independent building master records or change room identity.

Total scoped independent checks across split and category presentation:108 (16 synthetic,58 real readback,15 route compatibility,19 category presentation). Browser interaction/visual evidence remains ZEYN's separate verification.
