# GulHouse Living — native visual redesign

Direction: warm ivory, white surfaces, quiet olive accents, Manrope typography,
and actual GulHouse room photographs. The three references supplied by el guide
the composition: photo collections, clear prices, an interior gallery, and a
compact navigation dock. They are inspiration only; their property photos,
reviews, agents, favorites, maps, and chat features are not copied into the app.

## Internet research and implementation

These pages were actually searched/opened on 19 October 2026:

- [Real Estate App Design — Atiqur Rahaman / Design Monks](https://dribbble.com/shots/27153087-Real-Estate-App-Design): neutral property browsing and clear hierarchy informed the warm surfaces, larger photos, and quiet card treatment. No artwork was downloaded or reused.
- [Airbnb: How to take great photos for your listing](https://www.airbnb.com/resources/hosting-homes/a/how-to-take-great-photos-for-your-listing-687): landscape photography and a useful photo tour informed actual room covers, large detail photographs, and selectable gallery thumbnails. Listing photographs come exclusively from the existing GulHouse API; missing photographs remain visibly unavailable.
- [Android: Grids and units](https://developer.android.com/design/ui/mobile/guides/layout-and-content/grids-and-units): adaptive modular grids and content hierarchy informed the catalog, grouped admin hub, and financial dashboard. Grids account for safe-area insets on native devices.
- [Android: Make apps more accessible](https://developer.android.com/guide/topics/ui/accessibility/views/apps-views): 48 dp touch targets, explicit button names, and decorative icon exclusion inform shared controls. Input contrast, focus, keyboard handling, modal dismissal, and font scaling remain part of QA.
- [Material: Cards](https://m3.material.io/components/cards/guidelines) and [Bottom sheets](https://m3.material.io/components/bottom-sheets/guidelines): component references. These pages require JavaScript in the browsing tool, so detailed prose from them was not used as verified evidence.
- [Manrope — Google Fonts](https://fonts.google.com/specimen/Manrope), [official OFL](https://raw.githubusercontent.com/google/fonts/main/ofl/manrope/OFL.txt): genuine locally bundled regular, medium, and bold font files, loaded with Expo Font. Font files come from the [Expo Google Fonts repository](https://github.com/expo/google-fonts/tree/main/font-packages/manrope); the font license is included at `assets/fonts/OFL.txt`.

## Screen families

- Catalog: editorial introduction, real photo feature, search, property filters,
  horizontal room collection, and adaptive listing grid.
- Detail: large photograph, selectable thumbnails, room and availability details,
  clear facilities/deposit information, and a persistent survey action.
- Resident: warm stay summary, evidence-based journey indicator, initial finance
  digest, then complete payment, identity, facility, checkout/refund sections.
- Administration: role-filtered module groups, quick links to existing modules,
  adaptive tiles, prioritized financial metrics, and accessible secondary data.
- All other modules reuse the same typography, inputs, upload panels, cards,
  sheets, pickers, notices, file views, empty states, and navigation.

Business APIs, permission checks, mandatory KTP/consent, payment verification,
scan-first drafts, manual facility verification, physical signatures, H-15/H+2,
private evidence, idempotency, and the Android native Back fix are preserved.

TypeScript/source checks and visual/behavioral QA are reported separately by
ZEYN and CIKO. Browser preview alone does not establish native device behavior.
