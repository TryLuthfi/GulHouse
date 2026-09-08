<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="GUL HOUSE, hunian kos premium siap huni dengan kamar nyaman, fasilitas terawat, dan proses booking mudah.">
    <title><?= html_escape($title); ?></title>
    <link rel="preload" as="image" href="https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1500&q=76" fetchpriority="high">
    <link rel="preconnect" href="https://images.unsplash.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/landing/css/landing.css'); ?>">
</head>
<body>
    <header class="site-header" id="top">
        <div class="page-progress" data-page-progress aria-hidden="true"></div>
        <a class="brand" href="<?= base_url(); ?>">
            <span class="brand-mark">GH</span>
            <span>GUL HOUSE</span>
        </a>
        <button type="button" class="menu-toggle" data-menu-toggle aria-expanded="false" aria-label="Buka navigasi">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav class="nav-links" aria-label="Navigasi utama">
            <a href="#rooms">Kamar</a>
            <a href="#facilities">Fasilitas</a>
            <a href="#costs">Biaya</a>
            <a href="#location">Lokasi</a>
            <a href="#booking">Booking</a>
            <a href="#contact">Kontak</a>
        </nav>
        <a class="header-action" href="#booking">Cek Kamar</a>
    </header>

    <main>
        <section class="hero" data-hero-slider>
            <div class="hero-slides" aria-hidden="true">
                <div class="hero-slide is-active" style="--hero-image: url('https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1500&q=76');"></div>
                <div class="hero-slide" style="--hero-image: url('https://images.unsplash.com/photo-1616594039964-ae9021a400a0?auto=format&fit=crop&w=1500&q=76');"></div>
                <div class="hero-slide" style="--hero-image: url('https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1500&q=76');"></div>
            </div>
            <div class="hero-copy reveal">
                <p class="eyebrow">Kos premium siap huni</p>
                <h1>GUL HOUSE</h1>
                <p class="hero-lead">Hunian bulanan yang rapi, nyaman, dan mudah dikelola. Pilih kamar, jadwalkan survey, lalu masuk tanpa proses yang bertele-tele.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary glow-button" href="#booking">Booking Survey</a>
                    <a class="btn btn-ghost" href="https://wa.me/6280000000000" target="_blank" rel="noopener">WhatsApp</a>
                </div>
                <div class="hero-badges" role="list" aria-label="Ringkasan layanan">
                    <span role="listitem">Survey terjadwal</span>
                    <span role="listitem">Harga bulanan jelas</span>
                    <span role="listitem">Data kamar dari master</span>
                </div>
            </div>
            <div class="hero-panel reveal delay-1" role="group" aria-label="Ringkasan ketersediaan">
                <div class="lottie-row">
                    <span>Live Availability</span>
                    <span id="availability-lottie" class="lottie-badge" aria-hidden="true"></span>
                </div>
                <div>
                    <span>Total Kamar</span>
                    <strong><?= number_format((int) $summary['total_rooms'], 0, ',', '.'); ?></strong>
                </div>
                <div>
                    <span>Kamar Kosong</span>
                    <strong><?= number_format((int) $summary['available_rooms'], 0, ',', '.'); ?></strong>
                </div>
                <div>
                    <span>Mulai Dari</span>
                    <strong>Rp <?= number_format((int) $summary['starting_price'], 0, ',', '.'); ?></strong>
                </div>
            </div>
            <div class="hero-slider-ui reveal delay-2">
                <div class="hero-progress" data-hero-progress><span></span></div>
                <div class="hero-dots" data-hero-dots role="group" aria-label="Foto utama"></div>
            </div>
        </section>

        <section class="trust-strip" aria-label="Keunggulan GUL HOUSE">
            <div class="reveal">
                <span>01</span>
                <strong>Foto & tipe jelas</strong>
                <p>Calon penghuni melihat pilihan berdasarkan gedung dan tipe kamar, bukan nomor internal.</p>
            </div>
            <div class="reveal delay-1">
                <span>02</span>
                <strong>Survey cepat</strong>
                <p>Form booking dibuat ringkas agar admin bisa langsung follow up jadwal survey.</p>
            </div>
            <div class="reveal delay-2">
                <span>03</span>
                <strong>Harga transparan</strong>
                <p>Range harga dan estimasi biaya awal tampil sebelum calon penghuni bertanya.</p>
            </div>
        </section>

        <section class="quick-stats" aria-label="Ringkasan properti">
            <div class="stat-item reveal">
                <strong><?= number_format((int) $summary['occupancy_rate'], 0, ',', '.'); ?>%</strong>
                <span>Occupancy</span>
            </div>
            <div class="stat-item reveal delay-1">
                <strong><?= count($property_stats); ?></strong>
                <span>Lokasi/Properti</span>
            </div>
            <div class="stat-item reveal delay-2">
                <strong>24 jam</strong>
                <span>Akses penghuni</span>
            </div>
            <div class="stat-item reveal delay-3">
                <strong>Online</strong>
                <span>Booking & inquiry</span>
            </div>
        </section>

        <section class="section split" id="rooms">
            <div class="section-copy reveal">
                <p class="eyebrow">Ketersediaan</p>
                <h2>Pilih gedung dan tipe kamar yang paling pas.</h2>
                <p>Landing page menampilkan ringkasan per gedung dan jenis kamar. Nomor kamar disimpan untuk kebutuhan master agar calon penghuni cukup memilih tipe yang diinginkan.</p>
            </div>
            <div class="type-grid">
                <?php foreach ($room_types as $index => $type): ?>
                    <article class="type-card reveal delay-<?= ($index % 4); ?>">
                        <span><?= html_escape($type['name']); ?></span>
                        <strong>Rp <?= number_format((int) $type['price_from'], 0, ',', '.'); ?></strong>
                        <p><?= (int) $type['available_count']; ?> tersedia dari <?= (int) $type['total_rooms']; ?> kamar</p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section availability-insight">
            <div class="section-copy reveal">
                <p class="eyebrow">Snapshot</p>
                <h2>Stok kamar kebaca dalam sekali lihat.</h2>
                <p>Calon penghuni bisa memilih dari tipe tersedia, lalu lanjut ke detail tanpa melihat nomor kamar yang dipakai untuk operasional master.</p>
            </div>
            <div class="insight-panel reveal delay-1">
                <?php foreach (array_slice($property_stats, 0, 4) as $index => $stat): ?>
                    <?php
                        $totalRooms = max(1, (int) $stat['total_rooms']);
                        $availableRooms = (int) $stat['available_count'];
                        $availableRate = min(100, max(0, round(($availableRooms / $totalRooms) * 100)));
                    ?>
                    <div class="insight-row">
                        <div>
                            <strong><?= html_escape($stat['property_name']); ?></strong>
                            <span><?= $availableRooms; ?> tersedia dari <?= $totalRooms; ?> kamar</span>
                        </div>
                        <div class="insight-meter" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $availableRate; ?>" aria-label="<?= $availableRate; ?> persen tersedia">
                            <span style="width: <?= $availableRate; ?>%;"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section room-browser reveal" id="availability">
            <div class="browser-head">
                <div>
                    <p class="eyebrow">Jenis kamar</p>
                    <h2>Cek gedung dan tipe sebelum tanya admin.</h2>
                </div>
                <div class="room-filter" data-room-filter>
                    <button type="button" class="is-active" data-filter="all">Semua</button>
                    <button type="button" data-filter="available">Tersedia</button>
                    <button type="button" data-filter="GH 1">GH 1</button>
                    <button type="button" data-filter="GH 2">GH 2</button>
                    <button type="button" data-filter="Standart">Standart</button>
                    <button type="button" data-filter="Deluxe">Deluxe</button>
                    <button type="button" data-filter="VIP">VIP</button>
                </div>
            </div>
            <div class="room-list" data-room-list>
                <?php foreach ($all_rooms as $index => $room): ?>
                    <article class="compact-room reveal delay-<?= ($index % 4); ?>" data-room-card data-status="<?= html_escape($room['status']); ?>" data-property="<?= html_escape($room['code']); ?>" data-type="<?= html_escape($room['type_name']); ?>">
                        <div class="compact-photo photo-<?= ($index % 3) + 1; ?>"></div>
                        <div>
                            <span><?= (int) $room['available_count']; ?> tersedia dari <?= (int) $room['total_rooms']; ?> kamar</span>
                            <h3><?= html_escape($room['name']); ?></h3>
                            <p>
                                <?= html_escape($room['code']); ?> -
                                Rp <?= number_format((int) $room['price'], 0, ',', '.'); ?>
                                <?php if ((int) $room['max_price'] > (int) $room['price']): ?>
                                    - Rp <?= number_format((int) $room['max_price'], 0, ',', '.'); ?>
                                <?php endif; ?>/bulan
                            </p>
                        </div>
                        <div class="compact-actions">
                            <span class="status-pill status-<?= html_escape($room['status']); ?>"><?= html_escape(ucfirst($room['status'])); ?></span>
                            <a href="<?= base_url('rooms/' . html_escape($room['slug'])); ?>">Detail</a>
                        </div>
                    </article>
                <?php endforeach; ?>
                <div class="room-empty-state" data-room-empty aria-hidden="true">
                    <strong>Belum ada tipe yang cocok</strong>
                    <span>Coba pilih filter lain atau langsung booking survey agar admin bantu carikan opsi terdekat.</span>
                </div>
            </div>
        </section>

        <section class="room-carousel-section reveal" aria-label="Kamar tersedia">
            <div class="carousel-heading">
                <div>
                    <p class="eyebrow">Pilihan tersedia</p>
                    <h2>Gedung dan tipe yang bisa disurvey.</h2>
                </div>
                <div class="carousel-controls" role="group" aria-label="Kontrol carousel">
                    <button type="button" class="carousel-btn" data-carousel-prev aria-label="Kamar sebelumnya">&lsaquo;</button>
                    <button type="button" class="carousel-btn" data-carousel-next aria-label="Kamar berikutnya">&rsaquo;</button>
                </div>
            </div>
            <div class="room-carousel" data-carousel>
                <?php foreach ($featured_rooms as $index => $room): ?>
                    <article class="room-card" data-carousel-slide>
                        <div class="room-photo photo-<?= ($index % 3) + 1; ?>" data-parallax-card></div>
                        <div class="room-body">
                            <span><?= html_escape($room['code']); ?></span>
                            <h3><?= html_escape($room['name']); ?></h3>
                            <p><?= (int) $room['available_count']; ?> kamar tersedia, mulai Rp <?= number_format((int) $room['price'], 0, ',', '.'); ?>/bulan</p>
                            <div class="room-links">
                                <a href="<?= base_url('rooms/' . html_escape($room['slug'])); ?>">Lihat detail</a>
                                <a href="#booking">Booking survey</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="carousel-dots" data-carousel-dots role="group" aria-label="Posisi carousel"></div>
        </section>

        <section class="section photo-preview-section">
            <div class="section-copy reveal">
                <p class="eyebrow">Preview foto</p>
                <h2>Nuansa kamar, area santai, dan living space.</h2>
                <p>Bagian ini disiapkan untuk galeri foto asli GUL HOUSE. Untuk sekarang tampilannya memakai preview visual agar flow UI-nya sudah kebaca dari awal.</p>
            </div>
            <div class="preview-mosaic reveal delay-1">
                <figure class="preview-large">
                    <img src="https://images.unsplash.com/photo-1616594039964-ae9021a400a0?auto=format&fit=crop&w=980&q=74" alt="Preview kamar GUL HOUSE" loading="lazy" decoding="async">
                    <figcaption>Kamar siap huni</figcaption>
                </figure>
                <figure>
                    <img src="https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=620&q=74" alt="Preview ruang tidur GUL HOUSE" loading="lazy" decoding="async">
                    <figcaption>Ruang tidur</figcaption>
                </figure>
                <figure>
                    <img src="https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=620&q=74" alt="Preview fasilitas GUL HOUSE" loading="lazy" decoding="async">
                    <figcaption>Fasilitas kamar</figcaption>
                </figure>
                <figure>
                    <img src="https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=620&q=74" alt="Preview area bersama GUL HOUSE" loading="lazy" decoding="async">
                    <figcaption>Area bersama</figcaption>
                </figure>
            </div>
        </section>

        <section class="section facilities" id="facilities">
            <div class="section-copy reveal">
                <p class="eyebrow">Fasilitas</p>
                <h2>Yang perlu penghuni tahu sejak awal.</h2>
            </div>
            <div class="facility-grid">
                <?php foreach ($amenities as $index => $amenity): ?>
                    <div class="reveal delay-<?= ($index % 4); ?>">
                        <strong><?= html_escape($amenity['name']); ?></strong>
                        <span><?= html_escape($amenity['description']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="visual-band" data-parallax data-parallax-speed="0.18">
            <div class="visual-copy reveal">
                <p class="eyebrow">Daily living</p>
                <h2>Tenang buat tinggal, jelas buat bayar, gampang buat lapor.</h2>
            </div>
        </section>

        <section class="section costs-section" id="costs">
            <div class="section-copy reveal">
                <p class="eyebrow">Biaya awal</p>
                <h2>Transparan dari awal.</h2>
            </div>
            <div class="cost-grid">
                <?php foreach ($cost_items as $index => $item): ?>
                    <div class="cost-card reveal delay-<?= ($index % 4); ?>">
                        <span><?= html_escape($item['label']); ?></span>
                        <strong><?= html_escape($item['value']); ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="rules-panel reveal delay-1">
                <h3>Aturan singkat</h3>
                <ul>
                    <?php foreach ($house_rules as $rule): ?>
                        <li><?= html_escape($rule); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <section class="section testimonials">
            <div class="section-copy reveal">
                <p class="eyebrow">Penghuni</p>
                <h2>Cerita singkat dari yang sudah tinggal.</h2>
            </div>
            <div class="testimonial-grid">
                <?php foreach ($testimonials as $index => $testimonial): ?>
                    <figure class="testimonial-card reveal delay-<?= ($index % 3); ?>">
                        <blockquote><?= html_escape($testimonial['quote']); ?></blockquote>
                        <figcaption>
                            <strong><?= html_escape($testimonial['name']); ?></strong>
                            <span><?= html_escape($testimonial['room']); ?></span>
                        </figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section location-section" id="location">
            <div class="location-map reveal">
                <div class="map-pin" role="img" aria-label="Lokasi GUL HOUSE"></div>
            </div>
            <div class="location-copy reveal delay-1">
                <p class="eyebrow">Lokasi</p>
                <h2>Dibuat mudah untuk aktivitas harian.</h2>
                <div class="nearby-list">
                    <?php foreach ($nearby_places as $place): ?>
                        <div>
                            <strong><?= html_escape($place['place']); ?></strong>
                            <span><?= html_escape($place['distance']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section process-section">
            <div class="section-copy reveal">
                <p class="eyebrow">Alur booking</p>
                <h2>Dari lihat tipe sampai jadwal survey.</h2>
            </div>
            <div class="process-grid">
                <article class="process-card reveal">
                    <span>1</span>
                    <h3>Pilih tipe</h3>
                    <p>Lihat gedung, tipe kamar, jumlah tersedia, dan range harga bulanan.</p>
                </article>
                <article class="process-card reveal delay-1">
                    <span>2</span>
                    <h3>Kirim booking</h3>
                    <p>Isi nama, WhatsApp, minat kamar, dan rencana masuk.</p>
                </article>
                <article class="process-card reveal delay-2">
                    <span>3</span>
                    <h3>Survey</h3>
                    <p>Admin mengatur jadwal survey dan mengunci opsi kamar yang paling cocok.</p>
                </article>
                <article class="process-card reveal delay-3">
                    <span>4</span>
                    <h3>Masuk</h3>
                    <p>Konfirmasi pembayaran awal, serah terima, lalu kamar siap ditempati.</p>
                </article>
            </div>
        </section>

        <section class="section faq-section">
            <div class="section-copy reveal">
                <p class="eyebrow">FAQ</p>
                <h2>Pertanyaan yang sering muncul.</h2>
            </div>
            <div class="accordion reveal delay-1" data-accordion>
                <div class="accordion-item is-open">
                    <button type="button" class="accordion-trigger" aria-expanded="true">
                        Apakah bisa survey dulu sebelum booking?
                        <span></span>
                    </button>
                    <div class="accordion-panel">
                        <p>Bisa. Calon penghuni dapat mengisi form booking, lalu pengelola mengatur jadwal survey sesuai kamar yang tersedia.</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <button type="button" class="accordion-trigger" aria-expanded="false">
                        Apakah harga dan status kamar real-time?
                        <span></span>
                    </button>
                    <div class="accordion-panel">
                        <p>Landing page sudah disiapkan membaca data kamar dari MySQL. Nanti saat web master aktif, status publik bisa dikontrol dari dashboard.</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <button type="button" class="accordion-trigger" aria-expanded="false">
                        Apakah deposit dicatat?
                        <span></span>
                    </button>
                    <div class="accordion-panel">
                        <p>Ya. Alur deposit akan masuk ke modul master agar pembayaran masuk, potongan, dan pengembalian deposit bisa dilacak.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section booking-section" id="booking">
            <div class="booking-copy reveal">
                <p class="eyebrow">Booking</p>
                <h2>Jadwalkan survey kamar.</h2>
                <p>Isi data singkat. Tim GUL HOUSE dapat menindaklanjuti dari dashboard master setelah modul booking aktif.</p>
                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert success"><?= html_escape($this->session->flashdata('success')); ?></div>
                <?php elseif ($this->session->flashdata('warning')): ?>
                    <div class="alert warning"><?= html_escape($this->session->flashdata('warning')); ?></div>
                <?php endif; ?>
            </div>
            <form class="booking-form reveal delay-1" method="post" action="<?= base_url('booking'); ?>">
                <label>Nama Lengkap
                    <input type="text" name="fullname" required placeholder="Nama calon penghuni">
                </label>
                <label>No. WhatsApp
                    <input type="tel" name="phone" required placeholder="08xxxxxxxxxx">
                </label>
                <label>Minat Kamar
                    <select name="room_interest">
                        <option value="">Pilih kamar/tipe</option>
                        <?php foreach ($featured_rooms as $room): ?>
                            <option value="<?= html_escape($room['name']); ?>"><?= html_escape($room['name']); ?> - mulai Rp <?= number_format((int) $room['price'], 0, ',', '.'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Rencana Masuk
                    <input type="text" name="move_in_plan" placeholder="Contoh: akhir bulan ini">
                </label>
                <label class="full">Catatan
                    <textarea name="message" rows="4" placeholder="Tanyakan fasilitas, deposit, atau jadwal survey"></textarea>
                </label>
                <button type="submit" class="glow-button">Kirim Booking</button>
            </form>
        </section>

        <section class="section contact" id="contact">
            <div class="reveal">
                <p class="eyebrow">Kontak</p>
                <h2>Datang survey, pilih kamar, lalu masuk.</h2>
            </div>
            <div class="contact-grid">
                <?php foreach ($property_stats as $index => $stat): ?>
                    <div class="reveal delay-<?= ($index % 4); ?>">
                        <strong><?= html_escape($stat['property_name']); ?></strong>
                        <span><?= (int) $stat['available_count']; ?> kamar tersedia dari <?= (int) $stat['total_rooms']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <span>GUL HOUSE</span>
        <a href="#top">Kembali ke atas</a>
    </footer>
    <div class="sticky-cta">
        <a href="https://wa.me/6280000000000" target="_blank" rel="noopener">WhatsApp</a>
        <a href="#booking">Booking</a>
    </div>
    <script defer src="<?= base_url('assets/landing/js/landing.js'); ?>"></script>
</body>
</html>
