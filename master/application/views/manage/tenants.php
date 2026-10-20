<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    function gh_money_input($value) {
        return $value !== null && $value !== '' ? 'Rp ' . number_format((int) $value, 0, ',', '.') : '';
    }
    function gh_split_birth_place_date($value) {
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
<link rel="stylesheet" href="<?= base_url('assets/master/css/admin-guidelines.css?v=20261020-6'); ?>"><script src="<?= base_url('assets/master/js/admin-guidelines.js?v=20261020-2'); ?>" defer></script></head>
<body class="gh-admin-ui">
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
            <form class="filter-form master-wide-filter-form" method="get" action="<?= base_url('tenants'); ?>" data-tenant-cascade-form>
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
                                        <button type="button" class="table-action" data-open-modal="#tenant-edit-<?= (int) $row['id']; ?>">Edit</button>
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
        </section>
    </main>

    <?php foreach ($modalRows as $modalRow): ?>
        <?php $isCreate = (int) $modalRow['id'] === 0; ?>
        <?php
            $selectedRoom = isset($roomLookup[(int) $modalRow['room_id']]) ? $roomLookup[(int) $modalRow['room_id']] : null;
            $selectedPropertyId = $selectedRoom ? (int) $selectedRoom['property_id'] : 0;
            $selectedTypeId = $selectedRoom ? (int) $selectedRoom['room_type_id'] : 0;
            $birthParts = gh_split_birth_place_date($modalRow['birth_place_date']);
        ?>
        <div class="modal wide" id="<?= $isCreate ? 'tenant-create-modal' : 'tenant-edit-' . (int) $modalRow['id']; ?>" aria-hidden="true">
            <div class="modal-backdrop" data-close-modal></div>
            <section class="modal-card wide">
                <div class="modal-head"><h2><?= $isCreate ? 'Tambah Penghuni' : 'Edit ' . html_escape($modalRow['fullname'] ? $modalRow['fullname'] : $modalRow['source_unit_name']); ?></h2><button type="button" data-close-modal>x</button></div>
                <form class="manage-form tenant-form sectioned" method="post" action="<?= base_url('tenants/save'); ?>" data-confirm-submit data-tenant-cascade-form>
                    <input type="hidden" name="stay_id" value="<?= (int) $modalRow['id']; ?>">
                    <input type="hidden" name="tenant_id" value="<?= (int) $modalRow['tenant_id']; ?>">
                    <input type="hidden" name="birth_date_text" value="<?= html_escape($birthParts['date_text']); ?>">
                    <div class="form-section">
                        <h3 class="form-section-title">Relasi Stay</h3>
                        <label>Properti
                            <select data-tenant-property-filter data-cascade-template="tenant-modal-properties-<?= (int) $modalRow['id']; ?>">
                                <option value="">Semua properti</option>
                                <?php foreach ($properties as $property): ?>
                                    <?php
                                        $propertyId = (int) $property['id'];
                                        $typeIds = ! empty($propertyTypeMap[$propertyId]) ? implode(',', array_values($propertyTypeMap[$propertyId])) : '';
                                    ?>
                                    <option value="<?= $propertyId; ?>" data-type-ids="<?= html_escape($typeIds); ?>" <?= $selectedPropertyId === $propertyId ? 'selected' : ''; ?>><?= html_escape($property['code'] . ' - ' . $property['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Tipe Kamar
                            <select data-tenant-type-filter data-cascade-template="tenant-room-types">
                                <option value="">Semua tipe</option>
                                <?php foreach ($room_types as $type): ?>
                                    <?php
                                        $typeId = (int) $type['id'];
                                        $propertyIds = ! empty($typePropertyMap[$typeId]) ? implode(',', array_values($typePropertyMap[$typeId])) : '';
                                    ?>
                                    <option value="<?= $typeId; ?>" data-property-ids="<?= html_escape($propertyIds); ?>" <?= $selectedTypeId === $typeId ? 'selected' : ''; ?>><?= html_escape($type['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Unit
                            <select name="room_id" required data-tenant-room-select data-cascade-template="tenant-rooms">
                                <option value="">Pilih unit</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= (int) $room['id']; ?>" data-property-id="<?= (int) $room['property_id']; ?>" data-type-id="<?= (int) $room['room_type_id']; ?>" <?= (int) $modalRow['room_id'] === (int) $room['id'] ? 'selected' : ''; ?>>
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
                    </div>
                    <div class="form-section">
                        <h3 class="form-section-title">Data Penghuni</h3>
                        <label>Nama Penghuni<input type="text" name="fullname" value="<?= html_escape((string) $modalRow['fullname']); ?>" placeholder="Nama lengkap"></label>
                        <label>No. HP<input type="text" name="phone" value="<?= html_escape((string) $modalRow['phone']); ?>" placeholder="08xxxxxxxxxx"></label>
                        <label>NIK<input type="text" name="identity_number" value="<?= html_escape((string) $modalRow['identity_number']); ?>" placeholder="16 digit"></label>
                        <label>Tempat Lahir<input type="text" name="birth_place" value="<?= html_escape($birthParts['place']); ?>" placeholder="Jakarta"></label>
                        <label>Tanggal Lahir<input type="date" name="birth_date" value="<?= html_escape($birthParts['date']); ?>"></label>
                        <label>Agama<input type="text" name="religion" value="<?= html_escape((string) $modalRow['religion']); ?>"></label>
                        <label>Status<input type="text" name="marital_status" value="<?= html_escape((string) $modalRow['marital_status']); ?>" placeholder="Lajang/Menikah"></label>
                        <label>Pekerjaan<input type="text" name="occupation" value="<?= html_escape((string) $modalRow['occupation']); ?>"></label>
                    </div>
                    <div class="form-section">
                        <h3 class="form-section-title">Masa Tinggal dan Harga</h3>
                        <label>Check In<input type="date" name="check_in_date" value="<?= html_escape((string) $modalRow['check_in_date']); ?>"></label>
                        <label>Check Out<input type="date" name="check_out_date" value="<?= html_escape((string) $modalRow['check_out_date']); ?>"></label>
                        <label>Harga Bulanan<input type="text" name="monthly_price" inputmode="numeric" value="<?= html_escape(gh_money_input($modalRow['monthly_price'])); ?>" placeholder="Rp 2.500.000" data-money-input></label>
                        <label>Deposit<input type="text" name="deposit" inputmode="numeric" value="<?= html_escape(gh_money_input($modalRow['deposit'])); ?>" placeholder="Rp 1.000.000" data-money-input></label>
                        <label>Periode Sumber<input type="text" name="source_period" value="<?= html_escape((string) $modalRow['source_period']); ?>" placeholder="September 2026"></label>
                        <label>Unit Sumber<input type="text" name="source_unit_name" value="<?= html_escape((string) $modalRow['source_unit_name']); ?>" placeholder="EPHESUS 210"></label>
                    </div>
                    <div class="form-section">
                        <h3 class="form-section-title">Darurat dan Catatan</h3>
                        <label>Kontak Darurat<input type="text" name="emergency_name" value="<?= html_escape((string) $modalRow['contact_name']); ?>"></label>
                        <label>Hubungan<input type="text" name="emergency_relationship" value="<?= html_escape((string) $modalRow['relationship']); ?>" placeholder="Ibu/Adik/Teman"></label>
                        <label>No. HP Darurat<input type="text" name="emergency_phone" value="<?= html_escape((string) $modalRow['emergency_phone']); ?>"></label>
                        <label class="full">Alamat<textarea name="address" rows="3"><?= html_escape((string) $modalRow['address']); ?></textarea></label>
                        <label class="full">Catatan<textarea name="notes" rows="3"><?= html_escape((string) $modalRow['notes']); ?></textarea></label>
                    </div>
                    <div class="form-actions"><button type="submit">Simpan Penghuni</button></div>
                </form>
            </section>
        </div>
    <?php endforeach; ?>

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

    <script src="<?= base_url('assets/master/js/master.js?v=20261020-admin-ui-1'); ?>"></script>
</body>
</html>
