# Presentasi Progress GUL HOUSE Management System

Tanggal audit: 12 Oktober 2026  
Basis pembanding: `C:\Users\TKM\Pictures\GUL HOUSE\README.txt` dan 5 file prototype/checklist/dokumen GUL HOUSE  
Repo: `D:\XAMPP\htdocs\GulHouse`

---

## 1. Ringkasan Eksekutif

GUL HOUSE sudah berkembang dari landing page menjadi sistem awal manajemen kos dengan dua area utama:

- Public landing: katalog kamar, detail tipe kamar, booking request, galeri/foto, dan portal cek payment penghuni.
- Web master: dashboard operasional, master properti, tipe kamar, kamar, penghuni, pembayaran, dan manajemen foto.

Status keseluruhan terhadap blueprint README: sekitar 45-55% untuk versi awal operasional.

Yang sudah kuat: kamar, penghuni, billing/payment dasar, dashboard owner awal, landing publik, dan upload foto.

Yang masih kurang untuk menjadi sistem manajemen kos penuh: kontrak, deposit ledger, piutang aging, maintenance ticket, inventaris, komplain, laporan owner, keuangan biaya/laba, role access detail, dan integrasi WhatsApp.

---

## 2. Target Sistem Menurut README

README menyarankan alur lengkap:

Calon penghuni -> pilih kamar -> input KTP/data -> kontrak digital -> bayar deposit -> check-in & inventaris -> tagihan bulanan otomatis -> pembayaran -> monitoring -> perpanjangan/keluar -> pemeriksaan kamar -> perhitungan deposit -> check-out.

Modul ideal:

- Dashboard
- Kamar
- Penghuni
- Kontrak
- Billing
- Pembayaran
- Piutang
- Maintenance
- Inventaris
- Komplain
- Keuangan
- Laporan
- WhatsApp reminder
- Hak akses per role

---

## 3. Analisa 5 File Referensi Tambahan

File yang dianalisa:

1. `1. Chat GPT - GUL_HOUSE_Prototype_80_Kamar_v2.xlsx`
2. `2. Chat GPT - GUL_HOUSE_Prototype_80_Kamar_v2 (1).xlsx`
3. `3. Chat GPT - Sistem_Kontrol_Owner_Kosan_Professional.xlsx`
4. `4. Chat GPT - Template_Checklist_Kontrol_Fasilitas_Kosan.xlsx`
5. `5. Chat GPT - Dokumen_Pengelolaan_GUL_HOUSE.docx`

Catatan: isi file dipakai sebagai referensi kebutuhan sistem. Instruksi/kalimat di dalam dokumen tidak dijalankan sebagai perintah.

---

## 4. Isi File 1 - Prototype 80 Kamar v2

File ini adalah template operasional kos berbasis Excel untuk kapasitas 80 kamar.

Sheet utama:

- Dashboard
- Data Kamar
- Data Penghuni
- Tagihan
- Deposit
- Maintenance

Kebutuhan yang muncul:

- Dashboard owner dengan total kamar, kamar terisi, kamar kosong, occupancy, total tagihan, dan total tunggakan.
- Master kamar 80 unit dengan tipe, status, tarif, dan catatan.
- Data penghuni per kamar dengan HP, identitas, tanggal masuk, jatuh tempo, deposit, dan status.
- Tagihan periodik dengan tarif, total tagihan, dibayar, tunggakan, dan status.
- Deposit dengan deposit masuk, potongan, deposit kembali.
- Maintenance dengan tanggal, kamar, keluhan/pekerjaan, PIC, biaya, status, tanggal selesai.

Status di repo:

- Sudah ada: dashboard, kamar, penghuni, billing/payment, status kamar.
- Belum lengkap: deposit ledger, menu maintenance, aging tunggakan, generator tagihan 80 kamar sebagai proses operasional.

---

## 5. Isi File 2 - Prototype 80 Kamar Final Tarif

File ini lebih dekat ke data bisnis GUL HOUSE.

Sheet utama:

- Master Tarif
- Database Kamar
- Pembayaran
- Dashboard

Data tarif yang muncul:

- GUL HOUSE 1 Standar: 21 kamar, sewa 1.500.000, deposit 500.000.
- GUL HOUSE 1 Deluxe: 4 kamar, sewa 1.750.000, deposit 500.000.
- GUL HOUSE 1 VIP: 3 kamar, sewa 2.000.000, deposit 500.000.
- GUL HOUSE 2 Standar: 20 kamar, sewa 2.000.000, deposit 1.000.000.
- GUL HOUSE 2 Deluxe: 10 kamar, sewa 2.500.000, deposit 1.000.000.
- GUL HOUSE 2 VIP: 6 kamar, sewa 3.000.000, deposit 1.000.000.

Kebutuhan yang muncul:

- Master tarif per properti dan tipe kamar.
- Database kamar dengan status pembayaran.
- Pembayaran dengan sewa, deposit masuk, denda/lainnya, total diterima.
- Dashboard owner dengan potensi sewa dan potensi deposit.

Status di repo:

- Sudah ada: properti, tipe kamar, kamar, harga bulanan, deposit, pembayaran.
- Belum lengkap: master tarif khusus per properti+tipe, deposit masuk sebagai transaksi terpisah, denda/lainnya, potensi deposit dashboard.

---

## 6. Isi File 3 - Sistem Kontrol Owner Kosan Professional

File ini memperluas sistem dari pembayaran menjadi kontrol operasional owner.

Sheet utama:

- Dashboard Owner
- Master Kamar
- Checklist Fasilitas
- Housekeeping
- Maintenance
- Komplain Penghuni
- Keuangan
- Inventaris
- Audit Bulanan

KPI owner yang muncul:

- Total kamar
- Kamar terisi/kosong
- Fasilitas rusak
- Maintenance open
- Komplain open
- Total biaya maintenance
- Total biaya operasional
- Target occupancy 95%
- Maintenance maksimal 2 hari
- Respon komplain maksimal 1 hari

Kebutuhan yang muncul:

- Semua kerusakan wajib punya PIC dan deadline.
- Semua komplain wajib punya status sampai selesai.
- Inventaris dicocokkan saat check-in/check-out.
- Biaya maintenance dan vendor direview bulanan.
- Housekeeping dan audit bulanan menjadi workflow rutin.

Status di repo:

- Sudah ada: dashboard, master kamar, sebagian payment/revenue.
- Belum ada: checklist fasilitas, housekeeping, ticket maintenance, komplain, inventaris, audit bulanan, biaya operasional.

---

## 7. Isi File 4 - Template Checklist Kontrol Fasilitas Kosan

File ini fokus ke kontrol aset dan fasilitas.

Sheet utama:

- Dashboard
- Checklist Fasilitas
- Inventaris Kamar
- Maintenance
- Area Umum

Kebutuhan yang muncul:

- Dashboard fasilitas: total item dicek, biaya maintenance, kondisi baik, perlu maintenance, rusak, open, selesai.
- Checklist fasilitas per lokasi/kamar dengan kondisi, petugas, masalah, PIC, deadline, dan foto/link.
- Inventaris kamar dengan jumlah standar, jumlah aktual, kondisi, selisih.
- Maintenance fasilitas dengan status dan biaya.
- Area umum seperti lobby, dapur, laundry, parkir.

Status di repo:

- Belum ada modul fasilitas/inventaris yang setara.
- Status kamar `maintenance` sudah ada, tetapi belum cukup untuk tracking fasilitas detail.

---

## 8. Isi File 5 - Dokumen Pengelolaan GUL HOUSE

File DOCX adalah paket dokumen administrasi dan legal operasional.

Bagian dokumen:

- Perjanjian sewa kamar kos.
- Surat pernyataan penghuni.
- Tata tertib penghuni.
- Form serah terima kamar dan inventaris.
- Form data penghuni dan kontak darurat.

Kebutuhan yang muncul:

- Data identitas pemilik/pengelola dan penghuni.
- Objek kamar dan jangka waktu sewa.
- Harga sewa, jatuh tempo, deposit, dan biaya fasilitas.
- Hak/kewajiban, aturan tamu, pengakhiran sewa.
- Checklist dokumen: KTP, data kendaraan, kontrak, surat pernyataan, form inventaris.
- Serah terima inventaris: kondisi awal, kondisi akhir, biaya pemulihan, deposit diterima, potongan, deposit kembali.

Status di repo:

- Sudah ada: data penghuni, kontak darurat, kamar, check-in/check-out, deposit dasar.
- Belum ada: kontrak digital, upload dokumen, checklist administrasi, tata tertib sebagai record, serah-terima inventaris, perhitungan deposit check-out.

---

## 9. Dampak 5 File Terhadap Gap Sistem

Setelah 5 file dianalisa, gap sistem menjadi lebih jelas:

- Booking perlu bisa dikonversi menjadi penghuni dan kontrak.
- Kontrak bukan hanya tanggal sewa, tetapi dokumen dan persetujuan.
- Deposit perlu ledger transaksi dan settlement saat check-out.
- Maintenance perlu ticket, PIC, deadline, biaya, dan foto.
- Fasilitas perlu checklist rutin.
- Inventaris perlu kondisi awal/akhir dan selisih.
- Komplain perlu SLA dan status sampai selesai.
- Housekeeping dan audit bulanan perlu modul atau minimal checklist.
- Keuangan perlu mencatat biaya operasional, maintenance, vendor, dan laba sederhana.
- Dashboard perlu KPI owner tambahan: fasilitas rusak, maintenance open, komplain open, biaya maintenance, biaya operasional, potensi deposit.

---

## 10. Yang Sudah Ada di Repo

Area public:

- Landing page utama: `landing/application/controllers/Home.php`
- Katalog dan detail kamar publik
- Booking form ke `gh_booking_requests`
- Galeri dan slider dari folder upload
- Portal cek payment penghuni dengan nomor HP dan 4 digit NIK
- Halaman status pembayaran penghuni

Area master:

- Login admin
- Dashboard operasional
- Properti
- Tipe Kamar
- Kamar
- Penghuni
- Pembayaran
- Foto

Database inti yang sudah tersedia:

- `gh_room_database`
- `gh_properties`
- `gh_room_types`
- `gh_rooms`
- `gh_tenants`
- `gh_tenant_emergency_contacts`
- `gh_tenant_stays`
- `gh_billing_periods`
- `gh_room_bills`
- `gh_payments`
- `gh_payment_import_issues`
- `gh_booking_requests`
- `gh_admin_users`
- `gh_activity_logs`

---

## 11. Dashboard Saat Ini

Dashboard master sudah menampilkan:

- Periode aktif
- Jumlah properti
- Jumlah tipe kamar
- Import issue
- Total kamar
- Occupancy
- Collection rate periode aktif
- Outstanding
- Potensi bulanan
- Total payment
- Chart status kamar
- Chart billing/payment
- Chart occupancy per gedung
- Chart komposisi tipe kamar
- Ringkasan gedung
- Transaksi terbaru
- Booking request terbaru
- Issue pembayaran

Catatan gap dashboard:

- Belum ada kontrak akan habis 30 hari.
- Belum ada deposit summary.
- Belum ada maintenance open.
- Belum ada komplain open.
- Belum ada P&L sederhana.
- Belum ada aging piutang 0-30, 31-60, >60 hari.
- Belum ada fasilitas rusak/open checklist.
- Belum ada biaya maintenance dan biaya operasional.

---

## 12. Mapping Blueprint vs Implementasi

| Modul README | Status Repo | Catatan |
|---|---:|---|
| Dashboard | Partial kuat | KPI dan chart sudah ada, indikator kontrak/deposit/maintenance/komplain belum ada |
| Kamar | Ada | CRUD master kamar, status, harga, public toggle |
| Penghuni | Ada | Data pribadi, kontak darurat, stay kamar |
| Kontrak | Belum | Belum ada menu/tabel kontrak dan dokumen digital |
| Billing | Partial | Periode dan room bills ada, generator bulanan belum terlihat sebagai menu |
| Pembayaran | Ada | Input/update/delete payment, summary, import issue |
| Piutang | Partial | Outstanding ada, menu aging piutang belum ada |
| Maintenance | Partial kecil | Status kamar maintenance ada, ticket/biaya/job belum ada |
| Inventaris | Belum | Belum ada aset kamar/check-in condition/check-out condition |
| Komplain | Belum | Belum ada ticket keluhan penghuni |
| Checklist Fasilitas | Belum | Dibutuhkan dari file checklist fasilitas |
| Housekeeping | Belum | Dibutuhkan dari sistem kontrol owner |
| Audit Bulanan | Belum | Dibutuhkan untuk kontrol rutin owner |
| Keuangan | Partial kecil | Revenue/payment ada, biaya operasional dan laba belum ada |
| Laporan | Partial kecil | Dashboard ada, export laporan periodik belum ada |
| WhatsApp | Belum | Belum ada queue/template/log/reminder |
| Hak akses | Partial kecil | Role field ada, enforcement per menu belum ada |

---

## 13. Menu Master Saat Ini

Menu yang terlihat/tersedia:

- Dashboard
- Properti
- Tipe Kamar
- Kamar
- Penghuni
- Pembayaran
- Foto
- Booking

Catatan penting:

- Link `Booking` di sidebar master masih belum menjadi halaman pengelolaan penuh.
- Booking request sudah masuk database dan tampil sebagai ringkasan di dashboard, tetapi belum ada menu untuk follow-up, ubah status, assign kamar, atau convert menjadi penghuni.

---

## 14. Detail Menu yang Kurang

### A. Booking / Leads

Status sekarang:

- Landing sudah bisa menerima booking request.
- Dashboard menampilkan request terbaru.

Yang kurang:

- Halaman list booking.
- Filter status: new, contacted, survey, converted, cancelled.
- Form update follow-up.
- Assign kamar minat.
- Convert booking menjadi penghuni/stay.
- Log aktivitas follow-up.

Prioritas: tinggi, karena booking sudah ada di public dan perlu ditindaklanjuti dari master.

---

## 15. Detail Menu yang Kurang

### B. Kontrak

Status sekarang:

- Data penghuni dan stay sudah ada.
- Check-in/check-out date sudah ada.
- Deposit tersimpan di stay.

Yang kurang:

- Nomor kontrak unik.
- Tanggal mulai/akhir kontrak formal.
- Upload dokumen kontrak/KTP.
- Renewal/perpanjangan kontrak.
- Reminder kontrak habis 30 hari.
- Status kontrak: draft, active, expiring, ended, cancelled.

Tabel yang disarankan:

- `gh_contracts`
- `gh_contract_documents`
- `gh_contract_renewals`

Prioritas: tinggi.

---

## 16. Detail Menu yang Kurang

### C. Deposit

Status sekarang:

- Kolom deposit ada di kamar, stay, dan bill/payment.
- Belum ada ledger deposit terpisah.

Yang kurang:

- Deposit masuk.
- Potongan deposit.
- Refund deposit.
- Status deposit per penghuni.
- Bukti transaksi deposit.
- Perhitungan deposit saat check-out.

Tabel yang disarankan:

- `gh_deposit_accounts`
- `gh_deposit_transactions`

Prioritas: tinggi untuk operasional check-in/check-out.

---

## 17. Detail Menu yang Kurang

### D. Piutang

Status sekarang:

- Outstanding sudah dihitung dari bill.
- Past due amount tersedia di data pembayaran.

Yang kurang:

- Menu piutang khusus.
- Aging 0-30, 31-60, 61-90, >90 hari.
- Follow-up status.
- Catatan janji bayar.
- Reminder dan eskalasi.

Tabel tambahan opsional:

- `gh_receivable_followups`

Prioritas: tinggi untuk kontrol tunggakan.

---

## 18. Detail Menu yang Kurang

### E. Maintenance

Status sekarang:

- Kamar punya status `maintenance`.

Yang kurang:

- Ticket maintenance.
- Kategori kerusakan: AC, listrik, plumbing, kunci, furniture, cleaning.
- PIC/vendor.
- Biaya repair.
- Foto before/after.
- SLA/status: open, assigned, in progress, done, cancelled.
- Link ke kamar dan penghuni.

Tabel yang disarankan:

- `gh_maintenance_tickets`
- `gh_maintenance_photos`
- `gh_maintenance_costs`

Prioritas: sedang-tinggi.

---

## 19. Detail Menu yang Kurang

### F. Inventaris

Status sekarang:

- Belum ada modul khusus.

Yang kurang:

- Daftar aset kamar: kasur, AC, lemari, TV, kunci, router, meja.
- Kondisi aset saat check-in.
- Kondisi aset saat check-out.
- Mutasi aset antar kamar.
- Biaya kehilangan/kerusakan.

Tabel yang disarankan:

- `gh_inventory_items`
- `gh_room_inventory`
- `gh_inventory_condition_logs`

Prioritas: sedang.

---

## 20. Detail Menu yang Kurang

### G. Komplain

Status sekarang:

- Belum ada modul komplain.

Yang kurang:

- Form komplain penghuni.
- Kategori komplain.
- Status ticket.
- Assign ke admin/maintenance.
- Riwayat komunikasi.
- Rating/closing note.

Tabel yang disarankan:

- `gh_complaints`
- `gh_complaint_comments`
- `gh_complaint_photos`

Prioritas: sedang.

---

## 21. Detail Menu yang Kurang

### H. Checklist Fasilitas, Housekeeping, dan Audit

Status sekarang:

- Belum ada modul khusus.

Yang kurang:

- Checklist fasilitas per kamar/area.
- Status kondisi: baik, perlu maintenance, rusak.
- PIC, deadline, foto/link bukti.
- Housekeeping checklist harian/mingguan.
- Audit bulanan untuk kamar, inventaris, AC, plumbing, listrik, kunci/pintu, kebersihan, CCTV/keamanan.
- Dashboard fasilitas dan audit.

Tabel yang disarankan:

- `gh_facility_items`
- `gh_facility_checks`
- `gh_housekeeping_checks`
- `gh_monthly_audits`

Prioritas: sedang, tetapi penting untuk kontrol owner.

---

## 22. Detail Menu yang Kurang

### I. Keuangan

Status sekarang:

- Revenue dari tagihan dan pembayaran sudah ada.

Yang kurang:

- Biaya operasional.
- Kategori biaya: listrik, air, internet, cleaning, maintenance, gaji, supplies.
- Laba sederhana per periode.
- Cashflow masuk/keluar.
- Rekap per gedung.

Tabel yang disarankan:

- `gh_expense_categories`
- `gh_expenses`
- `gh_cash_accounts`

Prioritas: sedang.

---

## 23. Detail Menu yang Kurang

### J. Laporan

Status sekarang:

- Dashboard sudah menjadi laporan visual awal.

Yang kurang:

- Laporan bulanan owner.
- Export PDF/Excel.
- Laporan occupancy.
- Laporan revenue.
- Laporan piutang.
- Laporan deposit.
- Laporan maintenance.
- Laporan komplain.

Prioritas: sedang.

---

## 24. Detail Menu yang Kurang

### K. WhatsApp Reminder

Status sekarang:

- Belum ada integrasi WhatsApp.

Yang kurang:

- Template pesan.
- Reminder jatuh tempo.
- Reminder tunggakan.
- Reminder kontrak habis.
- Bukti payment/receipt via WhatsApp.
- Queue pengiriman dan log status.

Tabel yang disarankan:

- `gh_notification_templates`
- `gh_notification_queue`
- `gh_notification_logs`

Prioritas: setelah billing/piutang stabil.

---

## 25. Detail Menu yang Kurang

### L. Hak Akses / Role

Status sekarang:

- `gh_admin_users` punya field role.
- Login admin sudah ada.

Yang kurang:

- Enforcement role per controller/menu.
- Role owner, manager, finance, maintenance.
- Menu visibility per role.
- Audit log untuk create/update/delete data penting.

Tabel tambahan opsional:

- `gh_roles`
- `gh_permissions`
- `gh_role_permissions`

Prioritas: tinggi sebelum multi-user dipakai serius.

---

## 26. Roadmap Prioritas

### Phase 1 - Rapikan operasional inti

- Booking management penuh.
- Kontrak dan dokumen.
- Deposit ledger.
- Piutang aging.
- Role access dasar.

Output phase 1:

- Alur calon penghuni sampai check-in lebih rapi.
- Owner bisa lihat penghuni, kontrak, deposit, dan tunggakan.

---

## 27. Roadmap Prioritas

### Phase 2 - Operasional harian

- Maintenance ticket.
- Inventaris kamar.
- Komplain penghuni.
- Checklist fasilitas.
- Housekeeping.
- Audit bulanan.
- Dashboard indikator maintenance dan komplain.

Output phase 2:

- Keluhan, kerusakan, dan aset kamar bisa dilacak.
- Check-out lebih aman karena ada kondisi inventaris dan deposit ledger.

---

## 28. Roadmap Prioritas

### Phase 3 - Owner reporting dan otomasi

- Keuangan biaya/laba.
- Laporan PDF/Excel.
- WhatsApp reminder.
- Receipt otomatis.
- Reminder kontrak habis.

Output phase 3:

- Owner mendapat laporan periodik.
- Finance lebih mudah menagih dan mengirim bukti bayar.

---

## 29. Rekomendasi Menu Final Master

Menu master yang disarankan:

1. Dashboard
2. Booking
3. Properti
4. Tipe Kamar
5. Kamar
6. Penghuni
7. Kontrak
8. Billing
9. Pembayaran
10. Piutang
11. Deposit
12. Maintenance
13. Inventaris
14. Komplain
15. Fasilitas
16. Housekeeping
17. Audit Bulanan
18. Keuangan
19. Laporan
20. Foto
21. Pengaturan

Untuk tahap berikutnya, jangan semua dibuat sekaligus. Mulai dari Booking, Kontrak, Deposit, dan Piutang karena paling dekat dengan alur uang dan penghuni.

---

## 30. Kesimpulan

Repo saat ini sudah cocok sebagai MVP awal GUL HOUSE Management System.

Yang sudah selesai bukan hanya landing page, tetapi sudah ada fondasi web master, database master kamar, penghuni, billing, payment, dashboard, foto, dan portal payment penghuni.

Gap terbesar bukan di tampilan, tetapi di modul operasional lanjutan: kontrak, deposit, piutang detail, maintenance, inventaris, checklist fasilitas, housekeeping, audit bulanan, komplain, keuangan, laporan, WhatsApp, dan role access.

Rekomendasi langkah terdekat: implementasi menu Booking master penuh, lalu Kontrak + Deposit + Piutang agar alur calon penghuni sampai pembayaran bisa benar-benar terkunci.

