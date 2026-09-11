<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    $statusLabels = array(
        'active' => 'Aktif',
        'reserved' => 'Booking',
        'internal' => 'Internal',
        'empty' => 'Kosong',
        'ended' => 'Selesai',
    );
    $counts = array('active' => 0, 'reserved' => 0, 'internal' => 0, 'empty' => 0, 'ended' => 0);
    foreach ($rows as $row) {
        if (isset($counts[$row['stay_status']])) {
            $counts[$row['stay_status']]++;
        }
    }
    $emptyStay = array(
        'id' => 0,
        'tenant_id' => 0,
        'room_id' => '',
        'stay_status' => 'active',
        'check_in_date' => '',
        'check_out_date' => '',
        'monthly_price' => '',
        'deposit' => '',
        'source_period' => '',
        'source_unit_name' => '',
        'notes' => '',
        'fullname' => '',
        'birth_place_date' => '',
        'religion' => '',
        'identity_number' => '',
        'phone' => '',
        'marital_status' => '',
        'occupation' => '',
        'address' => '',
        'contact_name' => '',
        'relationship' => '',
        'emergency_phone' => '',
    );
    $modalRows = array_merge(array($emptyStay), $rows);
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
    <link rel="stylesheet" href="<?= base_url('assets/master/css/master.css'); ?>">
</head>
<body>
    <aside class="sidebar">
        <a class="brand" href="<?= base_url('dashboard'); ?>"><span>GH</span><strong>GUL HOUSE</strong></a>
        <nav>
            <a href="<?= base_url('dashboard'); ?>">Dashboard</a>
            <a href="<?= base_url('properties'); ?>">Properti</a>
            <a href="<?= base_url('room-types'); ?>">Tipe Kamar</a>
            <a href="<?= base_url('rooms'); ?>">Kamar</a>
            <a class="is-active" href="<?= base_url('tenants'); ?>">Penghuni</a>
            <a href="<?= base_url('photos'); ?>">Foto</a>
            <a href="#">Booking</a>
        </nav>
    </aside>

    <main class="main-shell">
        <header class="topbar">
            <div><p>Master Data</p><h1>Penghuni</h1></div>
            <div class="admin-chip"><span><?= html_escape($admin_name); ?></span><a href="<?= base_url('logout'); ?>">Logout</a></div>
        </header>

        <?php if ($this->session->flashdata('success')): ?><div class="notice success"><?= html_escape($this->session->flashdata('success')); ?></div><?php endif; ?>
        <?php if ($this->session->flashdata('error')): ?><div class="notice error"><?= html_escape($this->session->flashdata('error')); ?></div><?php endif; ?>

        <section class="metric-grid">
            <article><span>Aktif</span><strong><?= number_format($counts['active'], 0, ',', '.'); ?></strong></article>
            <article><span>Booking</span><strong><?= number_format($counts['reserved'], 0, ',', '.'); ?></strong></article>
            <article><span>Internal</span><strong><?= number_format($counts['internal'], 0, ',', '.'); ?></strong></article>
            <article><span>Kosong</span><strong><?= number_format($counts['empty'], 0, ',', '.'); ?></strong></article>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div><p>Tambah</p><h2>Data Penghuni Baru</h2></div>
                <button type="button" class="mini-link" data-open-modal="#tenant-create-modal">Tambah Penghuni</button>
            </div>
            <p class="panel-copy">Data penghuni hanya untuk master/admin. Landing tetap membaca status kamar tanpa menampilkan identitas penghuni.</p>
        </section>

        <section class="panel">
            <div class="panel-head"><div><p>Data</p><h2>Daftar Penghuni & Stay</h2></div></div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Penghuni</th>
                            <th>Kontak</th>
                            <th>Check In</th>
                            <th>Status</th>
                            <th>Harga</th>
                            <th>Darurat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td>
                                    <strong><?= html_escape(trim($row['property_code'] . ' - ' . $row['room_label'])); ?></strong>
                                    <span><?= html_escape($row['room_code'] . ' / ' . $row['type_name']); ?></span>
                                </td>
                                <td>
                                    <strong><?= html_escape($row['fullname'] ? $row['fullname'] : ($row['notes'] ? $row['notes'] : '-')); ?></strong>
                                    <span><?= html_escape($row['occupation'] ? $row['occupation'] : '-'); ?></span>
                                </td>
                                <td>
                                    <?= html_escape($row['phone'] ? $row['phone'] : '-'); ?>
                                    <span><?= html_escape($row['identity_number'] ? 'NIK tersimpan' : 'NIK belum ada'); ?></span>
                                </td>
                                <td><?= html_escape($row['check_in_date'] ? $row['check_in_date'] : '-'); ?></td>
                                <td><span class="badge <?= html_escape($row['stay_status']); ?>"><?= html_escape(isset($statusLabels[$row['stay_status']]) ? $statusLabels[$row['stay_status']] : ucfirst($row['stay_status'])); ?></span></td>
                                <td><?= $row['monthly_price'] ? 'Rp ' . number_format((int) $row['monthly_price'], 0, ',', '.') : '-'; ?></td>
                                <td>
                                    <?= html_escape($row['contact_name'] ? $row['contact_name'] : '-'); ?>
                                    <span><?= html_escape($row['emergency_phone'] ? $row['emergency_phone'] : '-'); ?></span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button type="button" class="table-action" data-open-modal="#tenant-edit-<?= (int) $row['id']; ?>">Edit</button>
                                        <?php if ($row['stay_status'] !== 'ended'): ?>
                                            <button type="button" class="table-action danger" data-delete-url="<?= base_url('tenants/end-stay/' . (int) $row['id']); ?>" data-delete-label="<?= html_escape($row['fullname'] ? $row['fullname'] : $row['source_unit_name']); ?>">Akhiri</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <?php foreach ($modalRows as $modalRow): ?>
        <?php $isCreate = (int) $modalRow['id'] === 0; ?>
        <div class="modal wide" id="<?= $isCreate ? 'tenant-create-modal' : 'tenant-edit-' . (int) $modalRow['id']; ?>" aria-hidden="true">
            <div class="modal-backdrop" data-close-modal></div>
            <section class="modal-card wide">
                <div class="modal-head"><h2><?= $isCreate ? 'Tambah Penghuni' : 'Edit ' . html_escape($modalRow['fullname'] ? $modalRow['fullname'] : $modalRow['source_unit_name']); ?></h2><button type="button" data-close-modal>x</button></div>
                <form class="manage-form tenant-form" method="post" action="<?= base_url('tenants/save'); ?>" data-confirm-submit>
                    <input type="hidden" name="stay_id" value="<?= (int) $modalRow['id']; ?>">
                    <input type="hidden" name="tenant_id" value="<?= (int) $modalRow['tenant_id']; ?>">
                    <label>Unit
                        <select name="room_id" required>
                            <option value="">Pilih unit</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= (int) $room['id']; ?>" <?= (int) $modalRow['room_id'] === (int) $room['id'] ? 'selected' : ''; ?>>
                                    <?= html_escape($room['property_code'] . ' - ' . $room['room_label'] . ' (' . $room['room_code'] . ' / ' . $room['type_name'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Status Stay
                        <select name="stay_status">
                            <?php foreach ($statusLabels as $status => $label): ?>
                                <option value="<?= $status; ?>" <?= $modalRow['stay_status'] === $status ? 'selected' : ''; ?>><?= html_escape($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Nama Penghuni<input type="text" name="fullname" value="<?= html_escape((string) $modalRow['fullname']); ?>" placeholder="Nama lengkap"></label>
                    <label>No. HP<input type="text" name="phone" value="<?= html_escape((string) $modalRow['phone']); ?>" placeholder="08xxxxxxxxxx"></label>
                    <label>NIK<input type="text" name="identity_number" value="<?= html_escape((string) $modalRow['identity_number']); ?>" placeholder="16 digit"></label>
                    <label>TTL<input type="text" name="birth_place_date" value="<?= html_escape((string) $modalRow['birth_place_date']); ?>" placeholder="Jakarta, 1 Januari 2000"></label>
                    <label>Agama<input type="text" name="religion" value="<?= html_escape((string) $modalRow['religion']); ?>"></label>
                    <label>Status<input type="text" name="marital_status" value="<?= html_escape((string) $modalRow['marital_status']); ?>" placeholder="Lajang/Menikah"></label>
                    <label>Pekerjaan<input type="text" name="occupation" value="<?= html_escape((string) $modalRow['occupation']); ?>"></label>
                    <label>Check In<input type="date" name="check_in_date" value="<?= html_escape((string) $modalRow['check_in_date']); ?>"></label>
                    <label>Check Out<input type="date" name="check_out_date" value="<?= html_escape((string) $modalRow['check_out_date']); ?>"></label>
                    <label>Harga Bulanan<input type="text" name="monthly_price" inputmode="numeric" value="<?= html_escape((string) $modalRow['monthly_price']); ?>"></label>
                    <label>Deposit<input type="text" name="deposit" inputmode="numeric" value="<?= html_escape((string) $modalRow['deposit']); ?>"></label>
                    <label>Periode Sumber<input type="text" name="source_period" value="<?= html_escape((string) $modalRow['source_period']); ?>" placeholder="September 2026"></label>
                    <label>Unit Sumber<input type="text" name="source_unit_name" value="<?= html_escape((string) $modalRow['source_unit_name']); ?>" placeholder="EPHESUS 210"></label>
                    <label>Kontak Darurat<input type="text" name="emergency_name" value="<?= html_escape((string) $modalRow['contact_name']); ?>"></label>
                    <label>Hubungan<input type="text" name="emergency_relationship" value="<?= html_escape((string) $modalRow['relationship']); ?>" placeholder="Ibu/Adik/Teman"></label>
                    <label>No. HP Darurat<input type="text" name="emergency_phone" value="<?= html_escape((string) $modalRow['emergency_phone']); ?>"></label>
                    <label class="full">Alamat<textarea name="address" rows="3"><?= html_escape((string) $modalRow['address']); ?></textarea></label>
                    <label class="full">Catatan<textarea name="notes" rows="3"><?= html_escape((string) $modalRow['notes']); ?></textarea></label>
                    <button type="submit">Simpan Penghuni</button>
                </form>
            </section>
        </div>
    <?php endforeach; ?>

    <div class="modal confirm-modal" data-save-confirm-modal aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card small">
            <div class="modal-head"><h2>Simpan perubahan?</h2><button type="button" data-close-modal>x</button></div>
            <p>Data penghuni akan disimpan ke database master.</p>
            <div class="modal-actions"><button type="button" class="mini-link" data-close-modal>Batal</button><button type="button" class="danger-solid" data-confirm-save>Simpan</button></div>
        </section>
    </div>

    <div class="modal confirm-modal" data-delete-confirm-modal aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card small">
            <div class="modal-head"><h2>Akhiri stay?</h2><button type="button" data-close-modal>x</button></div>
            <p><strong data-delete-label>Penghuni ini</strong> akan ditandai selesai dan kamar dibuka kembali.</p>
            <div class="modal-actions"><button type="button" class="mini-link" data-close-modal>Batal</button><button type="button" class="danger-solid" data-confirm-delete>Akhiri</button></div>
        </section>
    </div>

    <script src="<?= base_url('assets/master/js/master.js'); ?>"></script>
</body>
</html>
