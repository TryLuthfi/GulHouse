<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/master/css/master.css'); ?>">
</head>
<body>
    <aside class="sidebar">
        <a class="brand" href="<?= base_url('dashboard'); ?>">
            <span>GH</span>
            <strong>GUL HOUSE</strong>
        </a>
        <nav>
            <a class="is-active" href="<?= base_url('dashboard'); ?>">Dashboard</a>
            <a href="<?= base_url('properties'); ?>">Properti</a>
            <a href="<?= base_url('room-types'); ?>">Tipe Kamar</a>
            <a href="<?= base_url('rooms'); ?>">Kamar</a>
            <a href="<?= base_url('tenants'); ?>">Penghuni</a>
            <a href="#">Booking</a>
        </nav>
    </aside>

    <main class="main-shell">
        <header class="topbar">
            <div>
                <p>Web Master</p>
                <h1>Dashboard Operasional</h1>
            </div>
            <div class="admin-chip">
                <span><?= html_escape($admin_name); ?></span>
                <a href="<?= base_url('logout'); ?>">Logout</a>
            </div>
        </header>

        <section class="metric-grid">
            <article>
                <span>Properti</span>
                <strong><?= number_format((int) $summary['properties'], 0, ',', '.'); ?></strong>
            </article>
            <article>
                <span>Total Kamar</span>
                <strong><?= number_format((int) $summary['rooms'], 0, ',', '.'); ?></strong>
            </article>
            <article>
                <span>Kamar Public Tersedia</span>
                <strong><?= number_format((int) $summary['available'], 0, ',', '.'); ?></strong>
            </article>
            <article>
                <span>Booking Baru</span>
                <strong><?= number_format((int) $summary['bookings'], 0, ',', '.'); ?></strong>
            </article>
        </section>

        <section class="chart-grid">
            <article class="panel chart-panel">
                <div class="panel-head">
                    <div>
                        <p>Revenue</p>
                        <h2>Potensi Bulanan Per Gedung</h2>
                    </div>
                </div>
                <canvas id="revenueByPropertyChart" height="120"></canvas>
            </article>

            <article class="panel chart-panel">
                <div class="panel-head">
                    <div>
                        <p>Kamar</p>
                        <h2>Status Kamar</h2>
                    </div>
                </div>
                <canvas id="roomStatusChart" height="120"></canvas>
            </article>
        </section>

        <section class="panel chart-panel">
            <div class="panel-head">
                <div>
                    <p>Revenue</p>
                    <h2>Potensi Bulanan Per Gedung dan Tipe</h2>
                </div>
            </div>
            <canvas id="revenueByTypeChart" height="110"></canvas>
        </section>

        <section class="panel-grid">
            <article class="panel">
                <div class="panel-head">
                    <div>
                        <p>Properti</p>
                        <h2>Ringkasan Gedung</h2>
                    </div>
                    <strong>Rp <?= number_format((int) $summary['monthly_potential'], 0, ',', '.'); ?></strong>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Gedung</th>
                                <th>Tipe</th>
                                <th>Total</th>
                                <th>Tersedia</th>
                                <th>Potensi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($property_rows as $row): ?>
                                <tr>
                                    <td><strong><?= html_escape($row['code']); ?></strong><span><?= html_escape($row['name']); ?></span></td>
                                    <td><?= html_escape(ucfirst($row['property_type'])); ?></td>
                                    <td><?= (int) $row['total_rooms']; ?></td>
                                    <td><?= (int) $row['available_rooms']; ?></td>
                                    <td>Rp <?= number_format((int) $row['monthly_potential'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ( ! $property_rows): ?>
                                <tr><td colspan="5">Data properti belum tersedia.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="panel">
                <div class="panel-head">
                    <div>
                        <p>Tipe Kamar</p>
                        <h2>Harga Per Gedung</h2>
                    </div>
                </div>
                <div class="type-list">
                    <?php foreach ($room_type_rows as $row): ?>
                        <div>
                            <span><?= html_escape($row['property_code']); ?></span>
                            <strong><?= html_escape($row['type_name']); ?></strong>
                            <p><?= (int) $row['available_rooms']; ?> tersedia dari <?= (int) $row['total_rooms']; ?> kamar</p>
                            <em>
                                Rp <?= number_format((int) $row['min_price'], 0, ',', '.'); ?>
                                <?php if ((int) $row['max_price'] > (int) $row['min_price']): ?>
                                    - Rp <?= number_format((int) $row['max_price'], 0, ',', '.'); ?>
                                <?php endif; ?>
                            </em>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <p>Booking</p>
                    <h2>Request Terbaru</h2>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>WhatsApp</th>
                            <th>Minat</th>
                            <th>Status</th>
                            <th>Masuk</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($booking_rows as $row): ?>
                            <tr>
                                <td><?= html_escape($row['fullname']); ?></td>
                                <td><?= html_escape($row['phone']); ?></td>
                                <td><?= html_escape($row['room_interest']); ?></td>
                                <td><?= html_escape($row['status']); ?></td>
                                <td><?= html_escape($row['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ( ! $booking_rows): ?>
                            <tr><td colspan="5">Belum ada request booking baru.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <script>
        window.GH_DASHBOARD_CHARTS = <?= json_encode($charts); ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?= base_url('assets/master/js/master.js'); ?>"></script>
</body>
</html>
