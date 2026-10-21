# One APT property, locations per unit

Final local design: Master Properti contains one active APT / Apartemen property (id 4). Master Kamar stores each unit's `unit_city` and `unit_address`. No cities or addresses were guessed; administrators can enter them through Edit Kamar.

The five apartment room IDs 128-132 remain unchanged. The four properties created by the superseded building split (6-9) were removed only after checking their ownership manifest, expected rooms, and absence of other references. Original APT was reactivated. Rental prices, deposits, room types, occupant references and all other original room fields/timestamps were preserved.

## Backup and data preservation

Full SQL and all-table JSON backups were taken before additive schema changes and the guarded merge transaction:

`.e2e/apartment-unit-merge-20261021/20261021-045235-504a01/`

`result.json` records the committed gates. All 42 tables outside rooms/properties retain exact row counts and hashes, including tenants, contracts, bills, payments and history. The 65 existing apartment bill rows were preserved.

Independent CIKO readback passed 140 checks against the pre-merge baseline. Its isolated model tests passed 17 checks covering different locations for two units of the same apartment type, persistence, clearing, omitted-field preservation, search, house property locations and legacy URLs. Merge helper tests passed 21 checks, including rollback and idempotence. Synthetic checks did not modify real business data.

Final CIKO total: 202 independent checks passed, including 24 HTTP/template checks. No critical or major issues remained in the audited scope. See `ciko-apartment-unit-location-20261021.md`. ZEYN also verified the actual Master Properti count and the unit-location form in the authenticated browser; screenshot: `.e2e/apartment-unit-merge-20261021/master-unit-location-after.png`.

## Public and client behavior

Landing retains one APT filter. Apartment cards and details use each unit's city/address and unique unit URLs; house locations continue using Master Properti. Old generic apartment URLs and temporary building-split URLs resolve compatibly. Units without a price remain unpublished. Older clients that omit location fields preserve existing values.

The former `scripts/split-gulhouse-apartments.php` command is disabled. The guarded local merge entry point is `scripts/merge-gulhouse-apartment-units.php`. No production deployment was performed for this change.
