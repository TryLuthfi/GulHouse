<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    function gh_money_input($value) {
        return $value !== null && $value !== '' ? 'Rp ' . number_format((int) $value, 0, ',', '.') : '';
    }
    $totalRooms = count($rows);
    $availableRooms = 0;
    $occupiedRooms = 0;
    $publicRooms = 0;
    foreach ($rows as $roomRow) {
        if ($roomRow['status'] === 'available') {
            $availableRooms++;
        }
        if ($roomRow['status'] === 'occupied') {
            $occupiedRooms++;
        }
        if ((int) $roomRow['is_public'] === 1) {
            $publicRooms++;
        }
    }
    $typePropertyMap = array();
    $propertyTypeMap = array();
    foreach ($room_catalog as $catalogRoom) {
        $typeId = (int) $catalogRoom['room_type_id'];
        $propertyId = (int) $catalogRoom['property_id'];
        if ($typeId <= 0 || $propertyId <= 0) {
            continue;
        }
        if (empty($typePropertyMap[$typeId])) {
            $typePropertyMap[$typeId] = array();
        }
        $typePropertyMap[$typeId][$propertyId] = $propertyId;
        if (empty($propertyTypeMap[$propertyId])) {
            $propertyTypeMap[$propertyId] = array();
        }
        $propertyTypeMap[$propertyId][$typeId] = $typeId;
    }
    $metricUrl = function (array $set = array()) use ($filters) {
        $params = $filters;
        $active = ! empty($set);
        foreach ($set as $key => $value) {
            if (! isset($filters[$key]) || (string) $filters[$key] !== (string) $value) {
                $active = FALSE;
                break;
            }
        }

        if ($active) {
            foreach (array_keys($set) as $key) {
                unset($params[$key]);
            }
        } else {
            foreach ($set as $key => $value) {
                $params[$key] = $value;
            }
        }

        $params = array_filter($params, function ($value) {
            return $value !== '' && $value !== null && $value !== 0;
        });

        return base_url('rooms' . ($params ? '?' . http_build_query($params) : ''));
    };
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
            <a href="<?= base_url('dashboard'); ?>">Dashboard</a>
            <a href="<?= base_url('properties'); ?>">Properti</a>
            <a href="<?= base_url('room-types'); ?>">Tipe Kamar</a>
            <a class="is-active" href="<?= base_url('rooms'); ?>">Kamar</a>
            <a href="<?= base_url('tenants'); ?>">Penghuni</a>
            <a href="<?= base_url('payments'); ?>">Pembayaran</a>
            <a href="<?= base_url('photos'); ?>">Foto</a>
            <a href="#">Booking</a>
        </nav>
    </aside>

    <main class="main-shell">
        <header class="topbar">
            <div><p>Master Data</p><h1>Kamar Internal</h1></div>
            <div class="admin-chip"><span><?= html_escape($admin_name); ?></span><a href="<?= base_url('logout'); ?>">Logout</a></div>
        </header>

        <?php if ($this->session->flashdata('success')): ?><div class="notice success"><?= html_escape($this->session->flashdata('success')); ?></div><?php endif; ?>
        <?php if ($this->session->flashdata('error')): ?><div class="notice error"><?= html_escape($this->session->flashdata('error')); ?></div><?php endif; ?>

        <section class="metric-grid room-summary-grid">
            <a class="metric-card" href="<?= base_url('rooms'); ?>"><span>Total Kamar</span><strong><?= number_format($totalRooms, 0, ',', '.'); ?></strong></a>
            <a class="metric-card <?= $filters['status'] === 'available' ? 'is-active' : ''; ?>" href="<?= $metricUrl(array('status' => 'available')); ?>"><span>Available</span><strong><?= number_format($availableRooms, 0, ',', '.'); ?></strong></a>
            <a class="metric-card <?= $filters['status'] === 'occupied' ? 'is-active' : ''; ?>" href="<?= $metricUrl(array('status' => 'occupied')); ?>"><span>Occupied</span><strong><?= number_format($occupiedRooms, 0, ',', '.'); ?></strong></a>
            <a class="metric-card <?= $filters['public'] === 'public' ? 'is-active' : ''; ?>" href="<?= $metricUrl(array('public' => 'public')); ?>"><span>Public Landing</span><strong><?= number_format($publicRooms, 0, ',', '.'); ?></strong></a>
        </section>

        <section class="panel room-table-panel">
            <div class="panel-head">
                <div><p>Data</p><h2>Daftar Kamar</h2></div>
                <button type="button" class="mini-link" data-open-modal="#room-create-modal">Tambah Kamar</button>
            </div>
            <form class="filter-form master-wide-filter-form" method="get" action="<?= base_url('rooms'); ?>" data-room-cascade-form>
                <label>Properti
                    <select name="property_id" data-room-property-filter data-cascade-template="room-filter-properties">
                        <option value="">Semua properti</option>
                        <?php foreach ($properties as $property): ?>
                            <?php
                                $propertyId = (int) $property['id'];
                                $typeIds = ! empty($propertyTypeMap[$propertyId]) ? implode(',', array_values($propertyTypeMap[$propertyId])) : '';
                            ?>
                            <option value="<?= $propertyId; ?>" data-type-ids="<?= html_escape($typeIds); ?>" <?= (int) $filters['property_id'] === $propertyId ? 'selected' : ''; ?>><?= html_escape($property['code'] . ' - ' . $property['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Tipe Kamar
                    <select name="room_type_id" data-room-type-filter data-cascade-template="room-filter-types">
                        <option value="">Semua tipe</option>
                        <?php foreach ($room_types as $type): ?>
                            <?php
                                $typeId = (int) $type['id'];
                                $propertyIds = ! empty($typePropertyMap[$typeId]) ? implode(',', array_values($typePropertyMap[$typeId])) : '';
                            ?>
                            <option value="<?= $typeId; ?>" data-property-ids="<?= html_escape($propertyIds); ?>" <?= (int) $filters['room_type_id'] === $typeId ? 'selected' : ''; ?>><?= html_escape($type['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Status
                    <select name="status">
                        <option value="">Semua status</option>
                        <?php foreach (array('available' => 'Available', 'occupied' => 'Occupied', 'reserved' => 'Reserved', 'maintenance' => 'Maintenance', 'mess' => 'Internal/Mess', 'inactive' => 'Inactive') as $value => $label): ?>
                            <option value="<?= $value; ?>" <?= $filters['status'] === $value ? 'selected' : ''; ?>><?= $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Landing
                    <select name="public">
                        <option value="">Semua</option>
                        <option value="public" <?= $filters['public'] === 'public' ? 'selected' : ''; ?>>Public</option>
                        <option value="hidden" <?= $filters['public'] === 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                    </select>
                </label>
                <label>Cari
                    <input type="search" name="q" value="<?= html_escape($filters['q']); ?>" placeholder="Kode, label, tipe">
                </label>
                <button type="submit">Tampilkan</button>
                <a class="mini-link" href="<?= base_url('rooms'); ?>">Reset</a>
            </form>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Gedung</th>
                            <th>Kode</th>
                            <th>Label</th>
                            <th>Tipe</th>
                            <th>Harga</th>
                            <th>Status</th>
                            <th>Public</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><strong><?= html_escape($row['property_code']); ?></strong></td>
                                <td><?= html_escape($row['room_code']); ?></td>
                                <td><?= html_escape($row['room_label']); ?></td>
                                <td><?= html_escape($row['type_name']); ?></td>
                                <td><?= $row['monthly_price'] ? 'Rp ' . number_format((int) $row['monthly_price'], 0, ',', '.') : '-'; ?></td>
                                <td><span class="badge <?= html_escape($row['status']); ?>"><?= html_escape(ucfirst($row['status'])); ?></span></td>
                                <td><span class="badge <?= (int) $row['is_public'] ? 'good' : 'muted'; ?>"><?= (int) $row['is_public'] ? 'Public' : 'Hidden'; ?></span></td>
                                <td>
                                    <div class="table-actions">
                                        <button type="button" class="table-action" data-open-modal="#room-edit-<?= (int) $row['id']; ?>">Edit</button>
                                        <button type="button" class="table-action" data-delete-url="<?= base_url('rooms/toggle-public/' . (int) $row['id']); ?>" data-delete-label="ubah publish <?= html_escape($row['room_label']); ?>">Toggle</button>
                                        <button type="button" class="table-action danger" data-delete-url="<?= base_url('rooms/delete/' . (int) $row['id']); ?>" data-delete-label="<?= html_escape($row['room_label']); ?>">Hapus</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ( ! $rows): ?><tr><td colspan="8">Belum ada kamar.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <?php
        $emptyRoom = array('id' => 0, 'property_id' => '', 'room_type_id' => '', 'room_code' => '', 'room_name' => '', 'room_label' => '', 'floor_no' => '', 'deposit' => '', 'monthly_price' => '', 'status' => 'available', 'is_public' => 1);
        $roomModalRows = array_merge(array($emptyRoom), $rows);
    ?>
    <?php foreach ($roomModalRows as $modalRoom): ?>
        <?php $isCreate = (int) $modalRoom['id'] === 0; ?>
        <div class="modal wide" id="<?= $isCreate ? 'room-create-modal' : 'room-edit-' . (int) $modalRoom['id']; ?>" aria-hidden="true">
            <div class="modal-backdrop" data-close-modal></div>
            <section class="modal-card wide">
                <div class="modal-head"><h2><?= $isCreate ? 'Tambah Kamar' : 'Edit ' . html_escape($modalRoom['room_label']); ?></h2><button type="button" data-close-modal>x</button></div>
                <form class="manage-form room-form sectioned" method="post" action="<?= base_url('rooms/save'); ?>" data-confirm-submit>
                    <input type="hidden" name="id" value="<?= (int) $modalRoom['id']; ?>">
                    <div class="form-section">
                        <h3 class="form-section-title">Relasi Unit</h3>
                        <label>Properti
                            <select name="property_id" required>
                                <option value="">Pilih properti</option>
                                <?php foreach ($properties as $property): ?>
                                    <option value="<?= (int) $property['id']; ?>" <?= (int) $modalRoom['property_id'] === (int) $property['id'] ? 'selected' : ''; ?>><?= html_escape($property['code'] . ' - ' . $property['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Tipe
                            <select name="room_type_id" required>
                                <option value="">Pilih tipe</option>
                                <?php foreach ($room_types as $type): ?>
                                    <option value="<?= (int) $type['id']; ?>" <?= (int) $modalRoom['room_type_id'] === (int) $type['id'] ? 'selected' : ''; ?>><?= html_escape($type['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Status
                            <select name="status">
                                <?php foreach (array('available', 'occupied', 'reserved', 'maintenance', 'mess', 'inactive') as $status): ?>
                                    <option value="<?= $status; ?>" <?= $modalRoom['status'] === $status ? 'selected' : ''; ?>><?= html_escape(ucfirst($status)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="check-row"><input type="checkbox" name="is_public" value="1" <?= (int) $modalRoom['is_public'] === 1 ? 'checked' : ''; ?>> Muncul di agregasi landing</label>
                    </div>
                    <div class="form-section">
                        <h3 class="form-section-title">Identitas Kamar</h3>
                        <label>Kode Kamar<input type="text" name="room_code" required value="<?= html_escape($modalRoom['room_code']); ?>" placeholder="A-101"></label>
                        <label>Nama<input type="text" name="room_name" value="<?= html_escape($modalRoom['room_name']); ?>" placeholder="Cappadocia"></label>
                        <label>Label<input type="text" name="room_label" value="<?= html_escape($modalRoom['room_label']); ?>" placeholder="Cappadocia 101"></label>
                        <label>Lantai<input type="number" name="floor_no" min="0" value="<?= html_escape((string) $modalRoom['floor_no']); ?>"></label>
                    </div>
                    <div class="form-section">
                        <h3 class="form-section-title">Harga dan Deposit</h3>
                        <label>Deposit<input type="text" name="deposit" inputmode="numeric" value="<?= html_escape(gh_money_input($modalRoom['deposit'])); ?>" placeholder="Rp 1.000.000" data-money-input></label>
                        <label>Sewa Bulanan<input type="text" name="monthly_price" inputmode="numeric" value="<?= html_escape(gh_money_input($modalRoom['monthly_price'])); ?>" placeholder="Rp 2.500.000" data-money-input></label>
                    </div>
                    <div class="form-actions"><button type="submit">Simpan Kamar</button></div>
                </form>
            </section>
        </div>
    <?php endforeach; ?>

    <div class="modal confirm-modal" data-save-confirm-modal aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card small">
            <div class="modal-head"><h2>Simpan perubahan?</h2><button type="button" data-close-modal>x</button></div>
            <p>Data kamar akan disimpan ke database master.</p>
            <div class="modal-actions"><button type="button" class="mini-link" data-close-modal>Batal</button><button type="button" class="danger-solid" data-confirm-save>Simpan</button></div>
        </section>
    </div>

    <div class="modal confirm-modal" data-delete-confirm-modal aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card small">
            <div class="modal-head"><h2>Lanjutkan aksi?</h2><button type="button" data-close-modal>x</button></div>
            <p><strong data-delete-label>Data ini</strong> akan diproses setelah konfirmasi.</p>
            <div class="modal-actions"><button type="button" class="mini-link" data-close-modal>Batal</button><button type="button" class="danger-solid" data-confirm-delete>Lanjut</button></div>
        </section>
    </div>

    <script src="<?= base_url('assets/master/js/master.js?v=20261001-partial-metric'); ?>"></script>
</body>
</html>
