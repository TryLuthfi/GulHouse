<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    if (!function_exists('gh_money_input')) { function gh_money_input($value) {
        return $value !== null && $value !== '' ? 'Rp ' . number_format((int) $value, 0, ',', '.') : '';
    }
    }
    if (!function_exists('gh_split_birth_place_date')) { function gh_split_birth_place_date($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return array('place' => '', 'date' => '', 'date_text' => '');
        }

        $parts = array_map('trim', explode(',', $value, 2));
        $place = isset($parts[0]) ? $parts[0] : '';
        $date = isset($parts[1]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $parts[1]) ? $parts[1] : '';
        $dateText = isset($parts[1]) && $date === '' ? $parts[1] : '';

        return array('place' => $place, 'date' => $date, 'date_text' => $dateText);
    }
    }
    $statusLabels = array(
        'active' => 'Aktif',
        'reserved' => 'Booking',
        'internal' => 'Internal',
        'empty' => 'Kosong',
        'ended' => 'Selesai',
    );
    $counts = $summary;
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
    $modalRows = array($emptyStay);
    $roomLookup = array();
    $typePropertyMap = array();
    $propertyTypeMap = array();
    foreach ($rooms as $room) {
        $roomLookup[(int) $room['id']] = $room;
        $typeId = (int) $room['room_type_id'];
        $propertyId = (int) $room['property_id'];
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

        return base_url('tenants' . ($params ? '?' . http_build_query($params) : ''));
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
    <?php $this->load->view('partials/sidebar', array('active_menu' => 'tenants')); ?>

    <main class="main-shell">
        <header class="topbar">
            <div><p>Master Data</p><h1>Penghuni</h1></div>
            <div class="admin-chip"><span><?= html_escape($admin_name); ?></span><?php $this->load->view('partials/logout'); ?></div>
        </header>

        <?php if ($this->session->flashdata('success')): ?><div class="notice success"><?= html_escape($this->session->flashdata('success')); ?></div><?php endif; ?>
        <?php if ($this->session->flashdata('error')): ?><div class="notice error"><?= html_escape($this->session->flashdata('error')); ?></div><?php endif; ?>

        <section class="metric-grid tenant-summary-grid">
            <a class="metric-card <?= $filters['stay_status'] === 'active' ? 'is-active' : ''; ?>" href="<?= $metricUrl(array('stay_status' => 'active')); ?>"><span>Aktif</span><strong><?= number_format($counts['active'], 0, ',', '.'); ?></strong></a>
            <a class="metric-card <?= $filters['stay_status'] === 'reserved' ? 'is-active' : ''; ?>" href="<?= $metricUrl(array('stay_status' => 'reserved')); ?>"><span>Booking</span><strong><?= number_format($counts['reserved'], 0, ',', '.'); ?></strong></a>
            <a class="metric-card <?= $filters['stay_status'] === 'internal' ? 'is-active' : ''; ?>" href="<?= $metricUrl(array('stay_status' => 'internal')); ?>"><span>Internal</span><strong><?= number_format($counts['internal'], 0, ',', '.'); ?></strong></a>
            <a class="metric-card <?= $filters['stay_status'] === 'empty' ? 'is-active' : ''; ?>" href="<?= $metricUrl(array('stay_status' => 'empty')); ?>"><span>Kosong</span><strong><?= number_format($counts['empty'], 0, ',', '.'); ?></strong></a>
        </section>

        <section class="panel tenant-table-panel">
            <div class="panel-head">
                <div><p>Data</p><h2>Daftar Penghuni & Stay</h2></div>
                <button type="button" class="mini-link" data-open-modal="#tenant-create-modal">Tambah Penghuni</button>
            </div>
            <form data-master-paged-filter class="filter-form master-wide-filter-form" method="get" action="<?= base_url('tenants'); ?>" data-tenant-cascade-form>
                <label>Properti
                    <select name="property_id" data-tenant-property-filter data-cascade-template="tenant-filter-properties">
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
                    <select name="room_type_id" data-tenant-type-filter data-cascade-template="tenant-filter-room-types">
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
                <label>Unit
                    <select name="room_id" data-tenant-room-select data-cascade-template="tenant-filter-rooms">
                        <option value="">Semua unit</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= (int) $room['id']; ?>" data-property-id="<?= (int) $room['property_id']; ?>" data-type-id="<?= (int) $room['room_type_id']; ?>" <?= (int) $filters['room_id'] === (int) $room['id'] ? 'selected' : ''; ?>><?= html_escape($room['property_code'] . ' - ' . $room['room_label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Status Stay
                    <select name="stay_status">
                        <option value="">Semua status</option>
                        <?php foreach ($statusLabels as $status => $label): ?>
                            <option value="<?= $status; ?>" <?= $filters['stay_status'] === $status ? 'selected' : ''; ?>><?= html_escape($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Cari
                    <input type="search" name="q" value="<?= html_escape($filters['q']); ?>" placeholder="Nama, HP, NIK, unit">
                </label>
                <?php $this->load->view('partials/master_pagination', array('pagination' => $pagination, 'allowed_sorts' => $allowed_sorts, 'filters' => $filters, 'master_route' => 'tenants', 'pagination_controls' => TRUE)); ?>
                <button type="submit">Tampilkan</button>
                <a class="mini-link" href="<?= base_url('tenants'); ?>">Reset</a>
            </form>
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
                                        <?php if ($row['tenant_id'] && in_array($row['stay_status'], array('active', 'reserved'), TRUE)): ?><button type="button" class="table-action" data-open-modal="#stay-contract-<?= (int) $row['id']; ?>">Draft Kontrak</button><?php endif; ?>
                                        <button type="button" class="table-action" data-edit-form-url="<?= base_url('master-form/tenants/' . (int) $row['id']); ?>">Edit</button>
                                        <?php if ($row['stay_status'] !== 'ended'): ?>
                                            <button type="button" class="table-action danger" data-delete-url="<?= base_url('tenants/end-stay/' . (int) $row['id']); ?>" data-delete-label="<?= html_escape($row['fullname'] ? $row['fullname'] : $row['source_unit_name']); ?>">Akhiri</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ( ! $rows): ?><tr><td colspan="8">Belum ada data penghuni.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php $this->load->view('partials/master_pagination', array('pagination' => $pagination, 'filters' => $filters, 'master_route' => 'tenants', 'pagination_controls' => FALSE)); ?>
        </section>
    </main>

    <?php $this->load->view('manage/forms/tenants', array('modalRows' => array($emptyStay), 'rooms' => $rooms, 'properties' => $properties, 'room_types' => $room_types)); ?>

    <?php foreach ($rows as $stay): if (! $stay['tenant_id'] || ! in_array($stay['stay_status'], array('active','reserved'), TRUE)) continue; ?>
    <div class="modal wide" id="stay-contract-<?= (int) $stay['id']; ?>" aria-hidden="true"><div class="modal-backdrop" data-close-modal></div><section class="modal-card"><div class="modal-head"><h2>Draft Kontrak Penghuni Existing</h2><button type="button" data-close-modal>x</button></div>
        <form class="manage-form" method="post" action="<?= base_url('operations/draft_contract_from_stay/' . (int) $stay['id']); ?>" data-confirm-submit>
            <p>Draft tidak membuktikan kontrak telah ditandatangani. Lengkapi checklist dokumen dan verifikasi deposit sebelum aktivasi.</p>
            <label>Mulai kontrak<input type="date" name="start_date" value="<?= html_escape((string) $stay['check_in_date']); ?>" required></label>
            <label>Akhir kontrak<input type="date" name="end_date" value="<?= html_escape((string) $stay['check_out_date']); ?>" required></label>
            <label>Tarif bulanan<input type="number" name="monthly_price" min="1" value="<?= (int) $stay['monthly_price']; ?>" required></label>
            <label>Deposit<input type="number" name="deposit_amount" min="0" value="<?= (int) $stay['deposit']; ?>" required></label><button type="submit">Buat Draft Kontrak</button>
        </form></section></div>
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

    <script src="<?= base_url('assets/master/js/master.js?v=20261021-pagination-1'); ?>"></script>
</body>
</html>
