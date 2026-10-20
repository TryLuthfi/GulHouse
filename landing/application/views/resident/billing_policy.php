<?php defined('BASEPATH') OR exit('No direct script access allowed');
    $policy = isset($billing_policy) && is_array($billing_policy) ? $billing_policy : array();
    $policyEnabled = !empty($policy['enabled']) && (string)$policy['enabled'] !== '0';
    $policyBills = isset($policy['bills']) && is_array($policy['bills']) ? $policy['bills'] : array();
    $feeBills = array_values(array_filter($policyBills, function($bill) { return isset($bill['penalty_outstanding']) && (int)$bill['penalty_outstanding'] > 0; }));
    $feePayments = isset($policy['fee_payments']) && is_array($policy['fee_payments']) ? $policy['fee_payments'] : array();
    $penaltyCaseId = isset($case['id']) ? (int)$case['id'] : 0;
    if ($policy): ?>
<section class="lc-card" id="kebijakan-tagihan">
    <p class="eyebrow">Siklus pembayaran bulanan</p><h2>Tenggat pembayaran &amp; denda</h2>
    <?php if (!$policyEnabled): ?>
        <p class="lc-help">Kebijakan pembayaran baru belum diaktifkan untuk masa tinggal ini. Ketentuan baru tidak diterapkan mundur pada tagihan lama.</p>
    <?php else: ?>
        <dl class="lc-kv"><dt>Tanggal dasar siklus</dt><dd><?= gh_lc_e(gh_lc_v($policy,'anchor_date')); ?></dd><dt>Siklus / jatuh tempo berikutnya</dt><dd><?= gh_lc_e(gh_lc_v($policy,'next_due_date')); ?></dd><dt>Deadline pelunasan (H-2)</dt><dd><strong><?= gh_lc_e(gh_lc_v($policy,'payment_deadline')); ?></strong></dd><dt>Denda terhitung</dt><dd><?= gh_lc_money(gh_lc_v($policy,'penalty_accrued',0)); ?></dd><dt>Denda telah dibayar</dt><dd><?= gh_lc_money(gh_lc_v($policy,'penalty_paid',0)); ?></dd><dt>Sisa denda</dt><dd><strong><?= gh_lc_money(gh_lc_v($policy,'penalty_outstanding',0)); ?></strong></dd></dl>
        <p class="lc-note">Pelunasan paling lambat H-2 sebelum jatuh tempo siklus. Dua hari pertama setelah jatuh tempo bebas denda; mulai H+3, denda Rp100.000 per hari (H+3 Rp100.000, H+4 Rp200.000). Tanggal siklus bukan checkout otomatis. Denda dihitung terpisah dari sewa dan deposit; nominal di halaman ini mengikuti perhitungan server.</p>
        <?php if (!empty($policy['takeout_review_required']) && (string)$policy['takeout_review_required'] !== '0'): ?><div class="lc-alert error" role="status"><strong>Perlu tindak lanjut pengelola — keterlambatan H+7 atau lebih.</strong> Pengelola perlu mengonfirmasi penanganan penghuni dan proses checkout. Status ini belum mengakhiri masa tinggal atau melepas kamar.</div><?php endif; ?>

        <?php if(!empty($policy['overdue_review']) && is_array($policy['overdue_review'])): $review=$policy['overdue_review']; ?>
            <div class="lc-note"><strong>Tindak lanjut tunggakan telah dicatat</strong><p>Ditinjau pada <?= gh_lc_e(gh_lc_v($review,'reviewed_at')); ?> untuk tagihan #<?= (int)gh_lc_v($review,'bill_id',0); ?>. Piutang sewa dan denda tetap tercatat.</p><p><?= !empty($review['actual_exit_date']) ? 'Keluar aktual dikonfirmasi pada '.gh_lc_e($review['actual_exit_date']).'.' : 'Belum merupakan konfirmasi keluar aktual. Pemeriksaan akhir dan proses checkout tetap diperlukan.'; ?></p></div>
        <?php endif; ?>
        <div class="lc-table-wrap"><table><thead><tr><th>Tagihan / jatuh tempo</th><th>Deadline H-2</th><th>Terlambat / hari denda</th><th>Denda terhitung</th><th>Denda dibayar / sisa</th><th>Tindak lanjut</th></tr></thead><tbody>
            <?php foreach ($policyBills as $bill): ?><tr><td>#<?= (int)gh_lc_v($bill,'bill_id',0); ?><br><?= gh_lc_e(gh_lc_v($bill,'due_date')); ?></td><td><?= gh_lc_e(gh_lc_v($bill,'payment_deadline')); ?></td><td><?= (int)gh_lc_v($bill,'late_days',0); ?> / <?= (int)gh_lc_v($bill,'penalty_days',0); ?> hari</td><td><?= gh_lc_money(gh_lc_v($bill,'penalty_accrued',0)); ?></td><td><?= gh_lc_money(gh_lc_v($bill,'penalty_paid',0)); ?> / <?= gh_lc_money(gh_lc_v($bill,'penalty_outstanding',0)); ?></td><td><?= !empty($bill['takeout_review_required']) && (string)$bill['takeout_review_required'] !== '0' ? 'Perlu tinjauan pengelola' : 'Belum perlu tinjauan H+7'; ?></td></tr><?php endforeach; ?>
            <?php if (!$policyBills): ?><tr><td colspan="6">Belum ada tagihan siklus yang diterbitkan.</td></tr><?php endif; ?>
        </tbody></table></div>
        <?php if ($feePayments): ?><h3 style="margin-top:20px">Riwayat pembayaran denda</h3><div class="lc-table-wrap"><table><thead><tr><th>Tanggal / tagihan</th><th>Nominal diterima</th><th>Referensi</th><th>Bukti</th></tr></thead><tbody><?php foreach($feePayments as $fee): ?><tr><td><?= gh_lc_e(gh_lc_v($fee,'payment_date')); ?> · #<?= (int)gh_lc_v($fee,'bill_id',0); ?></td><td><?= gh_lc_money(gh_lc_v($fee,'amount',0)); ?></td><td><?= gh_lc_e(gh_lc_v($fee,'reference_no')); ?></td><td><?php if(!empty($fee['proof_file_id'])): ?><a class="lc-btn secondary" href="<?= base_url('resident/file/'.(int)$fee['proof_file_id']); ?>" target="_blank" rel="noopener">Lihat bukti</a><?php else: ?>Tanpa lampiran<?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>

    <?php endif; ?>
</section>
<?php endif; ?>
