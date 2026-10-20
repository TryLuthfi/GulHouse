<?php defined('BASEPATH') OR exit('No direct script access allowed');
    $change = is_array($room_change) ? $room_change : array();
    $choices = is_array($room_choices) ? $room_choices : array();
    $allowed = $canManage && ! empty($change['allowed']) && (string)$change['allowed'] !== '0';
    $current = isset($change['current']) ? $change['current'] : array();
    $changeBlockers = isset($change['blockers']) && is_array($change['blockers']) ? $change['blockers'] : array();
    if ($canManage && $change): ?>
<section class="lc-card" id="ubah-kamar">
    <p class="eyebrow">Sebelum check-in</p><h2>Ubah kamar yang disepakati</h2>
    <p class="muted">Gunakan untuk perubahan kamar sebelum penghuni masuk. Identitas penghuni dan pembayaran yang sudah tercatat tetap dipertahankan. Perpindahan setelah check-in memakai proses terpisah.</p>
    <?php if ($changeBlockers): ?><div class="lc-note"><strong>Perubahan kamar belum tersedia</strong><ul><?php foreach ($changeBlockers as $blocker): ?><li><?= gh_lc_e($blocker); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if ($allowed): ?>
        <dl class="lc-kv"><dt>Kamar saat ini</dt><dd><?= gh_lc_e(gh_lc_v($case,'display_room',gh_lc_v($case,'room_label','Kamar #'.gh_lc_v($case,'room_id')))); ?></dd><dt>Sewa bulanan saat ini</dt><dd><?= gh_lc_money(gh_lc_v($current,'monthly_price',0)); ?></dd><dt>Deposit saat ini</dt><dd><?= gh_lc_money(gh_lc_v($current,'deposit',0)); ?></dd><dt>Sewa telah dibayar</dt><dd><?= gh_lc_money(gh_lc_v($current,'rent_paid',0)); ?></dd><dt>Deposit telah dibayar</dt><dd><?= gh_lc_money(gh_lc_v($current,'deposit_paid',0)); ?></dd></dl>
        <form method="post" action="<?= $actionUrl; ?>" data-lc-form data-room-change data-confirm="Ubah kamar sebelum check-in sesuai persetujuan penghuni dan rincian selisih yang telah ditinjau?">
            <input type="hidden" name="action" value="change_room">
            <label>Kamar tujuan dari master<select name="room_id" required data-room-change-select><option value="">Pilih kamar tujuan</option>
                <?php foreach ($choices as $choice):
                    $isCurrent = !empty($choice['is_current']) || (int)$choice['id'] === (int)gh_lc_v($case,'room_id',0);
                    $isAvailable = !empty($choice['available']) && (string)$choice['available'] !== '0';
                    $overlap = !empty($choice['overlap']) && (string)$choice['overlap'] !== '0';
                    $disabled = $isCurrent || !$isAvailable || $overlap;
                    $label = trim(gh_lc_v($choice,'property_code','').' · '.gh_lc_v($choice,'room_label',gh_lc_v($choice,'room_code','Kamar #'.$choice['id'])));
                    $financial = isset($choice['financial']) && is_array($choice['financial']) ? $choice['financial'] : array();
                ?>
                    <option value="<?= (int)$choice['id']; ?>" <?= $disabled?'disabled':''; ?> data-monthly-price="<?= (int)gh_lc_v($choice,'monthly_price',0); ?>" data-deposit="<?= (int)gh_lc_v($choice,'deposit',0); ?>" data-financial="<?= gh_lc_e(json_encode($financial)); ?>"><?= gh_lc_e($label); ?><?= $isCurrent?' — kamar saat ini':($overlap?' — bentrok periode':(!$isAvailable?' — tidak tersedia':'')); ?></option>
                <?php endforeach; ?>
            </select></label>
            <div class="lc-note" data-room-change-preview hidden aria-live="polite">
                <h3>Tarif kamar tujuan &amp; selisih pembayaran</h3>
                <dl class="lc-kv"><dt>Sewa bulanan baru</dt><dd data-change-value="monthly_price"></dd><dt>Deposit baru</dt><dd data-change-value="deposit"></dd><dt>Selisih tarif sewa</dt><dd data-change-value="rent_delta"></dd><dt>Selisih tarif deposit</dt><dd data-change-value="deposit_delta"></dd><dt>Sisa sewa yang perlu dibayar</dt><dd data-change-value="rent_outstanding"></dd><dt>Kelebihan pembayaran sewa</dt><dd data-change-value="rent_credit"></dd><dt>Sisa deposit yang perlu dibayar</dt><dd data-change-value="deposit_outstanding"></dd><dt>Kelebihan pembayaran deposit</dt><dd data-change-value="deposit_credit"></dd></dl>
                <p class="lc-help">Selisih tarif positif berarti tarif naik, negatif berarti turun. Kelebihan pembayaran tetap tercatat dan belum berarti dana telah dikembalikan. Transfer dan penyelesaiannya dicatat terpisah. Server memeriksa ulang tarif serta ketersediaan saat disimpan.</p>
            </div>
            <p role="status" class="lc-help" data-room-change-status>Pilih kamar untuk melihat tarif master dan selisih pembayaran.</p>
            <label>Alasan perubahan kamar (10–1.000 karakter)<textarea name="reason" required minlength="10" maxlength="1000"></textarea></label>
            <label class="lc-check"><input type="checkbox" name="resident_confirmed" value="1" required><span>Penghuni menyetujui kamar tujuan, tarif, deposit dan rincian selisih pembayaran di atas.</span></label>
            <button type="submit" data-room-change-submit disabled>Simpan perubahan kamar</button>
        </form>
    <?php elseif (!$changeBlockers): ?><p class="lc-help">Kamar belum dapat diubah pada tahap ini.</p><?php endif; ?>
</section>
<?php endif; ?>
