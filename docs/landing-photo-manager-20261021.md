# Foto Landing

Local implementation: Master Foto (`/master/photos`) now has four ordered Preview selectors and one Daily living selector. Existing gallery images only; changing these slots does not change room covers or the hero slider.

Initial curated GH2 selection: VIP front bed, Deluxe bed, shared kitchen, shared lounge. Daily living uses the shared lounge. Existing source photos remain untouched.

## Files
- `master/application/libraries/Landing_photos.php`: validated gallery catalog and atomic selection metadata.
- `master/application/controllers/Manage.php`, `master/application/config/routes.php`: authenticated settings form and POST endpoint.
- `master/application/views/manage/photos.php`, `master/assets/master/css/landing-photo-manager.css`, `master/assets/master/js/landing-photo-manager.js`: BUBU implementation, labelled ordered selectors, thumbnail updates, duplicate validation and responsive layout.
- `landing/application/models/Landing_model.php`, `landing/application/controllers/Home.php`, `landing/application/views/landing_home.php`, `landing/assets/landing/css/landing.css`: ordered Preview and selected Daily living; removed automatic Preview selection and stock-photo fallback. Daily living has a stable gradient without parallax seams.
- `scripts/seed-landing-photos-local.php`: explicit local initial selection, backs up existing metadata before overwrite.

## Storage and missing files
Settings are stored in `uploads/.landing-photos.json`, not the database. Missing selected files are skipped; they are not replaced by another gallery or stock photos. Daily living with no valid image uses the solid background. Preview requires four distinct existing images on save; Daily living may reuse a Preview photo.

## Verification
CIKO independent helper, HTTP and policy checks: 43 PASS; details in `docs/ciko-landing-photos-20261021.md`.
ZEYN actual browser: save succeeded, five selected values persisted, public Preview order and Daily living image verified visually; thumbnail change verified without saving a temporary duplicate. Admin layout checked at 390 px (one column, no document overflow), 768 px (two columns, no document overflow) and default width. Temporary viewport reset.
Screenshots: `.e2e/landing-photo-manager-20261021/`.

Local only. No production deploy or database writes. Deployment must include image assets and `uploads/.landing-photos.json` explicitly because runtime uploads are ignored by Git.
