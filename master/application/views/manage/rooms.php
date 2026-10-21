<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    $totalRooms = (int) $summary['total'];
    $availableRooms = (int) $summary['available'];
    $occupiedRooms = (int) $summary['occupied'];
    $publicRooms = (int) $summary['public'];
    $apartmentPropertyIds = array();
    foreach ($properties as $property) {
        if (isset($property['property_type']) && $property['property_type'] === 'apartment') {
            $apartmentPropertyIds[(int) $property['id']] = TRUE;
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
    $metricUrl = function (array $set = array()) use ($filters, $pagination) {
        $params = array_merge($filters, array('per_page' => $pagination['per_page'], 'sort' => $pagination['sort'], 'dir' => $pagination['dir']));
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
    <link rel="stylesheet" href="<?= base_url('assets/master/css/master.css?v=20261020-admin-ui-1'); ?>">
<link rel="stylesheet" href="<?= base_url('assets/master/css/admin-guidelines.css?v=20261020-6'); ?>"><script src="<?= base_url('assets/master/js/admin-guidelines.js?v=20261020-2'); ?>" defer></script><link rel="stylesheet" href="<?= base_url('assets/master/css/master-pagination.css?v=20261021-1'); ?>"></head>
<body class="gh-admin-ui" data-master-paged>
    <?php $this->load->view('partials/sidebar', array('active_menu' => 'rooms')); ?>

    <main class="main-shell">
        <header class="topbar">
            <div><p>Master Data</p><h1>Kamar Internal</h1></div>
            <div class="admin-chip"><span><?= html_escape($admin_name); ?></span><?php $this->load->view('partials/logout'); ?></div>
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
            <form data-master-paged-filter class="filter-form master-wide-filter-form" method="get" action="<?= base_url('rooms'); ?>" data-room-cascade-form>
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
                <?php $this->load->view('partials/master_pagination', array('pagination' => $pagination, 'allowed_sorts' => $allowed_sorts, 'filters' => $filters, 'master_route' => 'rooms', 'pagination_controls' => TRUE)); ?>
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
                                <td>
                                    <?= html_escape($row['room_label']); ?>
                                    <?php if (isset($apartmentPropertyIds[(int) $row['property_id']])): ?>
                                        <br><small title="<?= html_escape((string) ($row['unit_address'] ?? '')); ?>"><?= html_escape(trim((string) ($row['unit_city'] ?? '')) !== '' ? $row['unit_city'] : 'Lokasi belum diatur'); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= html_escape($row['type_name']); ?></td>
                                <td><?= $row['monthly_price'] ? 'Rp ' . number_format((int) $row['monthly_price'], 0, ',', '.') : '-'; ?></td>
                                <td><span class="badge <?= html_escape($row['status']); ?>"><?= html_escape(ucfirst($row['status'])); ?></span></td>
                                <td><span class="badge <?= (int) $row['is_public'] ? 'good' : 'muted'; ?>"><?= (int) $row['is_public'] ? 'Public' : 'Hidden'; ?></span></td>
                                <td>
                                    <div class="table-actions">
                                        <button type="button" class="table-action" data-edit-form-url="<?= base_url('master-form/rooms/' . (int) $row['id']); ?>">Edit</button>
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
            <?php $this->load->view('partials/master_pagination', array('pagination' => $pagination, 'filters' => $filters, 'master_route' => 'rooms', 'pagination_controls' => FALSE)); ?>
        </section>
    </main>

    <?php
        $emptyRoom = array('id' => 0, 'property_id' => '', 'room_type_id' => '', 'room_code' => '', 'room_name' => '', 'room_label' => '', 'floor_no' => '', 'unit_city' => '', 'unit_address' => '', 'deposit' => '', 'monthly_price' => '', 'status' => 'available', 'is_public' => 1);
        $roomModalRows = array($emptyRoom);
    ?>
    <?php $this->load->view('manage/forms/rooms', array('modalRows' => array($emptyRoom), 'properties' => $properties, 'room_types' => $room_types, 'region_data' => $region_data ?? array())); ?>

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

    <script id="gh-region-data" type="application/json"><?= json_encode($region_data ?? array(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?></script>
    <script src="<?= base_url('assets/master/js/region-location.js?v=20261021-pagination-1'); ?>"></script>
    <script src="<?= base_url('assets/master/js/master.js?v=20261021-pagination-1'); ?>"></script>
</body>
</html>
