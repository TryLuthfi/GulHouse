<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
    <?php foreach ($modalRows as $row): ?>
        <div class="modal" id="property-edit-<?= (int) $row['id']; ?>" aria-hidden="true">
            <div class="modal-backdrop" data-close-modal></div>
            <section class="modal-card">
                <div class="modal-head"><h2>Edit <?= html_escape($row['code']); ?></h2><button type="button" data-close-modal>x</button></div>
                <form class="manage-form sectioned" method="post" action="<?= base_url('properties/save'); ?>" data-confirm-submit>
                    <input type="hidden" name="id" value="<?= (int) $row['id']; ?>">
                    <div class="form-section">
                        <h3 class="form-section-title">Identitas Properti</h3>
                        <label>Kode<input type="text" name="code" maxlength="30" required value="<?= html_escape($row['code']); ?>"></label>
                        <label>Nama<input type="text" name="name" maxlength="120" required value="<?= html_escape($row['name']); ?>"></label>
                        <label>Tipe Properti
                            <select name="property_type">
                                <?php foreach (array('house' => 'House', 'apartment' => 'Apartment', 'other' => 'Other') as $value => $label): ?>
                                    <option value="<?= $value; ?>" <?= $row['property_type'] === $value ? 'selected' : ''; ?>><?= $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="check-row"><input type="checkbox" name="is_active" value="1" <?= (int) $row['is_active'] === 1 ? 'checked' : ''; ?>> Aktif</label>
                    </div>
                    <?php $this->load->view('partials/location_fields', array('location_row' => $row, 'location_scope' => 'property', 'region_data' => $region_data ?? array())); ?>
                    <div class="form-actions"><button type="submit">Simpan Perubahan</button></div>
                </form>
            </section>
        </div>
    <?php endforeach; ?>