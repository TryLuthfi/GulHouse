<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    $booking = isset($booking) && is_array($booking) ? $booking : array();
    $plan = isset($plan) && is_array($plan) ? $plan : array();
    $bookingId = (int) (isset($booking['id']) ? $booking['id'] : 0);
    $isSuperAdmin = $this->session->userdata('gh_admin_role') === 'super_admin';
    $isDemo = ! empty($plan['is_demo']) && (string) $plan['is_demo'] !== '0';
    $blockers = isset($plan['blockers']) && is_array($plan['blockers']) ? $plan['blockers'] : array();
    $nodes = isset($plan['nodes']) && is_array($plan['nodes']) ? $plan['nodes'] : array();
    $files = isset($plan['files']) && is_array($plan['files']) ? $plan['files'] : array('count' => 0, 'items' => array());
    $phrase = isset($plan['confirmation_phrase']) ? (string) $plan['confirmation_phrase'] : '';
    $canPurge = $isSuperAdmin && $isDemo && ! empty($plan['can_purge']) && (string) $plan['can_purge'] !== '0'
        && empty($blockers) && $bookingId > 0 && $phrase !== '' && ! empty($plan['plan_hash']) && ! empty($plan['review_token']);
    $verification = isset($plan['verification']) && is_array($plan['verification']) ? $plan['verification'] : array();
    $canVerifyDemo = $isSuperAdmin && ! $isDemo && ! empty($plan['can_verify_demo'])
        && (string) $plan['can_verify_demo'] !== '0' && ! empty($verification['hash']) && ! empty($verification['token']);
    $verificationBlockers = isset($verification['blockers']) && is_array($verification['blockers']) ? $verification['blockers'] : array();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/master/css/master.css?v=20261020-admin-ui-1'); ?>">
<link rel="stylesheet" href="<?= base_url('assets/master/css/admin-guidelines.css?v=20261020-6'); ?>"><script src="<?= base_url('assets/master/js/admin-guidelines.js?v=20261020-2'); ?>" defer></script></head>
<body class="gh-admin-ui">
    <?php $this->load->view('partials/sidebar', array('active_menu' => 'booking')); ?>
    <main class="main-shell booking-retention-page">
        <header class="topbar">
            <div><p>Booking</p><h1>Arsip &amp; hapus booking #<?= $bookingId; ?></h1></div>
            <div class="admin-chip"><span><?= html_escape($admin_name); ?></span><?php $this->load->view('partials/logout'); ?></div>
        </header>
        <a class="mini-link" href="<?= base_url('booking?archive=all'); ?>">Kembali ke daftar booking</a>
        <?php if (! empty($error)): ?><div class="notice error" role="alert"><?= html_escape($error); ?></div><?php endif; ?>
        <?php if ($this->session->flashdata('error')): ?><div class="notice error" role="alert"><?= html_escape($this->session->flashdata('error')); ?></div><?php endif; ?>
        <?php if ($isSuperAdmin): ?>
            <section class="panel booking-retention-section">
                <div class="panel-head"><div><p>Booking yang ditinjau</p><h2><?= html_escape(isset($booking['fullname']) ? $booking['fullname'] : 'Booking #' . $bookingId); ?></h2></div><span class="badge"><?= ! empty($plan['is_archived']) ? 'Diarsipkan' : 'Aktif'; ?></span></div>
                <p class="panel-hint">Arsip mempertahankan data dan riwayat. Penghapusan permanen hanya tersedia untuk data demo yang asal-usul dan seluruh kaitannya telah diverifikasi server.</p>
                <dl class="booking-retention-facts">
                    <div><dt>ID booking</dt><dd>#<?= $bookingId; ?></dd></div>
                    <div><dt>Status booking</dt><dd><?= html_escape(isset($booking['status']) ? $booking['status'] : '-'); ?></dd></div>
                    <div><dt>Verifikasi data demo</dt><dd><?= $isDemo ? 'Terdaftar sebagai demo di server' : 'Tidak terverifikasi sebagai demo'; ?></dd></div>
                    <div><dt>Kelayakan hapus permanen</dt><dd><?= $canPurge ? 'Dapat ditinjau untuk penghapusan' : 'Tidak diizinkan'; ?></dd></div>
                </dl>
                <?php if (! $isDemo): ?><p class="notice">Nama atau catatan berisi kata demo bukan bukti. Tinjau seluruh data terkait di bawah sebelum memberikan verifikasi. Hubungan data yang tidak dapat dipastikan akan diblokir oleh server.</p><?php endif; ?>
            </section>

            <section class="panel booking-retention-section" aria-labelledby="retention-scope-title">
                <div class="panel-head"><div><p>Cakupan dari server</p><h2 id="retention-scope-title">Data terkait yang akan terdampak</h2></div><a class="mini-link" href="<?= base_url('booking/retention/' . $bookingId); ?>">Muat ulang pemeriksaan</a></div>
                <p class="panel-hint">Tinjau seluruh ID di bawah. Jika data berubah setelah pemeriksaan, muat ulang sebelum melanjutkan.</p>
                <div class="table-wrap">
                    <table><thead><tr><th>Jenis data</th><th>Jumlah</th><th>ID data</th></tr></thead><tbody>
                    <?php foreach ($nodes as $node): ?>
                        <tr><td><?= html_escape(isset($node['label']) ? $node['label'] : 'Data terkait'); ?></td><td><?= number_format((int) (isset($node['count']) ? $node['count'] : 0), 0, ',', '.'); ?></td><td class="booking-retention-ids"><?= ! empty($node['ids']) ? html_escape(implode(', ', array_map('intval', $node['ids']))) : '-'; ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (! $nodes): ?><tr><td colspan="3">Belum ada rincian cakupan dari server. Muat ulang pemeriksaan.</td></tr><?php endif; ?>
                    </tbody></table>
                </div>
                <details class="booking-retention-files"><summary>Lampiran terkait (<?= (int) (isset($files['count']) ? $files['count'] : 0); ?>)</summary>
                    <?php if (! empty($files['items'])): ?><ul><?php foreach ($files['items'] as $file): ?><li><?= html_escape(isset($file['name']) ? $file['name'] : 'Lampiran'); ?> · ID <?= (int) (isset($file['reference_id']) ? $file['reference_id'] : 0); ?></li><?php endforeach; ?></ul><?php else: ?><p>Tidak ada lampiran dalam cakupan pemeriksaan ini.</p><?php endif; ?>
                </details>
            </section>

            <?php if (! $isDemo): ?>
                <section class="panel booking-retention-section" aria-labelledby="verify-demo-title">
                    <h2 id="verify-demo-title">Verifikasi sebagai data demo</h2>
                    <p class="panel-hint">Khusus superadmin. Pastikan booking dan seluruh data terkait yang tercantum di atas adalah data uji, bukan data penghuni asli. Verifikasi ini tidak mengarsipkan atau menghapus data.</p>
                    <?php if ($canVerifyDemo && ! $verificationBlockers): ?>
                        <form class="manage-form" method="post" action="<?= base_url('booking/verify-demo/' . $bookingId); ?>" data-retention-submit data-verify-demo>
                            <input type="hidden" name="verification_hash" value="<?= html_escape($verification['hash']); ?>">
                            <input type="hidden" name="verification_token" value="<?= html_escape($verification['token']); ?>">
                            <label class="booking-demo-confirm"><input type="checkbox" name="confirmed_demo" value="1" required><span>Saya memastikan booking ini dan seluruh data terkait merupakan data demo, bukan data penghuni asli.</span></label>
                            <label>Alasan dan keterangan verifikasi (20–500 karakter)<textarea name="attestation" required minlength="20" maxlength="500" placeholder="Jelaskan mengapa booking dan data terkait ini merupakan data uji."></textarea></label>
                            <p class="panel-hint">Verifikasi berlaku untuk cakupan yang ditinjau saat ini. Muat ulang pemeriksaan jika data berubah atau sudah lewat 5 menit.</p>
                            <button type="submit">Verifikasi sebagai data demo</button>
                            <p class="panel-hint" role="status" data-retention-status></p>
                        </form>
                    <?php else: ?>
                        <p class="notice">Verifikasi demo belum tersedia untuk booking ini.</p>
                        <?php if ($verificationBlockers): ?><ul class="booking-retention-blockers"><?php foreach ($verificationBlockers as $blocker): ?><li><?= html_escape($blocker); ?></li><?php endforeach; ?></ul><?php endif; ?>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ($blockers): ?>
                <section class="panel booking-retention-section" aria-labelledby="retention-blockers-title"><h2 id="retention-blockers-title">Penghapusan diblokir</h2><ul class="booking-retention-blockers"><?php foreach ($blockers as $blocker): ?><li><?= html_escape($blocker); ?></li><?php endforeach; ?></ul><p class="panel-hint">Gunakan arsip untuk menyimpan booking tanpa menghapus riwayatnya.</p></section>
            <?php endif; ?>

            <section class="panel booking-retention-section booking-retention-danger" aria-labelledby="retention-delete-title">
                <h2 id="retention-delete-title">Penghapusan permanen data demo</h2>
                <p>Penghapusan tidak dapat dipulihkan melalui aplikasi. Tindakan ini hanya mencakup booking dan data terkait yang tercantum pada pemeriksaan server.</p>
                <?php if ($canPurge): ?>
                    <form class="manage-form" method="post" action="<?= base_url('booking/purge/' . $bookingId); ?>" data-retention-purge data-retention-submit data-confirmation-phrase="<?= html_escape($phrase); ?>">
                        <input type="hidden" name="plan_hash" value="<?= html_escape($plan['plan_hash']); ?>">
                        <input type="hidden" name="review_token" value="<?= html_escape($plan['review_token']); ?>">
                        <label>Alasan penghapusan (10–500 karakter)<textarea name="reason" required minlength="10" maxlength="500"></textarea></label>
                        <label>Ketik persis: <strong><?= html_escape($phrase); ?></strong><input type="text" name="confirmation" required autocomplete="off" spellcheck="false" data-retention-phrase aria-describedby="retention-confirm-help"></label>
                        <p class="panel-hint" id="retention-confirm-help">Konfirmasi hanya berlaku untuk booking #<?= $bookingId; ?> dan cakupan yang baru ditinjau. Pemeriksaan berlaku selama 5 menit; muat ulang jika kedaluwarsa.</p>
                        <button type="submit" class="danger-solid" data-retention-purge-button>Hapus permanen data demo #<?= $bookingId; ?></button>
                        <p class="panel-hint" role="status" data-retention-status></p>
                    </form>
                <?php else: ?>
                    <p class="notice">Penghapusan permanen tidak tersedia untuk booking ini. Tidak ada data yang dihapus.</p>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <div class="notice error" role="alert">Halaman tinjauan data terkait hanya dapat dibuka oleh superadmin.</div>
        <?php endif; ?>
    </main>
    <script src="<?= base_url('assets/master/js/master.js?v=20261020-admin-ui-1'); ?>"></script>
</body>
</html>
