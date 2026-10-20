# Akses Modul Operasional GUL HOUSE

Dokumen singkat ini untuk orang baru yang akan mencoba modul master.

## 1. Jalankan Patch Database

Jalankan patch ini ke database `gulhouse_db`:

```sql
database/patch_gulhouse_operations_full_mvp_20261012.sql
```

Patch ini menambah tabel:

- Kontrak
- Dokumen kontrak/penghuni
- Deposit ledger
- Maintenance ticket
- Inventaris
- Komplain
- Fasilitas
- Housekeeping
- Audit bulanan
- Keuangan/expense
- WhatsApp notification draft
- Template dokumen

## 2. Masuk Master

Local:

```text
http://localhost/GulHouse/master/login
```

VPS:

```text
https://gulhouse.co.id/master/login
```

Setelah login, buka menu dari sidebar.

## 3. Urutan Coba Modul

1. **Booking**  
   Melihat request booking dari landing page. Tombol **Convert** membuat penghuni, stay, kontrak draft, dan checklist dokumen otomatis.

2. **Kontrak**  
   Buat kontrak untuk penghuni, kamar, tanggal mulai/akhir, harga sewa, deposit, dan status kontrak.

3. **Dokumen**  
   Checklist KTP, kontrak, surat pernyataan, serah-terima, inventaris, kendaraan, dan dokumen lain. File fisik bisa diupload dari form.

4. **Deposit**  
   Catat deposit masuk, potongan, refund, dan adjustment.

5. **Billing**  
   Lihat tagihan kamar per periode sebelum pembayaran. Tombol **Generate Billing** membuat bill periode terpilih untuk semua kamar.

6. **Piutang**  
   Lihat tagihan yang belum lunas dan aging jatuh tempo.

7. **Maintenance**  
   Buat ticket kerusakan, PIC/vendor, deadline, biaya, dan status penyelesaian.

8. **Inventaris**  
   Catat kondisi item kamar saat check-in, rutin, dan check-out.

9. **Komplain**  
   Catat keluhan penghuni, PIC, deadline, dan solusi.

10. **Fasilitas**  
   Checklist fasilitas kamar/area umum seperti AC, lampu, CCTV, mesin cuci, dapur, parkir.

11. **Housekeeping**  
    Checklist kebersihan harian/mingguan.

12. **Audit Bulanan**  
    Review rutin owner untuk kamar, inventaris, AC, plumbing, listrik, kunci/pintu, kebersihan, CCTV/keamanan.

13. **Keuangan**  
    Catat biaya operasional, maintenance, listrik, supplies, vendor, dan pengeluaran lain.

14. **Laporan**  
    Ringkasan owner: kamar, piutang, maintenance open, komplain open, dan biaya.

15. **WhatsApp**  
    Draft reminder dan bukti pembayaran. Integrasi pengiriman otomatis belum disambungkan ke provider.

16. **Template Dokumen**  
    Template kontrak, surat pernyataan, tata tertib, serah-terima, dan data penghuni. Tombol **Print** bisa dipakai untuk cetak/PDF.

17. **Admin & Role**  
    Monitoring akun admin dan role. Perubahan password tetap dilakukan terpisah.

## 4. Catatan Status

Ini adalah full MVP operasional: data bisa dicatat, diedit, dilihat, dikonversi dari booking, digenerate billing, upload dokumen, print dokumen, export CSV, dan dipakai sebagai dasar operasional.

Yang sudah termasuk:

- Upload file fisik dokumen via form.
- Print/PDF kontrak dan template dokumen melalui print browser.
- Export CSV tiap modul.
- Permission dasar: role viewer tidak bisa write.
- Activity log untuk save/delete/convert/generate.
- Generate billing bulanan.
- Convert booking menjadi penghuni + kontrak draft.

Yang masih membutuhkan layanan eksternal:

- Pengiriman WhatsApp otomatis ke provider butuh API dan credential.
- Export PDF server-side bisa ditambahkan jika ingin file PDF otomatis tanpa dialog print browser.
