<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    function gh_dash_money($value) {
        return 'Rp ' . number_format((int) $value, 0, ',', '.');
    }
    $collectionRate = (int) $summary['collection_rate'];
    $occupancyRate = min(100, max(0, (int) $summary['occupancy_rate']));
    $dashboardRole = $this->session->userdata('gh_admin_role');
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
    <?php $this->load->view('partials/sidebar', array('active_menu' => 'dashboard')); ?>

    <main class="main-shell dashboard-shell">
        <header class="topbar dashboard-topbar">
            <div class="dashboard-title"><p>Web Master</p><h1>Dashboard Operasional GulHouse</h1></div>
            <?php if (! in_array($dashboardRole, array('finance','maintenance'), TRUE)): ?>
            <form class="dashboard-search" method="get" action="<?= base_url('rooms'); ?>">
                <label><span>Cari kamar</span><input name="q" type="search" placeholder="Nomor atau nama kamar" aria-label="Cari kamar"></label>
                <button type="submit">Cari</button>
            </form>
            <?php endif; ?>
            <div class="dashboard-actions">
                <?php if ($dashboardRole !== 'finance'): ?><a class="dashboard-icon-button" href="<?= base_url('complaints'); ?>">Komplain</a><?php endif; ?>
                <?php if (! in_array($dashboardRole, array('finance','maintenance'), TRUE)): ?><a class="dashboard-icon-button" href="<?= base_url('notifications'); ?>">Reminder</a><?php endif; ?>
                <div class="admin-chip"><span><?= html_escape($admin_name); ?></span><?php $this->load->view('partials/logout'); ?></div>
            </div>
        </header>

        <section class="dashboard-filterbar">
            <form class="dashboard-period-form" method="get" action="<?= base_url('dashboard'); ?>">
                <label for="dashboard-period">Periode tagihan</label>
                <input id="dashboard-period" type="month" name="period" value="<?= html_escape($selected_period); ?>" required>
                <button type="submit">Tampilkan</button>
            </form>
            <div><span>Periode aktif</span><strong><?= html_escape($summary['payment_period']); ?></strong></div>
            <div><span>Periode data terbaru</span><strong><?= html_escape($summary['latest_data_period']); ?></strong></div>
            <div><span>Properti</span><strong><?= number_format((int) $summary['properties'], 0, ',', '.'); ?> gedung</strong></div>
            <div><span>Tipe kamar</span><strong><?= number_format((int) $summary['room_types'], 0, ',', '.'); ?> tipe</strong></div>
            <div><span>Import issue</span><strong><?= number_format((int) $summary['import_issues'], 0, ',', '.'); ?> data</strong></div>
        </section>

        <section class="dashboard-kpi-grid">
            <article><span>Total kamar</span><strong><?= number_format((int) $summary['rooms'], 0, ',', '.'); ?></strong><p><?= (int) $summary['available']; ?> available, <?= (int) $summary['occupied']; ?> occupied</p></article>
            <article><span>Occupancy saat ini</span><strong><?= $occupancyRate; ?>%</strong><p>Occupied / kamar bertarif siap operasi; reservasi terpisah</p><div class="mini-progress"><i style="width: <?= $occupancyRate; ?>%"></i></div></article>
            <article><span>Collection <?= html_escape($summary['payment_period']); ?></span><strong><?= $summary['latest_bill_total'] > 0 ? $collectionRate . '%' : 'n.a.'; ?></strong><p><?= gh_dash_money($summary['latest_bill_paid']); ?> / <?= gh_dash_money($summary['latest_bill_total']); ?></p></article>
            <article><span>Outstanding</span><strong><?= gh_dash_money($summary['outstanding_amount']); ?></strong><p><?= number_format((int) $summary['unpaid_bills'], 0, ',', '.'); ?> bill belum lunas</p></article>
            <article><span>Potensi bulanan</span><strong><?= gh_dash_money($summary['monthly_potential']); ?></strong><p>Data master kamar aktif</p></article>
            <article><span>Pembayaran sewa periode</span><strong><?= gh_dash_money($summary['payment_revenue']); ?></strong><p><?= number_format((int) $summary['payment_count'], 0, ',', '.'); ?> transaksi; deposit terpisah</p></article>
            <article><span>Kontrak expiring</span><strong><?= number_format((int) $summary['contracts_expiring'], 0, ',', '.'); ?></strong><p>Berakhir <= 30 hari</p></article>
            <article><span>Maintenance open</span><strong><?= number_format((int) $summary['maintenance_open'], 0, ',', '.'); ?></strong><p>Open / assigned / progress</p></article>
            <article><span>Komplain open</span><strong><?= number_format((int) $summary['complaints_open'], 0, ',', '.'); ?></strong><p>Open / progress</p></article>
            <article><span>Fasilitas open</span><strong><?= number_format((int) $summary['facility_open'], 0, ',', '.'); ?></strong><p>Checklist perlu follow-up</p></article>
            <article><span>Saldo deposit seluruh histori</span><strong><?= gh_dash_money($summary['deposit_balance']); ?></strong><p>Terpisah dari sewa<?= $summary['deposit_issues'] ? '; ' . (int) $summary['deposit_issues'] . ' saldo perlu rekonsiliasi' : ''; ?></p></article>
            <article><span>Biaya operasional</span><strong><?= gh_dash_money($summary['operational_expenses']); ?></strong><p>Dibayar pada <?= html_escape($selected_period); ?></p></article>
        </section>

        <section class="dashboard-chart-grid">
            <article class="panel chart-panel wide">
                <div class="panel-head"><div><p>Payment</p><h2>Perbandingan Tagihan, Terbayar, dan Sisa Per Periode</h2><span class="panel-hint">Tagihan = total bill kamar, Terbayar = payment masuk, Sisa = bill yang belum lunas.</span></div><a class="mini-link" href="<?= base_url('payments'); ?>">Pembayaran</a></div>
                <canvas id="collectionByPeriodChart" height="120"></canvas>
            </article>
            <article class="panel chart-panel"><div class="panel-head"><div><p>Kamar</p><h2>Status Kamar</h2></div></div><canvas id="roomStatusChart" height="130"></canvas></article>
            <article class="panel chart-panel"><div class="panel-head"><div><p>Gedung</p><h2>Occupancy Per Gedung</h2></div></div><canvas id="occupancyByPropertyChart" height="130"></canvas></article>
            <article class="panel chart-panel"><div class="panel-head"><div><p>Revenue</p><h2>Potensi Bulanan Gedung</h2></div></div><canvas id="revenueByPropertyChart" height="130"></canvas></article>
            <article class="panel chart-panel"><div class="panel-head"><div><p>Kamar</p><h2>Komposisi Tipe Kamar</h2></div></div><canvas id="roomMixByTypeChart" height="130"></canvas></article>
            <article class="panel chart-panel"><div class="panel-head"><div><p>Payment</p><h2>Status Bill Terbaru</h2></div></div><canvas id="billStatusChart" height="130"></canvas></article>
            <article class="panel chart-panel"><div class="panel-head"><div><p>Penghuni</p><h2>Status Stay</h2></div></div><canvas id="tenantStatusChart" height="130"></canvas></article>
            <article class="panel chart-panel"><div class="panel-head"><div><p>Booking</p><h2>Status Request</h2></div></div><canvas id="bookingStatusChart" height="130"></canvas></article>
            <article class="panel chart-panel"><div class="panel-head"><div><p>Import</p><h2>Issue Pembayaran</h2></div></div><canvas id="paymentIssueChart" height="130"></canvas></article>
        </section>

        <section class="panel chart-panel">
            <div class="panel-head"><div><p>Revenue</p><h2>Potensi Bulanan Per Gedung dan Tipe</h2></div></div>
            <canvas id="revenueByTypeChart" height="110"></canvas>
        </section>

        <section class="panel-grid">
            <article class="panel">
                <div class="panel-head"><div><p>Properti</p><h2>Ringkasan Gedung</h2></div><strong><?= gh_dash_money($summary['monthly_potential']); ?></strong></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Gedung</th><th>Tipe</th><th>Total</th><th>Tersedia</th><th>Potensi</th></tr></thead>
                        <tbody>
                            <?php foreach ($property_rows as $row): ?>
                                <tr><td><strong><?= html_escape($row['code']); ?></strong><span><?= html_escape($row['name']); ?></span></td><td><?= html_escape(ucfirst($row['property_type'])); ?></td><td><?= (int) $row['total_rooms']; ?></td><td><?= (int) $row['available_rooms']; ?></td><td><?= gh_dash_money($row['monthly_potential']); ?></td></tr>
                            <?php endforeach; ?>
                            <?php if ( ! $property_rows): ?><tr><td colspan="5">Data properti belum tersedia.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="panel">
                <div class="panel-head"><div><p>Tipe Kamar</p><h2>Harga Per Gedung</h2></div></div>
                <div class="type-list dense">
                    <?php foreach ($room_type_rows as $row): ?>
                        <div>
                            <span><?= html_escape($row['property_code']); ?></span>
                            <strong><?= html_escape($row['type_name']); ?></strong>
                            <p><?= (int) $row['available_rooms']; ?> tersedia dari <?= (int) $row['total_rooms']; ?> kamar</p>
                            <em><?= gh_dash_money($row['min_price']); ?><?php if ((int) $row['max_price'] > (int) $row['min_price']): ?> - <?= gh_dash_money($row['max_price']); ?><?php endif; ?></em>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="dashboard-table-grid">
            <article class="panel">
                <div class="panel-head"><div><p>Payment</p><h2>Transaksi Terbaru</h2></div><a class="mini-link" href="<?= base_url('payments'); ?>">Kelola</a></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Tanggal</th><th>Periode</th><th>Kamar</th><th>Penghuni</th><th>Nilai</th></tr></thead>
                        <tbody>
                            <?php foreach ($payment_rows as $row): ?>
                                <tr><td><?= html_escape($row['payment_date'] ?: $row['payment_date_text'] ?: '-'); ?></td><td><?= html_escape($row['period_label']); ?></td><td><strong><?= html_escape($row['room_label']); ?></strong><span><?= html_escape($row['property_code']); ?></span></td><td><?= html_escape($row['tenant_name_snapshot'] ?: '-'); ?></td><td><?= gh_dash_money($row['amount']); ?></td></tr>
                            <?php endforeach; ?>
                            <?php if ( ! $payment_rows): ?><tr><td colspan="5">Belum ada transaksi payment.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="panel">
                <div class="panel-head"><div><p>Booking</p><h2>Request Terbaru</h2></div></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Nama</th><th>WhatsApp</th><th>Minat</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($booking_rows as $row): ?>
                                <tr><td><?= html_escape($row['fullname']); ?></td><td><?= html_escape($row['phone']); ?></td><td><?= html_escape($row['room_interest']); ?></td><td><span class="badge <?= html_escape($row['status']); ?>"><?= html_escape($row['status']); ?></span></td></tr>
                            <?php endforeach; ?>
                            <?php if ( ! $booking_rows): ?><tr><td colspan="4">Belum ada request booking baru.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <?php if ($payment_issue_rows): ?>
            <section class="panel">
                <div class="panel-head"><div><p>Import</p><h2>Issue Pembayaran Yang Perlu Dirapikan</h2></div></div>
                <div class="type-list dense">
                    <?php foreach ($payment_issue_rows as $issue): ?>
                        <div><span><?= html_escape($issue['issue_type']); ?></span><strong><?= number_format((int) $issue['total'], 0, ',', '.'); ?> data</strong><p>Perlu validasi/mapping agar report pembayaran makin bersih.</p></div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
    <script>
        window.GH_DASHBOARD_CHARTS = <?= json_encode($charts); ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?= base_url('assets/master/js/master.js?v=20261020-admin-ui-1'); ?>"></script>
</body>
</html>
