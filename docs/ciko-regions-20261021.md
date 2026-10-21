# CIKO audit: administrative regions through district level

Verdict: **PASS within the audited local data/backend scope**, with no critical or major issue found. CIKO ran 156 independent checks: 64 real database readback checks, 47 isolated model checks, and 45 actual SQL restore checks. Six changed PHP files and the region JavaScript passed syntax checks. Browser visual/interaction evidence is separately attributed to ZEYN below.

## Requirement and final structure

GulHouse uses a local copy of province, regency/city, and district references, without village/dusun data. GH locations belong to Master Properti; apartment locations belong to individual rooms while the master retains one APT property. Full addresses stay manually entered. Existing city/address values are not inferred or replaced during migration.

## Independent preservation baseline

Before migration, CIKO captured all 44 original tables, including complete row values, counts and hashes, at `.e2e/regions-20261021/ciko-before.json`. After migration, all original fields in every original table remained identical. This includes all 84 rooms, four properties, IDs, timestamps, tenants, stays, contracts, bills, payments and history. New nullable region IDs were not populated by guessing old address text. One active APT with five units remains.

The copied references match the selected columns of the source tables in `databasetkm_com` exactly: 34 provinces, 514 regencies/cities and 7,215 districts. Both reference-parent joins have zero missing parents. No village/dusun table was imported.

## Isolated functional checks

An owned synthetic schema exercised actual `Master_model` and `Landing_model` code. Checks covered saving a valid GH hierarchy with a literal manual address; canonical city derived from the selected regency; omitted fields and blank selector submissions preserving selected IDs; omitted property/unit addresses remaining intact; and old clients being unable to override a canonical city while retaining selected region IDs.

Mismatched province/city/district parents, missing IDs, unknown IDs, array/object values, oversized IDs, and SQL-like ID fragments were rejected. Each rejection preserved the complete prior property or room row. Two units of the same apartment type retained separate cities and addresses in public cards. Attempts to set common APT location or individual GH room location did not override the intended location scope. The owned fixture was removed after success.

## Backup restore proof

CIKO restored the actual full SQL backup from `.e2e/regions-20261021/20261021-050941-01f1ef/database-before.sql` into an explicitly named, owned test schema. The dump was guarded against changing the selected database. All 44 restored tables matched both `database-before.json` in that backup directory and CIKO's independent pre-migration baseline, including all complete data values and row counts. The restore fixture was removed after success. CIKO performed no writes to real business schemas.

## UI/source review and attribution

Source review confirms shared labelled province/city/district fields, escaped reference labels and safely encoded JSON, descendant resets on parent changes, inactive child selectors disabled, and separate GH property/APT unit location scopes. Full manual address fields remain separate. Empty legacy forms retain existing city guidance.

ZEYN separately reported actual authenticated browser checks: DKI Jakarta to Jakarta Barat to Cengkareng for an APT unit; switching province to Kalimantan Timur cleared descendant selections; GH3 retained its full existing address and legacy Balikpapan label; changing property type to Apartment hid and disabled the property location section, and changing back restored it. ZEYN closed those forms without saving real changes. Browser evidence: `.e2e/regions-20261021/property-region-browser.png`. These observations were performed by ZEYN, not CIKO.

CIKO did not independently execute an authenticated browser submit or a full mobile/keyboard/accessibility sweep. No production deployment, online transaction, or current national-boundary completeness claim is included. The source dataset has 34 provinces, as disclosed to el.

## Reproducible checks and evidence

- `tests/ciko-regions-baseline.php`: one-time pre-change capture, refuses overwrite.
- `tests/ciko-regions-readback.php`: 64 checks, no database writes.
- `tests/ciko-regions-model.php`: 47 checks against an owned synthetic schema.
- `tests/ciko-regions-restore.php`: 45 checks from an actual backup restore into an owned test schema.
- `.e2e/regions-20261021/ciko-readback-results.json`
- `.e2e/regions-20261021/ciko-model-results.json`
- `.e2e/regions-20261021/ciko-restore-results.json`

No implementation file was modified by CIKO. Test harnesses, evidence, and this audit report are the CIKO-owned outputs.
