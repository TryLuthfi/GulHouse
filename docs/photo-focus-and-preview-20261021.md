# Room card focus and full Preview photos

Local implementation only; not committed or deployed in this task.

Room cards now use one image at 4:3, without background-repeat, zoom or parallax. Master Foto > choose property/type > Atur posisi fokus provides horizontal and vertical 0–100 controls per photo, with live crop preview. Focus affects that image when selected as the room card cover; it does not change image bytes or the room gallery. Defaults: 50% horizontal, 70% vertical.

Preview foto uses four equal 4:3 canvases with object-fit contain, so each complete image is retained without cropping: two columns on desktop/tablet and one on mobile. Captions sit below photos. No fixed row/image heights, first-image special crop, zoom or parallax. Choices and order still follow Foto Landing.

Changed files: Gallery_focus.php library, Manage.php controller, routes.php, Landing_model.php, landing_home.php, landing.css, manage/photos.php, photo-focus.css and photo-focus.js. Tests and independent report: docs/ciko-photo-focus-20261021.md.

Runtime settings: uploads/{property}/{type}/.gallery-focus.json (ignored by Git). Include this metadata explicitly in any future photo synchronization/deployment. Existing gallery cover and landing selection metadata are preserved.

## Verification
- CIKO: 105 independent helper, HTTP, policy and Preview source checks PASS, no confirmed major/critical issue.
- ZEYN browser: desktop GH2 images359.89x269.91 and mobile286.09x214.56, ratio4:3, single image, no transform/parallax attribute.
- ZEYN authenticated browser using existing synthetic schema and test account: focus55/75 updates live preview, saves and survives reload; final VIP restored to50/70. No business form writes. Master mobile390 has no document overflow; sliders work with keyboard.
- ZEYN browser Preview: all4 displayed image ratios match natural pixel ratios on desktop and390px; contain, no transform. At390px imagewidth333 and natural proportional heights222/187.34/222.5/222. Temporary viewport reset.
- Screenshots in .e2e/photo-focus-20261021/.

No database schema/records changed by this feature. No online updates.

Final equal-size revision: all four desktop canvases measured204.15625x153.109375px, aspect4:3; image contents are contained, not cropped. Evidence preview-equal-after.png.
