<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
    <?php foreach ($modalRows as $row): ?>
        <div class="modal" id="type-edit-<?= (int) $row['id']; ?>" aria-hidden="true">
            <div class="modal-backdrop" data-close-modal></div>
            <section class="modal-card">
                <div class="modal-head"><h2>Edit <?= html_escape($row['name']); ?></h2><button type="button" data-close-modal>x</button></div>
                <form class="manage-form sectioned" method="post" action="<?= base_url('room-types/save'); ?>" data-confirm-submit>
                    <input type="hidden" name="id" value="<?= (int) $row['id']; ?>">
                    <div class="form-section">
                        <h3 class="form-section-title">Identitas Tipe</h3>
                        <label>Nama Tipe<input type="text" name="name" maxlength="80" required value="<?= html_escape($row['name']); ?>"></label>
                        <label class="check-row"><input type="checkbox" name="is_active" value="1" <?= (int) $row['is_active'] === 1 ? 'checked' : ''; ?>> Aktif</label>
                    </div>
                    <div class="form-section">
                        <h3 class="form-section-title">Deskripsi Landing</h3>
                        <label class="full">Deskripsi<textarea name="description" rows="5"><?= html_escape((string) $row['description']); ?></textarea></label>
                    </div>
                    <div class="form-actions"><button type="submit">Simpan Perubahan</button></div>
                </form>
            </section>
        </div>
    <?php endforeach; ?>