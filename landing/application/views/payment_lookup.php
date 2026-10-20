<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Cek status pembayaran penghuni GUL HOUSE.">
    <title><?= html_escape($title); ?></title>
    <link rel="preload" as="image" href="<?= base_url('index.php/media/logo'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/landing/css/landing.css?v=20261019-landing-ui-r2'); ?>">
</head>
<body class="payment-page">
    <header class="payment-header">
        <a class="brand" href="<?= base_url(); ?>">
            <span class="brand-mark"><img src="<?= base_url('index.php/media/logo'); ?>" alt="" width="38" height="38"></span>
            <span>GUL HOUSE</span>
        </a>
        <nav aria-label="Navigasi payment">
            <a href="<?= base_url(); ?>">Landing</a>
            <a href="<?= base_url('#booking'); ?>">Booking</a>
            <a href="<?= base_url('resident/access'); ?>">Portal Penghuni</a>
            <a href="<?= html_escape(gh_contact_url()); ?>" target="_blank" rel="noopener"><?= html_escape(gh_contact_label()); ?></a>
        </nav>
    </header>

    <main class="payment-shell">
        <section class="payment-lookup-grid">
            <div class="payment-hero-copy reveal is-visible">
                <p class="eyebrow">Payment</p>
                <h1>Cek status pembayaran bulanan.</h1>
                <p>Masukkan nomor HP penghuni dan 4 digit akhir NIK untuk melihat tagihan, riwayat pembayaran, dan status periode berjalan.</p>

                <div class="payment-feature-grid">
                    <div class="payment-feature">
                        <strong>Validasi singkat</strong>
                        <span>Cukup nomor HP terdaftar dan 4 digit akhir NIK penghuni.</span>
                    </div>
                    <div class="payment-feature">
                        <strong>Status berjalan</strong>
                        <span>Lihat apakah tagihan bulan ini sudah lunas, sebagian, atau belum dibuat.</span>
                    </div>
                    <div class="payment-feature">
                        <strong>Belum pakai DOKU</strong>
                        <span>Tombol bayar online akan ditambahkan setelah payment gateway siap.</span>
                    </div>
                </div>
            </div>

            <aside class="payment-card reveal is-visible">
                <p class="eyebrow">Akses penghuni</p>
                <h2>Masuk ke payment</h2>
                <p>Data harus sama dengan master penghuni yang sedang aktif.</p>

                <?php if ($this->session->flashdata('payment_warning')): ?>
                    <div class="payment-alert"><?= html_escape($this->session->flashdata('payment_warning')); ?></div>
                <?php endif; ?>

                <form class="payment-check-form" method="post" action="<?= base_url('payment/check'); ?>">
                    <label>No. HP Penghuni
                        <input type="tel" name="phone" required inputmode="tel" autocomplete="tel" placeholder="08xxxxxxxxxx">
                    </label>
                    <label>4 Digit Akhir NIK
                        <input type="password" name="nik_last4" required inputmode="numeric" maxlength="4" pattern="[0-9]{4}" autocomplete="off" placeholder="1234">
                    </label>
                    <button type="submit" class="glow-button">Cek Pembayaran</button>
                </form>
            </aside>
        </section>
    </main>
</body>
</html>
