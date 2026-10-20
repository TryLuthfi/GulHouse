<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php foreach ($fields as $field => $meta): ?>
    <?php
        $type = isset($meta['type']) ? $meta['type'] : 'text';
        $label = isset($meta['label']) ? $meta['label'] : $field;
        $value = isset($row[$field]) ? $row[$field] : (isset($meta['default']) ? $meta['default'] : '');
        $required = ! empty($meta['required']) ? 'required' : '';
        $placeholder = isset($meta['placeholder']) ? $meta['placeholder'] : '';
    ?>
    <?php if ($type === 'textarea'): ?>
        <label class="full"><?= html_escape($label); ?>
            <textarea name="<?= html_escape($field); ?>" rows="4" <?= $required; ?> placeholder="<?= html_escape($placeholder); ?>"><?= html_escape((string) $value); ?></textarea>
        </label>
    <?php elseif ($type === 'select'): ?>
        <label><?= html_escape($label); ?>
            <select name="<?= html_escape($field); ?>" <?= $required; ?>>
                <?php foreach ($meta['options'] as $optionValue => $optionLabel): ?>
                    <option value="<?= html_escape($optionValue); ?>" <?= (string) $value === (string) $optionValue ? 'selected' : ''; ?>><?= html_escape($optionLabel); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php elseif ($type === 'room'): ?>
        <label><?= html_escape($label); ?>
            <select name="<?= html_escape($field); ?>" <?= $required; ?>>
                <option value="">Pilih kamar</option>
                <?php foreach ($rooms as $room): ?>
                    <option value="<?= (int) $room['id']; ?>" <?= (int) $value === (int) $room['id'] ? 'selected' : ''; ?>><?= html_escape(trim($room['property_code'] . ' - ' . $room['room_label'])); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php elseif ($type === 'tenant'): ?>
        <label><?= html_escape($label); ?>
            <select name="<?= html_escape($field); ?>" <?= $required; ?>>
                <option value="">Pilih penghuni</option>
                <?php foreach ($tenants as $tenant): ?>
                    <option value="<?= (int) $tenant['id']; ?>" <?= (int) $value === (int) $tenant['id'] ? 'selected' : ''; ?>><?= html_escape($tenant['fullname'] . ($tenant['phone'] ? ' - ' . $tenant['phone'] : '')); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php elseif ($type === 'contract' || $type === 'stay'): ?>
        <label><?= html_escape($label); ?>
            <select name="<?= html_escape($field); ?>" <?= $required; ?>>
                <option value=""><?= $type === 'contract' ? 'Pilih kontrak (opsional untuk dokumen umum)' : 'Pilih masa tinggal penghuni'; ?></option>
                <?php foreach (($type === 'contract' ? $contracts : $stays) as $relation): ?>
                    <?php if (empty($relation['tenant_id'])) continue; ?>
                    <?php $relationLabel = $type === 'contract' ? $relation['contract_no'] . ' — ' . $relation['fullname'] . ' — ' . $relation['property_code'] . ' / ' . $relation['room_label'] . ' (' . $relation['status'] . ')' : $relation['fullname'] . ' — ' . $relation['property_code'] . ' / ' . $relation['room_label'] . ' — ' . $relation['check_in_date'] . ' (' . $relation['stay_status'] . ')'; ?>
                    <option value="<?= (int) $relation['id']; ?>" data-tenant-id="<?= (int) $relation['tenant_id']; ?>" data-room-id="<?= (int) $relation['room_id']; ?>" <?= (int) $value === (int) $relation['id'] ? 'selected' : ''; ?>><?= html_escape($relationLabel); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php elseif ($type === 'checkbox'): ?>
        <label class="check-row"><input type="checkbox" name="<?= html_escape($field); ?>" value="1" <?= (int) $value === 1 || $value === '' ? 'checked' : ''; ?>> <?= html_escape($label); ?></label>
    <?php else: ?>
        <?php
            $inputType = 'text';
            $attrs = '';
            if ($type === 'date') {
                $inputType = 'date';
            } elseif ($type === 'datetime') {
                $inputType = 'datetime-local';
                $value = $value ? str_replace(' ', 'T', substr((string) $value, 0, 16)) : '';
            } elseif ($type === 'number') {
                $inputType = 'number';
            } elseif ($type === 'money') {
                $attrs = 'inputmode="numeric" data-money-input';
                $value = $value !== '' && $value !== null ? 'Rp ' . number_format((int) $value, 0, ',', '.') : '';
            }
        ?>
        <label><?= html_escape($label); ?>
            <input type="<?= $inputType; ?>" name="<?= html_escape($field); ?>" value="<?= html_escape((string) $value); ?>" placeholder="<?= html_escape($placeholder); ?>" <?= $required; ?> <?= $attrs; ?>>
        </label>
    <?php endif; ?>
<?php endforeach; ?>
