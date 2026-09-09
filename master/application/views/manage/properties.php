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
        <a class="brand" href="<?= base_url('dashboard'); ?>"><span>GH</span><strong>GUL HOUSE</strong></a>
        <nav>
            <a href="<?= base_url('dashboard'); ?>">Dashboard</a>
            <a class="is-active" href="<?= base_url('properties'); ?>">Properti</a>
            <a href="<?= base_url('room-types'); ?>">Tipe Kamar</a>
            <a href="<?= base_url('rooms'); ?>">Kamar</a>
            <a href="<?= base_url('tenants'); ?>">Penghuni</a>
            <a href="#">Booking</a>
        </nav>
    </aside>

    <main class="main-shell">
        <header class="topbar">
            <div><p>Master Data</p><h1>Properti</h1></div>
            <div class="admin-chip"><span><?= html_escape($admin_name); ?></span><a href="<?= base_url('logout'); ?>">Logout</a></div>
        </header>

        <?php if ($this->session->flashdata('success')): ?><div class="notice success"><?= html_escape($this->session->flashdata('success')); ?></div><?php endif; ?>
        <?php if ($this->session->flashdata('error')): ?><div class="notice error"><?= html_escape($this->session->flashdata('error')); ?></div><?php endif; ?>

        <section class="manage-grid">
            <article class="panel">
                <div class="panel-head">
                    <div><p>Tambah</p><h2>Properti Baru</h2></div>
                    <button type="button" class="mini-link" data-open-modal="#property-create-modal">Tambah Properti</button>
                </div>
                <p class="panel-copy">Tambah GH baru, apartemen, atau properti lain dari modal. Data nonaktif tetap tersimpan untuk histori.</p>
            </article>

            <article class="panel">
                <div class="panel-head"><div><p>Data</p><h2>Daftar Properti</h2></div></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Kode</th><th>Nama</th><th>Tipe</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><strong><?= html_escape($row['code']); ?></strong></td>
                                    <td><?= html_escape($row['name']); ?></td>
                                    <td><?= html_escape(ucfirst($row['property_type'])); ?></td>
                                    <td><span class="badge <?= (int) $row['is_active'] ? 'good' : 'muted'; ?>"><?= (int) $row['is_active'] ? 'Aktif' : 'Nonaktif'; ?></span></td>
                                    <td>
                                        <div class="table-actions">
                                            <button type="button" class="table-action" data-open-modal="#property-edit-<?= (int) $row['id']; ?>">Edit</button>
                                            <button type="button" class="table-action danger" data-delete-url="<?= base_url('properties/delete/' . (int) $row['id']); ?>" data-delete-label="<?= html_escape($row['code']); ?>">Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </main>

    <div class="modal" id="property-create-modal" aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card">
            <div class="modal-head"><h2>Tambah Properti</h2><button type="button" data-close-modal>x</button></div>
            <form class="manage-form" method="post" action="<?= base_url('properties/save'); ?>" data-confirm-submit>
                <input type="hidden" name="id" value="0">
                <label>Kode<input type="text" name="code" required placeholder="GH 3"></label>
                <label>Nama<input type="text" name="name" required placeholder="Gul House 3"></label>
                <label>Tipe Properti
                    <select name="property_type">
                        <option value="house">House</option>
                        <option value="apartment">Apartment</option>
                        <option value="other">Other</option>
                    </select>
                </label>
                <label>Alamat<textarea name="address" rows="4" placeholder="Alamat properti"></textarea></label>
                <label class="check-row"><input type="checkbox" name="is_active" value="1" checked> Aktif</label>
                <button type="submit">Simpan Properti</button>
            </form>
        </section>
    </div>

    <?php foreach ($rows as $row): ?>
        <div class="modal" id="property-edit-<?= (int) $row['id']; ?>" aria-hidden="true">
            <div class="modal-backdrop" data-close-modal></div>
            <section class="modal-card">
                <div class="modal-head"><h2>Edit <?= html_escape($row['code']); ?></h2><button type="button" data-close-modal>x</button></div>
                <form class="manage-form" method="post" action="<?= base_url('properties/save'); ?>" data-confirm-submit>
                    <input type="hidden" name="id" value="<?= (int) $row['id']; ?>">
                    <label>Kode<input type="text" name="code" required value="<?= html_escape($row['code']); ?>"></label>
                    <label>Nama<input type="text" name="name" required value="<?= html_escape($row['name']); ?>"></label>
                    <label>Tipe Properti
                        <select name="property_type">
                            <?php foreach (array('house' => 'House', 'apartment' => 'Apartment', 'other' => 'Other') as $value => $label): ?>
                                <option value="<?= $value; ?>" <?= $row['property_type'] === $value ? 'selected' : ''; ?>><?= $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Alamat<textarea name="address" rows="4"><?= html_escape((string) $row['address']); ?></textarea></label>
                    <label class="check-row"><input type="checkbox" name="is_active" value="1" <?= (int) $row['is_active'] === 1 ? 'checked' : ''; ?>> Aktif</label>
                    <button type="submit">Simpan Perubahan</button>
                </form>
            </section>
        </div>
    <?php endforeach; ?>

    <div class="modal confirm-modal" data-save-confirm-modal aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card small">
            <div class="modal-head"><h2>Simpan perubahan?</h2><button type="button" data-close-modal>x</button></div>
            <p>Data akan disimpan ke database master.</p>
            <div class="modal-actions"><button type="button" class="mini-link" data-close-modal>Batal</button><button type="button" class="danger-solid" data-confirm-save>Simpan</button></div>
        </section>
    </div>

    <div class="modal confirm-modal" data-delete-confirm-modal aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card small">
            <div class="modal-head"><h2>Hapus data?</h2><button type="button" data-close-modal>x</button></div>
            <p><strong data-delete-label>Data ini</strong> akan dinonaktifkan dari master.</p>
            <div class="modal-actions"><button type="button" class="mini-link" data-close-modal>Batal</button><button type="button" class="danger-solid" data-confirm-delete>Hapus</button></div>
        </section>
    </div>

    <script src="<?= base_url('assets/master/js/master.js'); ?>"></script>
</body>
</html>
