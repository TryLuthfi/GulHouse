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
?>
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