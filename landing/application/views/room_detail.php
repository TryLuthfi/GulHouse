<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= html_escape($room['name']); ?> di GUL HOUSE, kamar siap huni dengan fasilitas terawat dan booking mudah.">
    <title><?= html_escape($title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/landing/css/landing.css'); ?>">
</head>
<body class="detail-page">
    <header class="site-header" id="top">
        <a class="brand" href="<?= base_url(); ?>" aria-label="GUL HOUSE">
            <span class="brand-mark">GH</span>
            <span>GUL HOUSE</span>
        </a>
        <nav class="nav-links" aria-label="Navigasi utama">
            <a href="<?= base_url('#rooms'); ?>">Kamar</a>
            <a href="<?= base_url('#facilities'); ?>">Fasilitas</a>
            <a href="#costs">Biaya</a>
            <a href="<?= base_url('#booking'); ?>">Booking</a>
            <a href="<?= base_url('#contact'); ?>">Kontak</a>
        </nav>
        <a class="header-action" href="<?= base_url('#booking'); ?>">Cek Kamar</a>
    </header>

    <div class="detail-sticky-bar" data-detail-sticky aria-hidden="true">
        <div>
            <span><?= html_escape($room['code']); ?></span>
            <strong><?= html_escape($room['name']); ?></strong>
        </div>
        <div>
            <span><?= (int) $room['available_count']; ?> tersedia</span>
            <strong><?= html_escape($room['price_label']); ?></strong>
            <a href="<?= base_url('#booking'); ?>">Booking</a>
        </div>
    </div>

    <main>
        <section class="detail-hero">
            <div class="detail-title reveal">
                <a class="back-link" href="<?= base_url(); ?>">Kembali ke daftar kamar</a>
                <p class="eyebrow"><?= html_escape($room['code']); ?></p>
                <h1><?= html_escape($room['name']); ?></h1>
                <p><?= html_escape($room['public_description']); ?></p>
            </div>
            <aside class="detail-booking-card reveal delay-1">
                <span><?= (int) $room['available_count']; ?> tersedia dari <?= (int) $room['total_rooms']; ?> kamar</span>
                <strong><?= html_escape($room['price_label']); ?></strong>
                <p>Estimasi deposit Rp <?= number_format((int) $room['deposit_estimate'], 0, ',', '.'); ?></p>
                <a class="btn btn-primary glow-button" href="<?= base_url('#booking'); ?>">Booking Survey</a>
            </aside>
        </section>

        <section class="detail-gallery reveal" data-gallery>
            <button type="button" class="gallery-main" data-gallery-open="0">
                <img src="<?= html_escape($gallery[0]['src']); ?>" alt="<?= html_escape($gallery[0]['title']); ?>">
                <span>Lihat semua foto (<?= count($gallery); ?>)</span>
            </button>
            <div class="gallery-side">
                <?php foreach (array_slice($gallery, 1, 4) as $index => $photo): ?>
                    <button type="button" data-gallery-open="<?= $index + 1; ?>">
                        <img src="<?= html_escape($photo['src']); ?>" alt="<?= html_escape($photo['title']); ?>">
                    </button>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section detail-content">
            <article class="detail-copy reveal">
                <p class="eyebrow">Detail tipe kamar</p>
                <h2><?= html_escape($room['type_name']); ?> di <?= html_escape($room['code']); ?></h2>
                <p><?= html_escape($room['type_description']); ?></p>
                <div class="detail-points">
                    <div><strong>Tersedia</strong><span><?= (int) $room['available_count']; ?> kamar</span></div>
                    <div><strong>Periode</strong><span>Sewa bulanan</span></div>
                    <div><strong>Survey</strong><span>By appointment</span></div>
                </div>
            </article>

            <aside class="facility-list reveal delay-1">
                <h3>Fasilitas kamar</h3>
                <ul>
                    <?php foreach ($room['facilities'] as $facility): ?>
                        <li><?= html_escape($facility); ?></li>
                    <?php endforeach; ?>
                </ul>
            </aside>
        </section>

        <section class="detail-trust-strip reveal" aria-label="Ringkasan detail">
            <div><strong>Survey</strong><span>By appointment</span></div>
            <div><strong>Kontrak</strong><span>Sewa bulanan</span></div>
            <div><strong>Deposit</strong><span>Tercatat</span></div>
            <div><strong>Maintenance</strong><span>Bisa dilaporkan</span></div>
        </section>

        <section class="section costs-section" id="costs">
            <div class="section-copy reveal">
                <p class="eyebrow">Estimasi biaya</p>
                <h2>Siapkan biaya masuk dengan tenang.</h2>
            </div>
            <div class="cost-grid detail-cost-grid">
                <div class="cost-card reveal">
                    <span>Sewa bulan pertama</span>
                    <strong><?= html_escape($room['price_label']); ?></strong>
                </div>
                <div class="cost-card reveal delay-1">
                    <span>Deposit</span>
                    <strong>Rp <?= number_format((int) $room['deposit_estimate'], 0, ',', '.'); ?></strong>
                </div>
                <div class="cost-card reveal delay-2">
                    <span>Survey</span>
                    <strong>By appointment</strong>
                </div>
            </div>
        </section>

        <section class="section faq-section">
            <div class="section-copy reveal">
                <p class="eyebrow">Informasi</p>
                <h2>Sebelum survey.</h2>
            </div>
            <div class="accordion reveal delay-1" data-accordion>
                <div class="accordion-item is-open">
                    <button type="button" class="accordion-trigger" aria-expanded="true">
                        Apa saja yang dicek saat survey?
                        <span></span>
                    </button>
                    <div class="accordion-panel">
                        <p>Calon penghuni bisa melihat kondisi kamar, fasilitas, akses, aturan tinggal, dan estimasi biaya awal termasuk deposit.</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <button type="button" class="accordion-trigger" aria-expanded="false">
                        Apakah foto akan memakai foto asli per tipe?
                        <span></span>
                    </button>
                    <div class="accordion-panel">
                        <p>Struktur galeri sudah siap. Foto contoh ini bisa diganti dari upload master per gedung, tipe, atau kamar saat modul admin dibuat.</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <button type="button" class="accordion-trigger" aria-expanded="false">
                        Bagaimana cara booking tipe ini?
                        <span></span>
                    </button>
                    <div class="accordion-panel">
                        <p>Tekan tombol Booking Survey, isi nama dan WhatsApp, lalu pengelola akan menghubungi untuk konfirmasi jadwal.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="room-carousel-section reveal">
            <div class="carousel-heading">
                <div>
                    <p class="eyebrow">Tipe lain</p>
                    <h2>Pilihan serupa.</h2>
                </div>
            </div>
            <div class="room-carousel">
                <?php foreach ($similar_rooms as $index => $similar): ?>
                    <article class="room-card">
                        <div class="room-photo photo-<?= ($index % 3) + 1; ?>"></div>
                        <div class="room-body">
                            <span><?= html_escape($similar['code']); ?></span>
                            <h3><?= html_escape($similar['name']); ?></h3>
                            <p><?= html_escape($similar['type_name']); ?> mulai Rp <?= number_format((int) $similar['price'], 0, ',', '.'); ?>/bulan</p>
                            <a href="<?= base_url('rooms/' . html_escape($similar['slug'])); ?>">Lihat detail</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <div class="photo-modal" data-photo-modal aria-hidden="true">
        <div class="photo-modal-backdrop" data-photo-close></div>
        <div class="photo-modal-shell" role="dialog" aria-modal="true" aria-label="Preview foto kamar">
            <div class="photo-modal-top">
                <strong data-photo-category>Foto Kamar</strong>
                <button type="button" data-photo-close aria-label="Tutup preview">x</button>
            </div>
            <div class="photo-stage">
                <button type="button" class="photo-nav prev" data-photo-prev aria-label="Foto sebelumnya">&lsaquo;</button>
                <figure>
                    <img src="" alt="" data-photo-main>
                    <figcaption>
                        <span data-photo-title></span>
                        <strong data-photo-count></strong>
                    </figcaption>
                </figure>
                <button type="button" class="photo-nav next" data-photo-next aria-label="Foto berikutnya">&rsaquo;</button>
            </div>
            <div class="photo-tabs" data-photo-tabs></div>
            <div class="photo-thumbs" data-photo-thumbs></div>
        </div>
    </div>

    <div class="sticky-cta">
        <a href="https://wa.me/6280000000000" target="_blank" rel="noopener">WhatsApp</a>
        <a href="<?= base_url('#booking'); ?>">Booking</a>
    </div>

    <script>
        window.GH_ROOM_GALLERY = <?= json_encode($gallery); ?>;
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.12.2/lottie.min.js"></script>
    <script src="<?= base_url('assets/landing/js/landing.js'); ?>"></script>
</body>
</html>
