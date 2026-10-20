<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if ($canInspect && empty($case['stay_id'])):
    $quote = isset($reservation_quote) && is_array($reservation_quote) ? $reservation_quote : array();
    $start = gh_lc_v($quote, 'start_date', gh_lc_v($case, 'planned_start', date('Y-m-d')));
    $defaultEnd = gh_lc_v($quote, 'end_date', '');
    if ($defaultEnd === '') {
        try {
            $startDate = new DateTimeImmutable($start);
            $nextMonth = $startDate->modify('first day of next month');
            $defaultEnd = $nextMonth->setDate((int)$nextMonth->format('Y'), (int)$nextMonth->format('n'), min((int)$startDate->format('j'), (int)$nextMonth->format('t')))->format('Y-m-d');
        } catch (Exception $e) { $defaultEnd = ''; }
    }
?>
<details open>
    <summary>Buat reservasi &amp; tagihan awal</summary>
    <p class="muted">Pilih tanggal mulai. Batas periode dihitung otomatis satu bulan kalender kemudian, menyesuaikan akhir bulan. Batas periode bukan checkout otomatis.</p>
    <form method="post" action="<?= $actionUrl; ?>" data-lc-form data-reserve-period>
        <input type="hidden" name="action" value="reserve">
        <div class="lc-fields">
            <label>Tanggal mulai<input type="date" name="start_date" value="<?= gh_lc_e($start); ?>" required data-reserve-start></label>
            <label>Batas periode sewa awal<input type="date" name="end_date" value="<?= gh_lc_e($defaultEnd); ?>" readonly aria-readonly="true" data-reserve-end></label>
            <label>Sewa bulanan<input value="<?= gh_lc_money(gh_lc_v($quote,'monthly_price',0)); ?>" readonly aria-readonly="true"></label>
            <label>Deposit<input value="<?= gh_lc_money(gh_lc_v($quote,'deposit',0)); ?>" readonly aria-readonly="true"></label>
        </div>
        <button type="submit" <?= empty($quote)?'disabled':''; ?>>Buat reservasi &amp; tagihan awal</button>
        <?php if (empty($quote)): ?><p class="lc-help">Tarif kamar belum tersedia. Muat ulang halaman atau periksa master kamar sebelum membuat reservasi.</p><?php endif; ?>
    </form>
</details>
<script src="<?= base_url('assets/master/js/reserve-period.js?v=20261020-1'); ?>" defer></script>
<?php endif; ?>
