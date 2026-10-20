# CIKO — matriks penerimaan lifecycle GulHouse

Tanggal: 19 Oktober 2026. Pemeriksa independen read-only implementasi. Hanya data sintetis pada schema `gulhouse_test_*` boleh dipakai untuk transaksi audit.

Status awal: BELUM DIUJI. Daftar ini adalah kriteria, bukan klaim kelulusan.

| Area | Jalur utama | Uji negatif / integritas |
|---|---|---|
| Onboarding | WhatsApp/walk-in/website; identitas awal dan KTP; full biodata setelah pembayaran diverifikasi | KTP kosong/jenis file palsu/oversize; ID tenant/stay milik orang lain; token salah/kedaluwarsa; bypass pembayaran |
| Check-in | Sewa awal lunas, deposit, administrasi, pemeriksaan awal dan serah terima | Route kontrak legacy, status dokumen tanpa bukti, inventory otomatis dianggap baik |
| Fasilitas awal | Paket kamar + masa tinggal + item aktual + kondisi + scan fisik bertanda tangan | Scan kamar salah; item hilang; tanpa konfirmasi manusia; duplikat |
| Bulanan | Periode unik per paket, tanggal aktual, petugas, kondisi dan tindak lanjut | Periode/tanggal tidak valid; scan berbeda kamar; final diedit/scan ditimpa |
| Revisi | Koreksi baru beralasan, sumber final tetap utuh | Parent final milik stay lain; menghapus final; mengubah sumber scan |
| Pembacaan dokumen | Bantuan ekstraksi berlabel hasil belum diverifikasi | Data kosong/tulisan tak terbaca tidak menjadi good; mock/manual tidak diklaim OCR nyata |
| Riwayat penghuni | Kondisi awal → bulanan → maintenance → checkout, termasuk arsip sendiri setelah keluar | Akses scan/riwayat penghuni lain, data penghuni lama bocor ke penghuni baru |
| Pemberitahuan | H−15 hari kalender dari rencana keluar, keterlambatan ditandai | Tepat 15 hari vs 14 hari; tanggal invalid; otomatis memotong deposit |
| Checkout | Kewajiban diselesaikan, pemeriksaan akhir dan kunci, tanggal aktual | Bypass route lama; checkout ganda; tagihan mendatang berbayar; saldo negatif |
| Refund | H+2 hari kalender dari tanggal keluar aktual; kewajiban vs pembayaran terpisah; proof transfer aktual | Dispute menahan seluruh saldo; lebih dari saldo; paid tanpa bukti; double submit; legacy ledger bypass |
| Keamanan | POST + CSRF, RBAC server, unduhan privat, no-store | Direct controller route, GET mutation, viewer/maintenance finance-write, path traversal |
| UI | Desktop/tablet/mobile, seluruh tombol baru + empty/error/success | Overflow, focus/label, confirmation cancel, submit ulang, link rusak |

Catatan baseline: tes legacy `gulhouse-lifecycle.e2e.ts` mengasumsikan aktivasi sebelum pembayaran sewa, penandaan dokumen verified tanpa upload, serta refund pada checkout. Ekspektasi tersebut harus disesuaikan agar tes lama tidak mengesahkan perilaku yang bertentangan dengan kebutuhan baru.
