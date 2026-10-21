<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if (!function_exists('gh_money_input')) { function gh_money_input($value) { return $value !== null && $value !== '' ? 'Rp ' . number_format((int) $value, 0, ',', '.') : ''; } } ?>
    <?php foreach ($modalRows as $modalRoom): ?>
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
                                    <option value="<?= (int) $property['id']; ?>" data-property-type="<?= html_escape($property['property_type']); ?>" <?= (int) $modalRoom['property_id'] === (int) $property['id'] ? 'selected' : ''; ?>><?= html_escape($property['code'] . ' - ' . $property['name']); ?></option>
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
                        <label>Kode Kamar<input type="text" name="room_code" maxlength="30" required value="<?= html_escape($modalRoom['room_code']); ?>" placeholder="A-101"></label>
                        <label>Nama<input type="text" name="room_name" maxlength="100" value="<?= html_escape($modalRoom['room_name']); ?>" placeholder="Cappadocia"></label>
                        <label>Label<input type="text" name="room_label" maxlength="150" value="<?= html_escape($modalRoom['room_label']); ?>" placeholder="Cappadocia 101"></label>
                        <label>Lantai<input type="number" name="floor_no" min="0" max="255" value="<?= html_escape((string) $modalRoom['floor_no']); ?>"></label>
                    </div>
                    <?php $this->load->view('partials/location_fields', array('location_row' => $modalRoom, 'location_scope' => 'unit', 'region_data' => $region_data ?? array())); ?>
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