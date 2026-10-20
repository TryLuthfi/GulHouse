# CIKO — acceptance aplikasi React Native GulHouse

Tanggal:19 Oktober2026. Scope dikoreksi menjadi **aplikasi React Native admin + penghuni**, bukan redesign web mobile dan bukan WebView. CIKO read-only terhadap implementasi; hanya tes `tests/mobile-*` dan laporan `docs/ciko-mobile-*` dimiliki CIKO. ZEYN koordinator, BUBU pemilik UI, WASP pemilik API. Status awal BELUM DIUJI.

## Batas bukti dan lingkungan

- Runtime CIKO terisolasi: `.e2e/mobile/ciko/runtime.json`, schema `gulhouse_test_mobile_ciko_20261019_105550_351`, server loopback65171. Password/token tidak dicetak atau dimasukkan laporan. Fixture CIKO tidak dipakai root.
- Android feasibility diperiksa dari PATH dan lokasi default: adb/emulator/java tidak ditemukan; ANDROID_HOME/ANDROID_SDK_ROOT tidak tersedia; folder default SDK/AndroidStudio/Java/Adoptium tidak ada. Native Android E2E sementara BLOCKED lingkungan. Tidak ada iOS simulator pada host Windows ini.
- Playwright terhadap export Expo web membuktikan perilaku React Native Web di browser. Ia **tidak** membuktikan native Android/iOS, native file picker, permission OS, keyboard/gesture/performance perangkat atau signed APK/IPA.
- Native component/source audit dan build/export adalah bukti terpisah. Memakai React Native `View/Text/Pressable/TextInput/ScrollView` dan API nyata harus terverifikasi. `WebView`, iframe atau HTML app wrapper tidak memenuhi scope. Membuka PDF eksternal yang terotorisasi tidak otomatis berarti app wrapper.
- Referensi visual: gambar lokal clipboard `codex-clipboard-ce9d6d72-e3bb-4af7-a64a-4c1dc4dc3f78.png`:teal, kartu putih membulat, foto properti nyata, bottom navigation gelap, hierarki teks dan CTA jelas.

## Matriks fitur dan aksi

| Area/screen | Aksi/hasil utama | Negatif/integritas |
|---|---|---|
| Public home/catalog | Properti/tipe/kamar/foto, pencarian/filter, loading/empty/error, detail dan booking | Harga/ketersediaan dari API; tidak pakai placeholder sebagai data nyata; filter reset bekerja |
| Room detail | Foto/gambar, fasilitas, harga, lokasi, kembali, survey/booking/onboarding | Kamar tidak tersedia dan kegagalan gambar/API; link eksternal bukan pengiriman otomatis |
| Initial identity | Nama/telepon/kamar/tanggal/jalur survey, persetujuan, upload KTP wajib, buku nikah opsional | Server menolak KTP kosong/fake MIME/oversize; upload bukan verifikasi identitas otomatis |
| Resident access | Kode privat/session, signout, restore, expiry, kode sekali tampil | Token tidak berada di URL/log; akses silang ditolak; logout tidak mempertahankan akses privat |
| Initial payment | Kesepakatan, invoice/sisa sewa/deposit, upload bukti, status dan riwayat | Upload tidak membuat ledger/payment_verified/unlock biodata; bukti milik case lain ditolak |
| Full identity | Biodata lengkap sesudah pelunasan tercatat+verifikasi, kontak darurat/alamat, field existing | Sebelum pembayaran ditolak API; setelah final tidak ditimpa tanpa proses yang sah |
| Facilities/history | Kondisi awal/bulanan/checkout,19 item/Bantal2, scan/foto, riwayat dan revisi | Sumber final immutable; draft tidak ditampilkan ke resident; empty berbeda dari baik |
| Recurring bills | Tagihan bulanan/riwayat pembayaran/kuitansi dan status sisa | Sewa vs deposit terpisah; periode/tanggal/tarif benar; scoped ke masa tinggal |
| Resident complaint | Input keluhan, status/tindak lanjut maintenance | Reserved/ended tidak membuat komplain aktif; case/tenant cross-access ditolak |
| Notice/checkout/refund | H−15 kalender, notice, rencana/rekening, hasil inspeksi/tagihan, H+2 aktual, bukti refund | Late flag tanpa penalti otomatis; saldo non-dispute tetap payable; setelah checkout riwayat tetap dapat dibuka |
| Admin login/dashboard | Login/session, ringkasan/KPI, navigasi seluruh modul, loading/error | Peran aktual dari server; stale/disabled/revoked tidak diberi write |
| Master property/type/room | List/search/filter/detail, tambah/edit, publication toggle, delete/cancel konfirmasi | Tidak menghilangkan fitur existing; destructive requires confirmation; constraint FK tampil jelas |
| Photos | Foto existing, upload slider/room, preview/rotate/delete | MIME/size/private path, progress/error; Cancel tidak menulis |
| Tenants | List/filter, detail, input/edit, stay dan draft contract | Aktif financial terms melalui proses sesuai; ended tidak bypass checkout managed |
| Lifecycle admin | Enroll existing+KTP, reserve/link stay, invoice, payment verify, activation | Preserve existing data, kode sekali tampil, prerequisite check-in ditegakkan API |
| Facility administration | Print/download template, scan/photo upload,19 quantities/conditions, OCR suggestion, draft correction, final/revision, maintenance | OCR tidak menentukan tanda tangan/penyebab kerusakan; qtyNULL/uninspected/draft maintenance/crosscase revision ditolak |
| Notice correction | Prefill, reason wajib, update tanggal/rekening dan audit | Original notice_date tetap, H15 recalc dari original, no fee, resident/closed denied |
| Booking/contracts/docs | Follow-up/conversion, draft/activate/renew/cancel/checkout, print, document upload/download | Tidak membypass managed lifecycle; file privat dan print melalui auth aman |
| Payments/billing/deposits | Generate periode, list/search, partial/full/overpayment, rent/deposit, proof upload/delete, receipt, refund | Duplicate submit/reference tidak menggandakan ledger; bayar refund butuh proof aktual; input money/date tervalidasi |
| Receivables/finance/reports | Filter periode, aging/sisa, expenses CRUD, report/export | Deposit bukan pendapatan; CSV formula injection escaped; export bukan plain private URL tanpa auth |
| Operations | Maintenance, inventory legacy, complaints/escalation, facilities, housekeeping, audits CRUD/status | Saat peran tidak berhak tombol tidak muncul dan API menolak; status/cost linkage sesuai existing |
| Notifications/templates/users | Draft WA, template CRUD/print, users/role/disable/delete sesuai role | Draft bukan klaim sent nyata; viewer tidak write; finance/maintenance tidak mengelola role |

## Matriks lintas layar

| Ukuran/metode | Cakupan | Kriteria |
|---|---|---|
| 320×800 dan390×844 Expo web | Seluruh screen utama + generic module family + drawer/modal/form uploads | Tidak ada overflow halaman, heading/angka/kondisi terbaca, CTA bisa disentuh, bottomnav tidak menutup konten |
| 768×1024 | Seluruh shell dan form/tabel dengan data | Tata letak tablet, dialog muat, scroll internal dapat dipakai, nav jelas |
| 1024×768/1440×900 | Export web fallback dan regression | Tidak ada shell menimpa konten, native design tidak menjadi halaman HTML lama |
| Keyboard browser | Login/input/menu/modal/tab actions | Label/nama aksesibel, fokus, Escape/cancel, restore focus, no trap yang salah |
| Native source/build | Semua platform branches, picker/download/auth storage | Native imports/URI multipart, secure token storage, OS-safe API base konfigurabel, no WebView |
| Android/iOS perangkat | Permission/picker/keyboard/safe area/back/gestures/install | Hanya dapat PASS setelah perangkat/runtime tersedia; saat ini belum diuji |

## Role/API matrix

- Admin/owner: seluruh area yang memang diizinkan backend.
- Manager: kecuali user/role management.
- Finance: keuangan dan workspace terkait; payment proof boleh, KTP/buku nikah tidak.
- Maintenance: operasional terkait; tidak melihat KTP/buku nikah/bukti pembayaran/refund/rekening sensitif.
- Viewer: read-only; tidak ada write meskipun direct endpoint dipanggil.
- Resident: hanya case/stay sendiri, read final history; tidak bisa admin actions atau dokumen penghuni lain.

Setiap temuan mencantumkan severity, screen/route/file, reproduksi, aktual vs harapan, bukti, dampak dan alternatif. Semua mutasi tes menunggu kontrak API dan fixture disepakati. Status PASS hanya pada cakupan yang benar-benar terverifikasi, bukan klaim semua fungsi native dari preview browser.
