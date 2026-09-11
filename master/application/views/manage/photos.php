<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    $selectedProperty = isset($selected_property) ? $selected_property : '';
    $selectedType = isset($selected_type) ? $selected_type : '';
    $typePropertyMap = array();
    foreach ($room_catalog as $room) {
        $typeName = (string) $room['type_name'];
        $propertyCode = (string) $room['property_code'];
        if ($typeName === '' || $propertyCode === '') {
            continue;
        }
        if (empty($typePropertyMap[$typeName])) {
            $typePropertyMap[$typeName] = array();
        }
        $typePropertyMap[$typeName][$propertyCode] = $propertyCode;
    }
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
    <link rel="stylesheet" href="<?= base_url('assets/master/css/master.css?v=20260911-cascade'); ?>">
</head>
<body>
    <aside class="sidebar">
        <a class="brand" href="<?= base_url('dashboard'); ?>"><span>GH</span><strong>GUL HOUSE</strong></a>
        <nav>
            <a href="<?= base_url('dashboard'); ?>">Dashboard</a>
            <a href="<?= base_url('properties'); ?>">Properti</a>
            <a href="<?= base_url('room-types'); ?>">Tipe Kamar</a>
            <a href="<?= base_url('rooms'); ?>">Kamar</a>
            <a href="<?= base_url('tenants'); ?>">Penghuni</a>
            <a href="<?= base_url('payments'); ?>">Pembayaran</a>
            <a class="is-active" href="<?= base_url('photos'); ?>">Foto</a>
            <a href="#">Booking</a>
        </nav>
    </aside>

    <main class="main-shell">
        <header class="topbar">
            <div><p>Media</p><h1>Foto Landing</h1></div>
            <div class="admin-chip"><span><?= html_escape($admin_name); ?></span><a href="<?= base_url('logout'); ?>">Logout</a></div>
        </header>

        <?php if ($this->session->flashdata('success')): ?><div class="notice success"><?= html_escape($this->session->flashdata('success')); ?></div><?php endif; ?>
        <?php if ($this->session->flashdata('error')): ?><div class="notice error"><?= html_escape($this->session->flashdata('error')); ?></div><?php endif; ?>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <p>Slider</p>
                    <h2>Foto Hero Landing</h2>
                </div>
                <button type="button" class="mini-link" data-open-modal="#slider-upload-modal">Upload Slider</button>
            </div>
            <p class="panel-copy">Foto di sini dipakai otomatis oleh slider halaman depan dari folder <strong>uploads/SLIDER</strong>.</p>
            <?php if ($slider_photos): ?>
                <div class="photo-grid">
                    <?php foreach ($slider_photos as $index => $photo): ?>
                        <article class="photo-card">
                            <button
                                type="button"
                                class="photo-preview-trigger"
                                data-photo-preview
                                data-photo-src="<?= html_escape($photo['url']); ?>"
                                data-photo-title="<?= html_escape($photo['filename']); ?>"
                                data-photo-category="Slider <?= $index + 1; ?>"
                                data-photo-scope="slider"
                                data-photo-property=""
                                data-photo-type=""
                                data-photo-file="<?= html_escape($photo['filename']); ?>">
                                <img src="<?= html_escape($photo['url']); ?>" alt="<?= html_escape($photo['filename']); ?>" loading="lazy">
                            </button>
                            <div>
                                <span>Slider <?= $index + 1; ?></span>
                                <strong><?= html_escape($photo['filename']); ?></strong>
                                <p><?= number_format((int) ceil($photo['size'] / 1024), 0, ',', '.'); ?> KB</p>
                                <button type="button" class="table-action danger" data-delete-url="<?= html_escape($photo['delete_url']); ?>" data-delete-label="<?= html_escape($photo['filename']); ?>">Hapus</button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-panel">Belum ada foto slider.</div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <p>Galeri Kamar</p>
                    <h2>Foto Per Gedung dan Tipe</h2>
                </div>
                <button type="button" class="mini-link" data-open-modal="#room-upload-modal">Upload Foto Kamar</button>
            </div>
            <form class="filter-form" method="get" action="<?= base_url('photos'); ?>" data-photo-cascade-form>
                <label>Gedung
                    <select name="property" data-photo-property>
                        <option value="">Pilih gedung</option>
                        <?php foreach ($properties as $property): ?>
                            <option value="<?= html_escape($property['code']); ?>" <?= $selectedProperty === $property['code'] ? 'selected' : ''; ?>><?= html_escape($property['code'] . ' - ' . $property['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Tipe Kamar
                    <select name="type" data-photo-type data-cascade-template="photo-room-types">
                        <option value="">Pilih tipe</option>
                        <?php foreach ($room_types as $type): ?>
                            <?php $propertyCodes = ! empty($typePropertyMap[$type['name']]) ? implode(',', array_values($typePropertyMap[$type['name']])) : ''; ?>
                            <option value="<?= html_escape($type['name']); ?>" data-property-codes="<?= html_escape($propertyCodes); ?>" <?= $selectedType === $type['name'] ? 'selected' : ''; ?>><?= html_escape($type['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit">Tampilkan</button>
            </form>

            <?php if ($selectedProperty && $selectedType): ?>
                <p class="panel-copy">Menampilkan foto untuk <strong><?= html_escape($selectedProperty . ' - ' . $selectedType); ?></strong>.</p>
                <?php if ($room_photos): ?>
                    <div class="photo-grid">
                        <?php foreach ($room_photos as $photo): ?>
                            <article class="photo-card">
                                <button
                                    type="button"
                                    class="photo-preview-trigger"
                                    data-photo-preview
                                    data-photo-src="<?= html_escape($photo['url']); ?>"
                                    data-photo-title="<?= html_escape($photo['filename']); ?>"
                                    data-photo-category="<?= html_escape($photo['category']); ?>"
                                    data-photo-scope="room"
                                    data-photo-property="<?= html_escape($selectedProperty); ?>"
                                    data-photo-type="<?= html_escape($selectedType); ?>"
                                    data-photo-file="<?= html_escape($photo['filename']); ?>">
                                    <img src="<?= html_escape($photo['url']); ?>" alt="<?= html_escape($photo['filename']); ?>" loading="lazy">
                                </button>
                                <div>
                                    <span><?= html_escape($photo['category']); ?></span>
                                    <strong><?= html_escape($photo['filename']); ?></strong>
                                    <p><?= number_format((int) ceil($photo['size'] / 1024), 0, ',', '.'); ?> KB</p>
                                    <button type="button" class="table-action danger" data-delete-url="<?= html_escape($photo['delete_url']); ?>" data-delete-label="<?= html_escape($photo['filename']); ?>">Hapus</button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-panel">Belum ada foto untuk pilihan ini.</div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-panel">Pilih gedung dan tipe kamar untuk melihat galeri.</div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <div class="panel-head"><div><p>Ringkasan</p><h2>Folder Galeri Aktif</h2></div></div>
            <?php if ($room_photo_groups): ?>
                <div class="media-folder-list">
                    <?php foreach ($room_photo_groups as $group): ?>
                        <a href="<?= base_url('photos?property=' . rawurlencode($group['property']) . '&type=' . rawurlencode($group['type'])); ?>">
                            <strong><?= html_escape($group['property'] . ' - ' . $group['type']); ?></strong>
                            <span><?= (int) $group['count']; ?> foto</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-panel">Belum ada folder galeri kamar.</div>
            <?php endif; ?>
        </section>
    </main>

    <div class="modal" id="slider-upload-modal" aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card">
            <div class="modal-head"><h2>Upload Slider</h2><button type="button" data-close-modal>x</button></div>
            <form class="manage-form" method="post" action="<?= base_url('photos/upload-slider'); ?>" enctype="multipart/form-data" data-confirm-submit>
                <label>Foto Slider
                    <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required>
                </label>
                <button type="submit">Upload Foto</button>
            </form>
        </section>
    </div>

    <div class="modal wide" id="room-upload-modal" aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card wide">
            <div class="modal-head"><h2>Upload Foto Kamar</h2><button type="button" data-close-modal>x</button></div>
            <form class="manage-form room-batch-upload" method="post" action="<?= base_url('photos/upload-room'); ?>" enctype="multipart/form-data" data-confirm-submit data-room-upload-form>
                <div class="room-upload-rows" data-room-upload-rows>
                    <div class="room-upload-row" data-room-upload-row>
                        <input type="hidden" name="upload_rows[0][field]" value="photos_0" data-room-upload-field>
                        <label>Gedung
                            <select name="upload_rows[0][property_code]" required data-room-upload-name="property_code" data-photo-property>
                                <option value="">Pilih gedung</option>
                                <?php foreach ($properties as $property): ?>
                                    <option value="<?= html_escape($property['code']); ?>" <?= $selectedProperty === $property['code'] ? 'selected' : ''; ?>><?= html_escape($property['code'] . ' - ' . $property['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Tipe Kamar
                            <select name="upload_rows[0][room_type]" required data-room-upload-name="room_type" data-photo-type data-cascade-template="photo-room-types">
                                <option value="">Pilih tipe</option>
                                <?php foreach ($room_types as $type): ?>
                                    <?php $propertyCodes = ! empty($typePropertyMap[$type['name']]) ? implode(',', array_values($typePropertyMap[$type['name']])) : ''; ?>
                                    <option value="<?= html_escape($type['name']); ?>" data-property-codes="<?= html_escape($propertyCodes); ?>" <?= $selectedType === $type['name'] ? 'selected' : ''; ?>><?= html_escape($type['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Kategori
                            <select name="upload_rows[0][category]" required data-room-upload-name="category">
                                <?php foreach ($categories as $value => $label): ?>
                                    <option value="<?= html_escape($value); ?>"><?= html_escape($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Foto
                            <input type="file" name="photos_0[]" accept="image/jpeg,image/png,image/webp" multiple required data-room-upload-files>
                        </label>
                        <button type="button" class="table-action danger" data-room-upload-remove>Hapus Row</button>
                    </div>
                </div>
                <div class="room-upload-actions">
                    <button type="button" class="mini-link" data-room-upload-add>Tambah Row</button>
                    <button type="submit">Upload Foto</button>
                </div>
            </form>
        </section>
    </div>

    <div class="modal confirm-modal" data-save-confirm-modal aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card small">
            <div class="modal-head"><h2>Upload foto?</h2><button type="button" data-close-modal>x</button></div>
            <p>Foto akan disimpan ke folder upload dan langsung dipakai oleh landing sesuai kategori.</p>
            <div class="modal-actions"><button type="button" class="mini-link" data-close-modal>Batal</button><button type="button" class="danger-solid" data-confirm-save>Upload</button></div>
        </section>
    </div>

    <div class="modal confirm-modal" data-delete-confirm-modal aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card small">
            <div class="modal-head"><h2>Hapus foto?</h2><button type="button" data-close-modal>x</button></div>
            <p><strong data-delete-label>Foto ini</strong> akan dihapus dari folder upload.</p>
            <div class="modal-actions"><button type="button" class="mini-link" data-close-modal>Batal</button><button type="button" class="danger-solid" data-confirm-delete>Hapus</button></div>
        </section>
    </div>

    <div class="photo-preview-modal" data-master-photo-modal aria-hidden="true">
        <div class="photo-preview-backdrop" data-master-photo-close></div>
        <section class="photo-preview-shell" role="dialog" aria-modal="true" aria-label="Preview foto">
            <header class="photo-preview-top">
                <div>
                    <strong data-master-photo-title>Preview Foto</strong>
                    <span data-master-photo-category></span>
                </div>
                <div class="photo-preview-tools">
                    <button type="button" data-master-photo-prev aria-label="Foto sebelumnya">&lsaquo;</button>
                    <button type="button" data-master-photo-zoom-out aria-label="Perkecil foto">-</button>
                    <button type="button" data-master-photo-zoom-in aria-label="Perbesar foto">+</button>
                    <button type="button" data-master-photo-rotate-left aria-label="Putar kiri" title="Putar kiri">&#8634;</button>
                    <button type="button" data-master-photo-rotate-right aria-label="Putar kanan" title="Putar kanan">&#8635;</button>
                    <button type="button" data-master-photo-reset aria-label="Reset zoom">Reset</button>
                    <button type="button" class="save" data-master-photo-save data-save-url="<?= base_url('photos/rotate'); ?>">Simpan</button>
                    <button type="button" data-master-photo-next aria-label="Foto berikutnya">&rsaquo;</button>
                    <button type="button" class="dark" data-master-photo-close aria-label="Tutup preview">x</button>
                </div>
            </header>
            <figure class="photo-preview-stage" data-master-photo-stage>
                <img src="" alt="" draggable="false" data-master-photo-image>
                <figcaption><span data-master-photo-counter></span></figcaption>
            </figure>
        </section>
    </div>

    <script src="<?= base_url('assets/master/js/master.js?v=20260911-cascade'); ?>"></script>
</body>
</html>
