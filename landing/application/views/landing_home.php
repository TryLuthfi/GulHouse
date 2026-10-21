<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    $bookingError = (string) $this->session->flashdata('booking_error');
    $bookingErrorField = (string) $this->session->flashdata('booking_error_field');
    $bookingOld = $this->session->flashdata('booking_old');
    $bookingOld = is_array($bookingOld) ? $bookingOld : array();
    $bookingValues = array();
    foreach (array('fullname', 'phone', 'room_interest', 'move_in_plan', 'message') as $field) {
        $bookingValues[$field] = isset($bookingOld[$field]) && is_scalar($bookingOld[$field]) ? (string) $bookingOld[$field] : '';
    }
    if (! in_array($bookingErrorField, array('fullname', 'phone', 'room_interest', 'move_in_plan', 'message'), TRUE)) {
        $bookingErrorField = 'form';
    }
    $bookingErrorAttrs = function ($field) use ($bookingError, $bookingErrorField) {
        return $bookingError !== '' && $bookingErrorField === $field ? ' aria-invalid="true" aria-describedby="booking-error-message"' : '';
    };
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="GUL HOUSE, hunian kos premium siap huni dengan kamar nyaman, fasilitas terawat, dan proses booking mudah.">
    <title><?= html_escape($title); ?></title>
    <?php $heroSlides = isset($hero_slides) && $hero_slides ? $hero_slides : array(); ?>
    <?php if (! empty($heroSlides[0]['url'])): ?>
        <link rel="preload" as="image" href="<?= html_escape($heroSlides[0]['url']); ?>" fetchpriority="high">
    <?php endif; ?>
    <link rel="preload" as="image" href="<?= base_url('index.php/media/logo'); ?>">
    <link rel="preconnect" href="https://images.unsplash.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/landing/css/landing.css?v=20261021-photo-focus-preview-equal'); ?>">
</head>
<body>
    <header class="site-header" id="top">
        <div class="page-progress" data-page-progress aria-hidden="true"></div>
        <a class="brand" href="<?= base_url(); ?>">
            <span class="brand-mark"><img src="<?= base_url('index.php/media/logo'); ?>" alt="" width="38" height="38"></span>
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
            <a href="#booking" data-booking-open>Booking</a>
            <a href="<?= base_url('resident/portal'); ?>">Pembayaran</a>
            <a href="<?= base_url('resident/access'); ?>">Penghuni</a>
            <a href="#contact">Kontak</a>
        </nav>
        <a class="header-action" href="#booking" data-booking-open>Survey Kamar</a>
    </header>

    <main>
        <section class="hero" data-hero-slider>
            <div class="hero-slides" aria-hidden="true">
                <?php foreach ($heroSlides as $index => $slide): ?>
                    <div class="hero-slide <?= $index === 0 ? 'is-active' : ''; ?>" style="--hero-image: url('<?= html_escape($slide['url']); ?>');"></div>
                <?php endforeach; ?>
            </div>
            <div class="hero-copy reveal">
                <p class="eyebrow">Kos premium siap huni</p>
                <h1>GUL HOUSE</h1>
                <p class="hero-lead">Hunian bulanan yang rapi dan nyaman. Temukan kamar sesuai kebutuhan, lihat fasilitasnya, lalu jadwalkan survey bersama pengelola.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary glow-button" href="#booking" data-booking-open>Booking Survey</a>
                    <a class="btn btn-ghost" href="<?= base_url('resident/start'); ?>">Daftar Penghuni</a>
                    <a class="btn btn-ghost" href="<?= html_escape(gh_contact_url()); ?>" target="_blank" rel="noopener"><?= html_escape(gh_contact_label()); ?></a>
                </div>
                <div class="hero-badges" role="list" aria-label="Ringkasan layanan">
                    <span role="listitem">Survey terjadwal</span>
                    <span role="listitem">Harga bulanan jelas</span>
                    <span role="listitem">Pilihan kamar sesuai kebutuhan</span>
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
                <p>Jelajahi pilihan gedung dan tipe kamar untuk menemukan hunian yang cocok.</p>
            </div>
            <div class="reveal delay-1">
                <span>02</span>
                <strong>Survey cepat</strong>
                <p>Isi formulir singkat, lalu pengelola akan menghubungi Anda untuk menjadwalkan survey.</p>
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

        <?php
            $roomsByProperty = array();
            foreach ($all_rooms as $room) {
                $propertyCode = isset($room['code']) && $room['code'] !== '' ? $room['code'] : 'GUL HOUSE';
                if (! isset($roomsByProperty[$propertyCode])) {
                    $roomsByProperty[$propertyCode] = array(
                        'code' => $propertyCode,
                        'display_name' => !empty($room['property_name']) ? $room['property_name'] : $propertyCode,
                        'city' => ($room['property_type'] ?? '') === 'apartment' ? 'Lokasi mengikuti masing-masing unit' : (isset($room['city']) ? $room['city'] : ''),
                        'price_from' => 0,
                        'total_rooms' => 0,
                        'available_count' => 0,
                        'types' => array(),
                        'detail_slug' => isset($room['slug']) ? $room['slug'] : '',
                    );
                }

                $price = (int) $room['price'];
                if ($price > 0 && ((int) $roomsByProperty[$propertyCode]['price_from'] === 0 || $price < (int) $roomsByProperty[$propertyCode]['price_from'])) {
                    $roomsByProperty[$propertyCode]['price_from'] = $price;
                    $roomsByProperty[$propertyCode]['detail_slug'] = isset($room['slug']) ? $room['slug'] : $roomsByProperty[$propertyCode]['detail_slug'];
                }

                $roomsByProperty[$propertyCode]['total_rooms'] += (int) $room['total_rooms'];
                $roomsByProperty[$propertyCode]['available_count'] += (int) $room['available_count'];
                $roomsByProperty[$propertyCode]['types'][] = array(
                    'name' => isset($room['property_type']) && $room['property_type'] === 'apartment' ? $room['name'] : $room['type_name'],
                    'available_count' => (int) $room['available_count'],
                    'total_rooms' => (int) $room['total_rooms'],
                    'price' => $price,
                    'status' => $room['status'],
                    'slug' => isset($room['slug']) ? $room['slug'] : '',
                );
            }
            ksort($roomsByProperty);
        ?>
        <section class="section split" id="rooms">
            <div class="section-copy reveal">
                <p class="eyebrow">Ketersediaan</p>
                <h2>Pilih gedung dan tipe kamar yang paling pas.</h2>
                <p>Bandingkan lokasi dan pilihan kamar di setiap properti. Harga mulai mengikuti tipe kamar yang tersedia.</p>
            </div>
            <div class="type-grid property-room-grid">
                <?php $propertyIndex = 0; ?>
                <?php foreach ($roomsByProperty as $property): ?>
                    <article class="type-card property-room-card <?= (int) $property['available_count'] <= 0 ? 'is-unavailable' : ''; ?> reveal delay-<?= ($propertyIndex % 4); ?>">
                        <span><?= html_escape($property['display_name']); ?></span>
                        <small class="property-city"><?= html_escape(! empty($property['city']) ? $property['city'] : 'Lokasi belum diatur'); ?></small>
                        <strong>Mulai Rp <?= number_format((int) $property['price_from'], 0, ',', '.'); ?></strong>
                        <p><?= (int) $property['available_count']; ?> tersedia dari <?= (int) $property['total_rooms']; ?> kamar</p>
                        <div class="property-type-list">
                            <?php foreach ($property['types'] as $type): ?>
                                <a class="<?= (int) $type['available_count'] <= 0 ? 'is-unavailable' : ''; ?>" href="<?= base_url('rooms/' . html_escape($type['slug'])); ?>">
                                    <span><?= html_escape($type['name']); ?></span>
                                    <em><?= (int) $type['available_count']; ?>/<?= (int) $type['total_rooms']; ?> tersedia</em>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </article>
                    <?php $propertyIndex++; ?>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section availability-insight">
            <div class="section-copy reveal">
                <p class="eyebrow">Snapshot</p>
                <h2>Stok kamar kebaca dalam sekali lihat.</h2>
                <p>Lihat ketersediaan di setiap properti, lalu pilih tipe kamar untuk melihat harga dan fasilitasnya.</p>
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
                        <div class="insight-progress <?= $availableRate <= 0 ? 'is-empty' : ''; ?>">
                            <div class="insight-meter" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $availableRate; ?>" aria-label="<?= $availableRate; ?> persen tersedia">
                                <span style="width: <?= $availableRate; ?>%;"></span>
                            </div>
                            <em><?= $availableRate; ?>%</em>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <?php
            $publicPropertyGroup = function (array $room) {
                return isset($room['property_type']) && $room['property_type'] === 'apartment' ? 'APT' : $room['code'];
            };
            $filterProperties = array();
            foreach ($all_rooms as $room) {
                $propertyGroup = $publicPropertyGroup($room);
                $filterProperties[$propertyGroup] = $propertyGroup;
            }
            ksort($filterProperties);
        ?>
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
                <div class="room-filter catalog-room-filter" data-room-filter>
                    <div class="filter-group" data-filter-group="property">
                        <span>Jenis Hunian</span>
                        <div>
                            <button type="button" class="is-active" data-filter-field="property" data-filter-value="all">Semua</button>
                            <?php foreach ($filterProperties as $propertyCode): ?>
                                <button type="button" data-filter-field="property" data-filter-value="<?= html_escape($propertyCode); ?>"><?= html_escape($propertyCode); ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <div class="room-carousel" data-carousel>
                <?php foreach ($featured_rooms as $index => $room): ?>
                    <article class="room-card" data-carousel-slide data-room-card data-property="<?= html_escape($publicPropertyGroup($room)); ?>" data-property-code="<?= html_escape($room['code']); ?>" data-type="<?= html_escape($room['type_name']); ?>" data-status="<?= (int) $room['available_count'] > 0 ? 'available' : 'unavailable'; ?>">
                        <div class="room-photo<?= empty($room['cover_image']) ? ' room-photo-empty' : ''; ?>">
                            <?php if (! empty($room['cover_image'])): ?>
                                <img src="<?= html_escape($room['cover_image']); ?>" alt="<?= html_escape('Kamar ' . $room['name']); ?>" loading="lazy" decoding="async" style="object-position: <?= (int) (isset($room['image_focus_x']) ? $room['image_focus_x'] : 50); ?>% <?= (int) (isset($room['image_focus_y']) ? $room['image_focus_y'] : 70); ?>%;">
                            <?php else: ?><span>Foto belum tersedia</span><?php endif; ?>
                        </div>
                        <div class="room-body">
                            <span><?= html_escape($publicPropertyGroup($room)); ?></span>
                            <h3><?= html_escape($room['name']); ?></h3>
                            <p class="room-city"><?= html_escape(! empty($room['city']) ? $room['city'] : 'Lokasi belum diatur'); ?></p>
                            <p><?= (int) $room['available_count']; ?> kamar tersedia, mulai Rp <?= number_format((int) $room['price'], 0, ',', '.'); ?>/bulan</p>
                            <div class="room-links">
                                <a href="<?= base_url('rooms/' . html_escape($room['slug'])); ?>">Lihat detail</a>
                                <?php if ((int) $room['available_count'] > 0): ?><a href="#booking" data-booking-open>Booking survey</a><?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="room-empty-state" data-room-empty aria-hidden="true" role="status"><strong>Belum ada tipe yang cocok</strong><span>Pilih Semua pada filter untuk melihat pilihan lainnya.</span></div>
            <div class="carousel-dots" data-carousel-dots role="group" aria-label="Posisi carousel"></div>
        </section>

        <section class="section photo-preview-section">
            <div class="section-copy reveal">
                <p class="eyebrow">Preview foto</p>
                <h2>Nuansa kamar, area santai, dan living space.</h2>
                <p>Lihat suasana kamar dan fasilitas bersama sebelum menjadwalkan survey.</p>
            </div>
            <div class="preview-mosaic reveal delay-1">
                <?php foreach ($preview_photos as $index => $photo): ?>
                    <figure>
                        <img src="<?= html_escape($photo['src']); ?>" alt="<?= html_escape($photo['title']); ?>" loading="lazy" decoding="async">
                        <figcaption><?= html_escape($photo['category']); ?></figcaption>
                    </figure>
                <?php endforeach; ?>
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

        <section class="visual-band"<?php if (!empty($daily_living_photo['src'])): ?> style="background-image: linear-gradient(180deg, rgba(15,36,31,.08), rgba(15,36,31,.8)), url('<?= html_escape($daily_living_photo['src']); ?>')"<?php endif; ?>>
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
                <div class="rules-panel__header">
                    <h3>Aturan singkat</h3>
                    <a class="rules-detail-link" href="<?= base_url('documents/house_rules'); ?>" target="_blank" rel="noopener">
                        Lihat detail
                    </a>
                </div>
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

        <?php
            $propertyMaps = array(
                array(
                    'code' => 'GH 1',
                    'name' => 'Gulhouse 1',
                    'address' => 'Jl. H. Muchtar Raya No.96, RT.6/RW.8, Joglo, Kec. Kembangan, Kota Jakarta Barat, DKI Jakarta 11640',
                    'maps_url' => 'https://maps.app.goo.gl/XbAsDanWesr3FpMKA',
                    'embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d247.89404462167548!2d106.73711087479933!3d-6.223493099999987!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f184bb8dc073%3A0x4668b03a9462681a!2sArba%20Properti!5e0!3m2!1sid!2sid!4v1788919540224!5m2!1sid!2sid',
                    'lat' => -6.22342,
                    'lng' => 106.73698,
                ),
                array(
                    'code' => 'GH 2',
                    'name' => 'Gulhouse 2',
                    'address' => 'Jl. H. Muchtar Raya No.2a, RT.9/RW.8, Joglo, Kec. Kembangan, Kota Jakarta Barat, DKI Jakarta 11640',
                    'maps_url' => 'https://maps.app.goo.gl/9XBSHXUQEM547QXp9',
                    'embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d247.89404462167548!2d106.73711087479933!3d-6.223493099999987!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f10054586a53%3A0x3f3f54e2c1865ddb!2sGulhouse%20-%202!5e0!3m2!1sid!2sid!4v1788919573399!5m2!1sid!2sid',
                    'lat' => -6.22357,
                    'lng' => 106.73724,
                ),
            );
        ?>
        <section class="section location-section" id="location">
            <div class="location-copy reveal delay-1">
                <p class="eyebrow">Lokasi</p>
                <h2>Dibuat mudah untuk aktivitas harian.</h2>
                <p>GH 1 dan GH 2 berada di area yang sama di Joglo, Kembangan. Pilih gedung untuk melihat peta dan alamat masing-masing lokasi.</p>
                <div class="map-tabs" role="tablist" aria-label="Pilih lokasi Gulhouse">
                    <?php foreach ($propertyMaps as $index => $map): ?>
                        <button
                            type="button"
                            class="<?= $index === 0 ? 'is-active' : ''; ?>"
                            id="map-tab-<?= $index; ?>"
                            role="tab"
                            aria-controls="map-view-<?= $index; ?>"
                            tabindex="<?= $index === 0 ? '0' : '-1'; ?>"
                            data-map-tab="<?= html_escape($map['code']); ?>"
                            aria-selected="<?= $index === 0 ? 'true' : 'false'; ?>">
                            <span><?= html_escape($map['code']); ?></span>
                            <?= html_escape($map['name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="nearby-list">
                    <?php foreach ($nearby_places as $place): ?>
                        <div>
                            <strong><?= html_escape($place['place']); ?></strong>
                            <span><?= html_escape($place['distance']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="location-map-shell reveal" data-map-switcher>
                <div class="location-map">
                    <?php foreach ($propertyMaps as $index => $map): ?>
                        <div id="map-view-<?= $index; ?>" role="tabpanel" aria-labelledby="map-tab-<?= $index; ?>" data-map-view="<?= html_escape($map['code']); ?>"<?= $index === 0 ? '' : ' hidden'; ?>>
                            <iframe
                                title="Peta lokasi <?= html_escape($map['name']); ?>"
                                <?= $index === 0 ? 'src' : 'data-map-src'; ?>="<?= html_escape($map['embed_url']); ?>"
                                loading="lazy"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allowfullscreen></iframe>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="map-help">Jika peta tidak tampil karena koneksi atau pengaturan browser, gunakan tautan Google Maps pada alamat di bawah.</p>
                <div class="map-card-list">
                <?php foreach ($propertyMaps as $index => $map): ?>
                    <article
                        class="map-card <?= $index === 0 ? 'is-active' : ''; ?>"
                        data-map-panel="<?= html_escape($map['code']); ?>"
                        data-map-lat="<?= html_escape($map['lat']); ?>"
                        data-map-lng="<?= html_escape($map['lng']); ?>">
                        <span><?= html_escape($map['code']); ?></span>
                        <strong><?= html_escape($map['name']); ?></strong>
                        <p><?= html_escape($map['address']); ?></p>
                        <a href="<?= html_escape($map['maps_url']); ?>" target="_blank" rel="noopener">Buka Google Maps</a>
                    </article>
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
                    <p>Lihat langsung kondisi dan fasilitas kamar saat survey untuk menemukan pilihan yang sesuai kebutuhan Anda.</p>
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
                        <p>Harga dan ketersediaan mengikuti pembaruan pengelola. Konfirmasikan kembali pilihan kamar saat menjadwalkan survey.</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <button type="button" class="accordion-trigger" aria-expanded="false">
                        Apakah deposit dicatat?
                        <span></span>
                    </button>
                    <div class="accordion-panel">
                        <p>Ya. Pembayaran deposit, potongan jika ada, dan pengembaliannya dicatat agar dapat ditinjau bersama pengelola.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section booking-section" id="booking">
            <div class="booking-copy reveal">
                <p class="eyebrow">Booking</p>
                <h2>Jadwalkan survey kamar.</h2>
                <p>Isi data singkat. Tim GUL HOUSE akan menghubungi Anda melalui WhatsApp untuk mengonfirmasi jadwal survey.</p>
                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert success"><?= html_escape($this->session->flashdata('success')); ?></div>
                <?php elseif ($this->session->flashdata('warning')): ?>
                    <div class="alert warning"><?= html_escape($this->session->flashdata('warning')); ?></div>
                <?php endif; ?>
            </div>
            <div class="booking-modal-trigger reveal delay-1">
                <div class="booking-modal-trigger-copy">
                    <span>LANGKAH SELANJUTNYA</span>
                    <strong>Temukan kamar yang cocok.</strong>
                </div>
                <button type="button" class="booking-modal-trigger-button" data-booking-open>
                    Booking survey <span aria-hidden="true">&rarr;</span>
                </button>
            </div>
        </section>

        <div class="booking-modal" data-booking-modal aria-hidden="true"<?= $bookingError !== '' ? ' data-booking-error="' . html_escape($bookingErrorField) . '"' : ''; ?>>
            <div class="booking-modal-backdrop" data-booking-close></div>
            <section class="booking-modal-card" role="dialog" aria-modal="true" aria-labelledby="booking-modal-title">
                <button type="button" class="booking-modal-close" data-booking-close aria-label="Tutup modal booking">&times;</button>
                <p class="eyebrow">Booking</p>
                <h2 id="booking-modal-title">Jadwalkan survey kamar.</h2>
                <p>Isi data singkat. Tim GUL HOUSE akan menghubungi Anda melalui WhatsApp untuk mengonfirmasi jadwal survey.</p>
                <?php if ($bookingError !== ''): ?>
                    <div class="alert warning booking-error" id="booking-error-message" role="alert" tabindex="-1"><strong>Booking belum terkirim.</strong> <?= html_escape($bookingError); ?></div>
                <?php endif; ?>
                <form class="booking-form" method="post" action="<?= base_url('booking'); ?>">
                    <label>Nama Lengkap
                        <input type="text" name="fullname" required maxlength="150" placeholder="Nama calon penghuni" value="<?= html_escape($bookingValues['fullname']); ?>"<?= $bookingErrorAttrs('fullname'); ?>>
                    </label>
                    <label>No. WhatsApp
                        <input type="tel" name="phone" required inputmode="numeric" pattern="[0-9]{9,15}" minlength="9" maxlength="15" placeholder="08xxxxxxxxxx" value="<?= html_escape($bookingValues['phone']); ?>" data-booking-phone<?= $bookingErrorAttrs('phone'); ?>>
                    </label>
                    <label>Minat Kamar
                        <select name="room_interest" required<?= $bookingErrorAttrs('room_interest'); ?>>
                            <option value="">Pilih kamar/tipe</option>
                            <?php $bookingInterestFound = FALSE; ?>
                            <?php foreach ($featured_rooms as $room): ?>
                                <?php if ((int) $room['available_count'] <= 0) { continue; } ?>
                                <?php $isPreviousInterest = $bookingValues['room_interest'] === (string) $room['name']; if ($isPreviousInterest) { $bookingInterestFound = TRUE; } ?>
                                <option value="<?= html_escape($room['name']); ?>" <?= $isPreviousInterest ? 'selected' : ''; ?>><?= html_escape($room['name']); ?> - mulai Rp <?= number_format((int) $room['price'], 0, ',', '.'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($bookingValues['room_interest'] !== '' && ! $bookingInterestFound): ?><small class="booking-field-help">Pilihan sebelumnya, <?= html_escape($bookingValues['room_interest']); ?>, tidak tersedia. Silakan pilih kamar lain.</small><?php endif; ?>
                    </label>
                    <label>Estimasi mulai tinggal
                        <input type="date" name="move_in_plan" min="<?= (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d'); ?>" value="<?= html_escape($bookingValues['move_in_plan']); ?>" data-booking-date<?= $bookingErrorAttrs('move_in_plan'); ?>>
                    </label>
                    <label class="full">Catatan
                        <textarea name="message" rows="4" maxlength="2000" placeholder="Tanyakan fasilitas, deposit, atau jadwal survey"<?= $bookingErrorAttrs('message'); ?>><?= html_escape($bookingValues['message']); ?></textarea>
                    </label>
                    <button type="submit" class="glow-button">Kirim Booking</button>
                </form>
            </section>
        </div>

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
        <span class="footer-brand">GUL HOUSE</span>
        <nav class="footer-contacts" aria-label="Kontak GUL HOUSE">
            <span>Hubungi kami</span>
            <?php if (gh_contact_number()): ?>
                <a href="<?= html_escape(gh_contact_url()); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp GUL HOUSE">WhatsApp <span>+<?= html_escape(gh_contact_number()); ?></span></a>
            <?php endif; ?>
            <?php if (gh_instagram_username()): ?>
                <a href="<?= html_escape(gh_instagram_url()); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram GUL HOUSE">Instagram <span>@<?= html_escape(gh_instagram_username()); ?></span></a>
            <?php endif; ?>
        </nav>
        <a class="footer-top" href="#top" data-scroll-top>Kembali ke atas</a>
    </footer>
    <div class="sticky-cta">
        <a href="<?= html_escape(gh_contact_url()); ?>" target="_blank" rel="noopener"><?= html_escape(gh_contact_label()); ?></a>
        <a href="#booking" data-booking-open>Booking</a>
        <a href="<?= base_url('resident/portal'); ?>">Pembayaran</a>
    </div>
    <script defer src="<?= base_url('assets/landing/js/landing.js?v=20261020-portal-access'); ?>"></script>
</body>
</html>
