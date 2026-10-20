# Persistent login alert — backend verification

20 October 2026. Local implementation only; no deployment and no real browser session/cookie was read.

The alert was reproduced with an owned synthetic fresh login: unauthenticated dashboard redirected correctly, login succeeded, then the authenticated properties page returned HTTP 200 with the old “Sesi belum aktif” error. This was stale flash data, not a failed authentication check.

Both bundled CodeIgniter session implementations aged flash using `$value < $current_time`, relying on the string `old` comparing numerically as zero. The actual PHP 8.2.12 runtime evaluates `'old' < time()` as false. Consequently old flash messages never expired.

The only application-source changes are the `_ci_init_vars()` predicates in `master/system/libraries/Session/Session.php` and `landing/system/libraries/Session/Session.php`: expire the explicit `old` marker or an expired integer tempdata timestamp. New flash still becomes old first. Authentication guards, controllers, roles and unrelated session data remain unchanged. Backup copies exist under `.e2e/session-alert-20261020/`.

Verification actually run with `D:/XAMPP/php/php.exe`:

- PHP syntax checks: both session files PASS.
- `tests/verify-session-flash-php8.php`: 6/6 PASS using the actual session aging method. Covers new → old → removed, expired and future tempdata, preservation of ordinary/admin session values, and identical corrected behavior in both applications.
- `tests/verify-master-session-alert.php`: 15/15 PASS over the guarded synthetic loopback runtime. Covers unauthenticated redirect, login reason, successful login, two navigation rounds across Dashboard/Manage/Operations/Lifecycle including case detail, explicit logout and renewed protection after logout. The same harness failed before the patch on authenticated properties with stale error present.

The HTTP harness uses its own temporary cookie jar and synthetic credentials from the guarded runtime pointer. It never reads the user's browser cookie. Independent CIKO browser retesting is separate evidence; these checks do not claim production deployment or direct verification of the user's existing real session.
