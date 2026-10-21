# Apartment properties split (local)

> SUPERSEDED: The building split below was reversed locally. The final design has one APT property and city/address per unit. See `apartment-unit-location-20261021.md`. The old split CLI is disabled.

Five existing units were reassigned to four building properties. Only gh_rooms.property_id changed. Room IDs, codes, types, prices, deposit, status, visibility and timestamps remained identical.

| Property | Units | Room IDs |
| --- | --- | --- |
| APT THE NEST | E-1606, E-1607 | 128, 129 |
| APT MEJESTIC | L-1003 | 130 |
| APT PIK 2 | TRBP-68-59 | 131 |
| APT TAMAN ANGGREK | STD-50P | 132 |

The former APT property id4 remains archived (is_active0), not deleted. Each new property has independently editable city/address in Master Properti; both fields are initially blank because no verified location was present in the old data. Room types were retained.

## Migration
`scripts/split-gulhouse-apartments.php` defaults to plan; --apply backs up every table as JSON plus SQL before a guarded transaction. Only local gulhouse_db or owned test schemas on localhost/127.0.0.1 are accepted. Unknown units, incorrect room types, conflicting targets or direct APT expenses block the operation. The caller checks all other table hashes and exact room field parity before commit. Reruns are idempotent.

Backup and receipt pointer: .e2e/apartment-split-20261021/active.txt. Actual backup: .e2e/apartment-split-20261021/20261021-044034-2ee21e/ (database-before.sql, database-before.json, result.json).

42 unrelated tables, including tenants, stays, contracts, bills, payments and event/history data, passed unchanged-row/hash gates. The65 existing apartment bill rows retain their original room references. There were no apartment rows in the legacy gh_room_database or direct property expenses requiring additional mappings.

## Public continuity
Landing shows readable apartment property names with cities from each new master. Archived APT is excluded from public property stats. Existing /rooms/apt-mejestic, /rooms/apt-pik-2 and /rooms/apt-the-nest still resolve to their matching new building. Taman Anggrek remains excluded from the public room catalog because its price was already null; migration does not invent a price or publish it.

All four previous APT photo folders were empty before migration. No photo fallback or cross-building gallery was introduced; existing photo files/settings were not changed.

## Single APT category
All four building properties retain property_type=apartment. They are locations, not four new housing categories. The landing Jenis Hunian filter exposes one APT option and groups all priced apartment cards under it; data-property-code retains the actual building for independent city/address. Summary names use the building name and the apartment type chip says APT. Existing internal room-type IDs are preserved to avoid altering historical relationships; their legacy building labels are not exposed as additional landing filter categories.

## Evidence
CIKO independent pre-migration baseline: .e2e/apartment-split-20261021/ciko-before.json. Synthetic migration checks cover linked occupant/contract/payment/history rows, rollback and rerun. ZEYN actual local browser confirmed four active apartment properties and editable blank city/address fields. No production deploy/database change was performed in this task.
