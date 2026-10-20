# Ringkasan Implementasi Modul Operasional

Tanggal: 12 Oktober 2026

## Yang Diimplementasikan

Full MVP modul operasional sudah ditambahkan ke web master GUL HOUSE.

Menu baru:

- Booking
- Kontrak
- Dokumen
- Billing
- Piutang
- Deposit
- Maintenance
- Inventaris
- Komplain
- Fasilitas
- Housekeeping
- Audit Bulanan
- Keuangan
- Laporan
- WhatsApp
- Template Dokumen
- Admin & Role

## File Utama

- Patch database: `database/patch_gulhouse_operations_full_mvp_20261012.sql`
- Controller: `master/application/controllers/Operations.php`
- Model: `master/application/models/Operations_model.php`
- View utama: `master/application/views/operations/index.php`
- Field partial: `master/application/views/operations/partials/fields.php`
- Route: `master/application/config/routes.php`
- Panduan akses: `docs/gulhouse_operational_modules_access_20261012.md`

## Cara Akses

Local:

```text
http://localhost/GulHouse/master/login
```

VPS:

```text
https://gulhouse.co.id/master/login
```

Setelah login, semua menu baru muncul di sidebar master.

## Status Implementasi

Sudah ada:

- Tabel database untuk semua modul gap utama.
- Menu sidebar.
- Route URL.
- Halaman list.
- Form tambah/edit untuk modul yang butuh input.
- Hapus data untuk modul input.
- Filter status dan pencarian.
- Metric dasar total/open/done/nominal.
- Template dokumen kontrak, surat pernyataan, tata tertib, serah-terima, dan data penghuni.
- Template WhatsApp reminder.
- Convert booking menjadi penghuni, stay, kontrak draft, dan checklist dokumen.
- Generate billing bulanan dari data kamar dan stay aktif.
- Upload file dokumen fisik.
- Print/PDF kontrak dan template dokumen melalui print browser.
- Export CSV tiap modul.
- Dashboard KPI modul baru.
- Permission dasar role viewer read-only.
- Activity log untuk save/delete/convert/generate.

Masih lanjutan:

- Integrasi WhatsApp provider.
- Export PDF server-side otomatis.
- Permission matrix detail per field/aksi.

## Catatan Database

Patch sudah dijalankan ke database lokal `gulhouse_db`.

Saat testing, env pusat `D:\XAMPP\htdocs\DatabaseTKM\.env` masih mengarah ke `databasetkm_com`; patch sempat membuat tabel baru non-destruktif di database itu juga. Tidak ada tabel lama yang dihapus atau diubah. Aplikasi GUL HOUSE lokal tetap membaca `landing/.env` yang mengarah ke `gulhouse_db`.
