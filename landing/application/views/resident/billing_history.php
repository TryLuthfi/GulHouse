<?php defined('BASEPATH') OR exit('No direct script access allowed');
    $billing = isset($resident_billing) && is_array($resident_billing) ? $resident_billing : array();
    $billingContext = isset($billing['context']) && is_array($billing['context']) ? $billing['context'] : array();
    $currentBill = isset($billing['current_bill']) && is_array($billing['current_bill']) ? $billing['current_bill'] : array();
    $billHistory = isset($billing['bill_history']) && is_array($billing['bill_history']) ? $billing['bill_history'] : array();
    $billPayments = isset($billing['payments']) && is_array($billing['payments']) ? $billing['payments'] : array();
    $administration = isset($billing['portal']) && is_array($billing['portal']) ? $billing['portal'] : array();
    $billStatusLabels = array('paid'=>'Lunas','partial'=>'Dibayar sebagian','unpaid'=>'Belum dibayar','overpaid'=>'Lebih bayar','reserved'=>'Reservasi','internal'=>'Internal','empty'=>'Kosong','unknown'=>'Belum diterbitkan');
    $billStatus = function($value) use($billStatusLabels) { return isset($billStatusLabels[$value]) ? $billStatusLabels[$value] : gh_lc_label($value); };
?>
<section class="lc-card" id="tagihan">
    <p class="eyebrow">Portal pembayaran penghuni</p><h2>Tagihan bulanan &amp; riwayat pembayaran</h2>
    <?php if (!$billingContext): ?><p class="lc-help">Tagihan bulanan tersedia setelah pengelola menghubungkan reservasi dengan masa tinggal Anda.</p><?php else: ?>
        <p class="muted"><?= gh_lc_e(gh_lc_v($billingContext,'display_room')); ?> · <?= gh_lc_e(gh_lc_v($billingContext,'room_type')); ?></p>
        <form method="get" action="<?= base_url('resident/portal#tagihan'); ?>"><div class="lc-form-footer"><label>Periode tagihan<input type="month" name="period" required value="<?= gh_lc_e(gh_lc_v($currentBill,'period_key',date('Y-m'))); ?>"></label><button type="submit">Tampilkan periode</button></div></form>
        <?php if ($currentBill): ?>
            <div class="lc-note"><strong><?= gh_lc_e(gh_lc_v($currentBill,'period_label')); ?> · <?= gh_lc_e($billStatus(gh_lc_v($currentBill,'bill_status','unknown'))); ?></strong><?php if (!empty($currentBill['is_draft'])): ?><p>Tagihan periode ini belum diterbitkan pengelola. Tarif di bawah merupakan rincian sementara.</p><?php endif; ?></div>
            <dl class="lc-kv"><dt>Tagihan sewa</dt><dd><?= gh_lc_money(gh_lc_v($currentBill,'base_price',0)); ?></dd><dt>Pembayaran tercatat</dt><dd><?= gh_lc_money(gh_lc_v($currentBill,'paid_total',0)); ?></dd><dt>Sisa tagihan sewa</dt><dd><strong><?= gh_lc_money(gh_lc_v($currentBill,'remaining_amount',0)); ?></strong></dd><?php if(!empty($currentBill['due_date'])): ?><dt>Jatuh tempo tagihan</dt><dd><?= gh_lc_e($currentBill['due_date']); ?></dd><?php endif; ?></dl>
        <?php endif; ?>
        <h3 style="margin-top:24px">Riwayat periode tagihan</h3>
        <div class="lc-table-wrap"><table><thead><tr><th>Periode</th><th>Status</th><th>Tagihan sewa</th><th>Dibayar</th><th>Sisa</th><th>Transaksi</th></tr></thead><tbody>
            <?php foreach($billHistory as $item): ?><tr><td><?= gh_lc_e(gh_lc_v($item,'period_label')); ?></td><td><?= gh_lc_e($billStatus(gh_lc_v($item,'bill_status','unknown'))); ?></td><td><?= gh_lc_money(gh_lc_v($item,'base_price',0)); ?></td><td><?= gh_lc_money(gh_lc_v($item,'paid_total',0)); ?></td><td><?= gh_lc_money(gh_lc_v($item,'remaining_amount',0)); ?></td><td><a href="<?= base_url('resident/portal?'.http_build_query(array('period'=>gh_lc_v($item,'period_key',''))).'#tagihan'); ?>">Lihat transaksi</a></td></tr><?php endforeach; ?>
            <?php if(!$billHistory): ?><tr><td colspan="6">Belum ada riwayat tagihan.</td></tr><?php endif; ?>
        </tbody></table></div>
        <h3 style="margin-top:24px">Transaksi periode yang dipilih</h3>
        <div class="lc-table-wrap"><table><thead><tr><th>Tanggal</th><th>Nominal</th><th>Jenis</th><th>Metode / referensi</th></tr></thead><tbody>
            <?php foreach($billPayments as $payment): ?><tr><td><?= gh_lc_e(gh_lc_v($payment,'payment_date',gh_lc_v($payment,'payment_date_text'))); ?></td><td><?= gh_lc_money(gh_lc_v($payment,'amount',0)); ?></td><td><?= gh_lc_e(gh_lc_v($payment,'payment_type')==='rent'?'Sewa':(gh_lc_v($payment,'payment_type')==='deposit'?'Deposit':'Lainnya')); ?></td><td><?= gh_lc_e(gh_lc_v($payment,'method')); ?> · <?= gh_lc_e(gh_lc_v($payment,'reference_no')); ?></td></tr><?php endforeach; ?>
            <?php if(!$billPayments): ?><tr><td colspan="4">Belum ada transaksi untuk periode ini.</td></tr><?php endif; ?>
        </tbody></table></div>
        <p class="lc-help">Saldo mengikuti transaksi yang telah dicatat pengelola. Upload bukti pembayaran tidak otomatis melunasi tagihan. Sewa, deposit dan denda dicatat terpisah.</p>
        <?php if($administration): ?>
            <details><summary>Kontrak, deposit &amp; dokumen administrasi</summary><a class="lc-btn secondary" href="<?= base_url('documents/house_rules'); ?>" target="_blank" rel="noopener">Lihat tata tertib</a>
                <div class="lc-table-wrap"><table><thead><tr><th>Kontrak</th><th>Periode</th><th>Status</th><th>Sewa bulanan</th></tr></thead><tbody><?php foreach(gh_lc_v($administration,'contracts',array()) as $contract): ?><tr><td><?= gh_lc_e(gh_lc_v($contract,'contract_no')); ?></td><td><?= gh_lc_e(gh_lc_v($contract,'start_date')); ?> s.d. <?= gh_lc_e(gh_lc_v($contract,'end_date')); ?></td><td><?= gh_lc_e(gh_lc_label(gh_lc_v($contract,'status'))); ?></td><td><?= gh_lc_money(gh_lc_v($contract,'monthly_price',0)); ?></td></tr><?php endforeach; ?></tbody></table></div>
                <p class="lc-note">Saldo deposit tercatat: <?= gh_lc_money(gh_lc_v($administration,'deposit_balance',0)); ?>. Deposit terpisah dari pembayaran sewa.</p>
                <div class="lc-table-wrap"><table><thead><tr><th>Dokumen administrasi</th><th>Status</th></tr></thead><tbody><?php foreach(gh_lc_v($administration,'documents',array()) as $document): ?><tr><td><?= gh_lc_e(gh_lc_v($document,'document_title')); ?></td><td><?= gh_lc_e(gh_lc_label(gh_lc_v($document,'status'))); ?></td></tr><?php endforeach; ?></tbody></table></div>
            </details>
            <details id="komplain"><summary>Komplain penghuni</summary>
                <?php foreach(array('portal_success','portal_error') as $flashKey): if($message=$this->session->flashdata($flashKey)): ?><p role="<?= $flashKey==='portal_error'?'alert':'status'; ?>"><?= gh_lc_e($message); ?></p><?php endif; endforeach; ?>
                <?php if(gh_lc_v($billingContext,'stay_status')==='active'): ?><form method="post" action="<?= base_url('resident/complaints'); ?>" data-lc-form><div class="lc-fields"><label>Kategori<select name="category"><option value="facility">Fasilitas</option><option value="noise">Kebisingan</option><option value="billing">Tagihan</option><option value="security">Keamanan</option><option value="cleanliness">Kebersihan</option><option value="other">Lainnya</option></select></label><label>Judul komplain<input name="complaint_title" required maxlength="180"></label><label class="lc-full">Detail<textarea name="complaint_detail" rows="3" maxlength="3000"></textarea></label></div><button type="submit">Kirim komplain</button></form><?php endif; ?>
                <div class="lc-table-wrap"><table><thead><tr><th>Tiket</th><th>Komplain</th><th>Status</th></tr></thead><tbody><?php foreach(gh_lc_v($administration,'complaints',array()) as $complaint): ?><tr><td><?= gh_lc_e(gh_lc_v($complaint,'ticket_no')); ?></td><td><?= gh_lc_e(gh_lc_v($complaint,'complaint_title')); ?></td><td><?= gh_lc_e(gh_lc_label(gh_lc_v($complaint,'status'))); ?></td></tr><?php endforeach; ?><?php if(empty($administration['complaints'])): ?><tr><td colspan="3">Belum ada komplain.</td></tr><?php endif; ?></tbody></table></div>
            </details>
        <?php endif; ?>
    <?php endif; ?>
</section>
