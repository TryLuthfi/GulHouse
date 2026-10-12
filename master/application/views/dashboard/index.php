<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    function gh_dash_money($value) {
        return 'Rp ' . number_format((int) $value, 0, ',', '.');
    }
    $collectionRate = min(100, max(0, (int) $summary['collection_rate']));
    $occupancyRate = min(100, max(0, (int) $summary['occupancy_rate']));
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
    <link rel="stylesheet" href="<?= base_url('assets/master/css/master.css?v=20261001-partial-metric'); ?>">
</head>
<body>
    <aside class="sidebar">
        <a class="brand" href="<?= base_url('dashboard'); ?>"><span>GH</span><strong>GUL HOUSE</strong></a>
        <nav>
            <a class="is-active" href="<?= base_url('dashboard'); ?>">Dashboard</a>
            <a href="<?= base_url('properties'); ?>">Properti</a>
            <a href="<?= base_url('room-types'); ?>">Tipe Kamar</a>
            <a href="<?= base_url('rooms'); ?>">Kamar</a>
            <a href="<?= base_url('tenants'); ?>">Penghuni</a>
            <a href="<?= base_url('payments'); ?>">Pembayaran</a>
            <a href="<?= base_url('photos'); ?>">Foto</a>
            <a href="#">Booking</a>
        </nav>
    </aside>

    <main class="main-shell dashboard-shell">
        <header class="topbar dashboard-topbar">
            <div class="dashboard-title"><p>Web Master</p><h1>Dashboard Operasional GulHouse</h1></div>
            <label class="dashboard-search">
                <span>Search</span>
                <input type="search" placeholder="Cari kamar, pembayaran, penghuni" aria-label="Cari dashboard">
            </label>
            <div class="dashboard-actions">
                <button type="button" class="dashboard-icon-button">ID</button>
                <button type="button" class="dashboard-icon-button">Mail</button>
                <button type="button" class="dashboard-icon-button">Bell</button>
                <div class="admin-chip"><span><?= html_escape($admin_name); ?></span><a href="<?= base_url('logout'); ?>">Logout</a></div>
            </div>
        </header>

        <section class="dashboard-filterbar">
            <div><span>Periode aktif</span><strong><?= html_escape($summary['payment_period']); ?></strong></div>
            <div><span>Properti</span><strong><?= number_format((int) $summary['properties'], 0, ',', '.'); ?> gedung</strong></div>
            <div><span>Tipe kamar</span><strong><?= number_format((int) $summary['room_types'], 0, ',', '.'); ?> tipe</strong></div>
            <div><span>Import issue</span><strong><?= number_format((int) $summary['import_issues'], 0, ',', '.'); ?> data</strong></div>
        </section>

        <section class="dashboard-kpi-grid">
            <article><span>Total kamar</span><strong><?= number_format((int) $summary['rooms'], 0, ',', '.'); ?></strong><p><?= (int) $summary['available']; ?> available, <?= (int) $summary['occupied']; ?> occupied</p></article>
            <article><span>Occupancy</span><strong><?= $occupancyRate; ?>%</strong><div class="mini-progress"><i style="width: <?= $occupancyRate; ?>%"></i></div></article>
            <article><span>Collection <?= html_escape($summary['payment_period']); ?></span><strong><?= $collectionRate; ?>%</strong><p><?= gh_dash_money($summary['latest_bill_paid']); ?> / <?= gh_dash_money($summary['latest_bill_total']); ?></p></article>
            <article><span>Outstanding</span><strong><?= gh_dash_money($summary['outstanding_amount']); ?></strong><p><?= number_format((int) $summary['unpaid_bills'], 0, ',', '.'); ?> bill belum lunas</p></article>
            <article><span>Potensi bulanan</span><strong><?= gh_dash_money($summary['monthly_potential']); ?></strong><p>Data master kamar aktif</p></article>
            <article><span>Total payment</span><strong><?= gh_dash_money($summary['payment_total_all']); ?></strong><p><?= number_format((int) $summary['payment_count'], 0, ',', '.'); ?> transaksi periode aktif</p></article>
        </section>

        <section class="dashboard-chart-grid">
            <article class="panel chart-panel wide">
                <div class="panel-head"><div><p>Payment</p><h2>Tagihan, Terbayar, Outstanding Per Periode</h2></div><a class="mini-link" href="<?= base_url('payments'); ?>">Pembayaran</a></div>
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
    <script src="<?= base_url('assets/master/js/master.js?v=20261001-partial-metric'); ?>"></script>
</body>
</html>
