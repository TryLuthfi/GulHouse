<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    function gh_payment_money($value) {
        return 'Rp ' . number_format((int) $value, 0, ',', '.');
    }

    function gh_payment_label($status) {
        $labels = array(
            'paid' => 'Lunas',
            'partial' => 'Sebagian',
            'unpaid' => 'Belum Bayar',
            'overpaid' => 'Lebih Bayar',
            'reserved' => 'Reservasi',
            'internal' => 'Internal',
            'empty' => 'Kosong',
            'unknown' => 'Belum Dibuat',
        );

        return isset($labels[$status]) ? $labels[$status] : ucfirst((string) $status);
    }

    function gh_payment_mask_name($name) {
        $name = trim((string) $name);
        if ($name === '') {
            return 'Penghuni';
        }

        $parts = preg_split('/\s+/', $name);
        $first = $parts[0];
        $second = isset($parts[1]) ? ' ' . strtoupper(substr($parts[1], 0, 1)) . '.' : '';

        return $first . $second;
    }

    function gh_payment_date($date, $fallback = '-') {
        if (! $date || $date === '0000-00-00') {
            return $fallback;
        }

        return date('d M Y', strtotime($date));
    }

    $bill = $current_bill;
    $status = isset($bill['bill_status']) ? $bill['bill_status'] : 'unknown';
    $badgeClass = preg_replace('/[^a-z0-9_-]/', '', strtolower($status));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Status pembayaran penghuni GUL HOUSE.">
    <title><?= html_escape($title); ?></title>
    <link rel="preload" as="image" href="<?= base_url('index.php/media/logo'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/landing/css/landing.css?v=20260912-payment'); ?>">
</head>
<body class="payment-page">
    <header class="payment-header">
        <a class="brand" href="<?= base_url(); ?>">
            <span class="brand-mark"><img src="<?= base_url('index.php/media/logo'); ?>" alt="" width="38" height="38"></span>
            <span>GUL HOUSE</span>
        </a>
        <nav aria-label="Navigasi payment">
            <a href="<?= base_url(); ?>">Landing</a>
            <a href="<?= base_url('payment/logout'); ?>">Cek penghuni lain</a>
            <a href="https://wa.me/6280000000000" target="_blank" rel="noopener">WhatsApp</a>
        </nav>
    </header>

    <main class="payment-shell">
        <section class="payment-status-head">
            <div class="payment-status-title reveal is-visible">
                <p class="eyebrow">Payment penghuni</p>
                <h1><?= html_escape(gh_payment_mask_name($context['fullname'])); ?></h1>
                <p>
                    <?= html_escape($context['display_room']); ?>,
                    <?= html_escape($context['room_type']); ?>.
                    Status ini membaca data pembayaran dari master GUL HOUSE.
                </p>
            </div>
            <aside class="payment-status-box reveal is-visible">
                <span><?= html_escape($bill['period_label']); ?></span>
                <strong><?= html_escape(gh_payment_label($status)); ?></strong>
                <span class="payment-badge <?= html_escape($badgeClass); ?>"><?= html_escape(gh_payment_label($status)); ?></span>
                <p>Tagihan periode berjalan untuk kamar aktif.</p>
                <?php if (! empty($bill['is_draft'])): ?>
                    <small>Tagihan periode ini belum dibuat di master.</small>
                <?php endif; ?>
            </aside>
        </section>

        <section class="payment-summary-grid">
            <div class="payment-card payment-metric">
                <span>Tagihan</span>
                <strong><?= gh_payment_money($bill['base_price']); ?></strong>
            </div>
            <div class="payment-card payment-metric">
                <span>Sudah Dibayar</span>
                <strong><?= gh_payment_money($bill['paid_total']); ?></strong>
            </div>
            <div class="payment-card payment-metric">
                <span>Sisa</span>
                <strong><?= gh_payment_money($bill['remaining_amount']); ?></strong>
            </div>
            <div class="payment-card payment-metric">
                <span>Jatuh Tempo</span>
                <strong><?= html_escape(gh_payment_date($bill['due_date'], $bill['due_date_text'] ? $bill['due_date_text'] : '-')); ?></strong>
            </div>
        </section>

        <section class="payment-history-grid">
            <div class="payment-table-card">
                <div class="payment-table-head">
                    <h2>Riwayat tagihan</h2>
                    <span><?= count($bill_history); ?> periode</span>
                </div>
                <div class="payment-table-wrap">
                    <table class="payment-table">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>Status</th>
                                <th>Tagihan</th>
                                <th>Dibayar</th>
                                <th>Sisa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($bill_history): ?>
                                <?php foreach ($bill_history as $item): ?>
                                    <?php $itemStatus = preg_replace('/[^a-z0-9_-]/', '', strtolower($item['bill_status'])); ?>
                                    <tr>
                                        <td><?= html_escape($item['period_label']); ?></td>
                                        <td><span class="payment-badge <?= html_escape($itemStatus); ?>"><?= html_escape(gh_payment_label($item['bill_status'])); ?></span></td>
                                        <td><?= gh_payment_money($item['base_price']); ?></td>
                                        <td><?= gh_payment_money($item['paid_total']); ?></td>
                                        <td><?= gh_payment_money($item['remaining_amount']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">Belum ada riwayat tagihan untuk kamar ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <aside class="payment-table-card">
                <div class="payment-table-head">
                    <h2>Pembayaran periode ini</h2>
                </div>
                <div class="payment-table-wrap">
                    <table class="payment-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($payments): ?>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?= html_escape(gh_payment_date($payment['payment_date'], $payment['payment_date_text'] ? $payment['payment_date_text'] : '-')); ?></td>
                                        <td><?= gh_payment_money($payment['amount']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2">Belum ada pembayaran tercatat untuk periode ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="payment-note">
                    Pembayaran online via DOKU belum diaktifkan. Untuk koreksi status atau bukti transfer, hubungi admin GUL HOUSE melalui WhatsApp.
                </div>
            </aside>
        </section>
    </main>
</body>
</html>
