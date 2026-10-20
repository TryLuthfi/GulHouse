<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $types = array('rent' => 'Sewa', 'deposit' => 'Deposit', 'other' => 'Lainnya'); ?>
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Kuitansi GH-PAY-<?= (int) $receipt['id']; ?></title>
<style>
*{box-sizing:border-box}body{margin:0;color:#17261f;background:#eef2ef;font:15px Arial,sans-serif}.toolbar,main{width:min(760px,calc(100% - 32px));margin:24px auto}main{padding:40px;background:white;border:1px solid #d4dfd7;border-radius:12px}header{border-bottom:3px solid #397653;padding-bottom:18px;display:flex;justify-content:space-between;gap:16px}h1{margin:0;font-size:25px}h2{font-size:17px;margin:26px 0 12px}.amount{padding:22px;background:#eff7f1;border-radius:8px;font-size:28px;font-weight:bold;margin:24px 0}dl{display:grid;grid-template-columns:165px 1fr;gap:13px;margin:0}dt{color:#5b6c60}dd{margin:0;overflow-wrap:anywhere}.note{color:#56635b;font-size:13px;line-height:1.6;margin-top:26px}button,a{display:inline-block;border:1px solid #bac9be;background:white;padding:10px 15px;border-radius:6px;color:#17261f;text-decoration:none;cursor:pointer}@media(max-width:500px){main{padding:22px}header{display:block}dl{grid-template-columns:1fr;gap:5px}dd{margin-bottom:10px}}@media print{body{background:white}.toolbar{display:none}main{width:100%;margin:0;padding:0;border:0;border-radius:0}@page{size:A4;margin:20mm}}
</style></head><body>
<div class="toolbar"><a href="<?= base_url('payments'); ?>">Kembali ke Pembayaran</a> <button type="button" onclick="window.print()">Cetak / Simpan PDF</button></div>
<main><header><div><h1>GUL HOUSE</h1><p>Kuitansi pembayaran manual</p></div><div><strong>GH-PAY-<?= (int) $receipt['id']; ?></strong><p><?= html_escape((string) ($receipt['payment_date'] ?: $receipt['payment_date_text'])); ?></p></div></header>
<div class="amount">Rp <?= number_format((int) $receipt['amount'], 0, ',', '.'); ?></div>
<dl>
<dt>Diterima dari</dt><dd><?= html_escape((string) ($receipt['receipt_tenant_name'] ?: '-')); ?></dd>
<dt>Properti / kamar</dt><dd><?= html_escape(trim($receipt['property_code'] . ' / ' . $receipt['room_label'])); ?></dd>
<dt>Periode invoice</dt><dd><?= html_escape($receipt['period_label']); ?></dd>
<dt>Jenis transaksi</dt><dd><?= html_escape(isset($types[$receipt['payment_type']]) ? $types[$receipt['payment_type']] : $receipt['payment_type']); ?></dd>
<dt>Metode</dt><dd><?= html_escape((string) ($receipt['method'] ?: '-')); ?></dd>
<dt>Referensi transaksi</dt><dd><?= html_escape((string) ($receipt['reference_no'] ?: '-')); ?></dd>
<dt>Catatan</dt><dd><?= html_escape((string) ($receipt['notes'] ?: '-')); ?></dd>
</dl>
<p class="note">Kuitansi ini mencatat satu transaksi yang diinput admin. Nominalnya bukan pernyataan pelunasan seluruh invoice. Transaksi deposit dicatat terpisah dari penerimaan sewa. Bukti transfer dapat dilampirkan melalui Dokumen dengan tipe Lainnya dan referensi nomor kuitansi.</p>
<p class="note">Dicetak: <?= date('d-m-Y H:i'); ?> WIB</p>
</main></body></html>
