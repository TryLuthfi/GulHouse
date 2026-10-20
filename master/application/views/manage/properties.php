<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    $totalProperties = count($rows);
    $activeProperties = 0;
    $houseProperties = 0;
    $apartmentProperties = 0;
    foreach ($rows as $propertyRow) {
        if ((int) $propertyRow['is_active'] === 1) {
            $activeProperties++;
        }
        if ($propertyRow['property_type'] === 'house') {
            $houseProperties++;
        }
        if ($propertyRow['property_type'] === 'apartment') {
            $apartmentProperties++;
        }
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
            return $value !== '' && $value !== null;
        });

        return base_url('properties' . ($params ? '?' . http_build_query($params) : ''));
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
    <?php $this->load->view('partials/sidebar', array('active_menu' => 'properties')); ?>

    <main class="main-shell">
        <header class="topbar">
            <div><p>Master Data</p><h1>Properti</h1></div>
            <div class="admin-chip"><span><?= html_escape($admin_name); ?></span><?php $this->load->view('partials/logout'); ?></div>
        </header>

        <?php if ($this->session->flashdata('success')): ?><div class="notice success"><?= html_escape($this->session->flashdata('success')); ?></div><?php endif; ?>
        <?php if ($this->session->flashdata('error')): ?><div class="notice error"><?= html_escape($this->session->flashdata('error')); ?></div><?php endif; ?>

        <section class="metric-grid property-summary-grid">
            <a class="metric-card" href="<?= base_url('properties'); ?>"><span>Total Properti</span><strong><?= number_format($totalProperties, 0, ',', '.'); ?></strong></a>
            <a class="metric-card <?= $filters['status'] === 'active' ? 'is-active' : ''; ?>" href="<?= $metricUrl(array('status' => 'active')); ?>"><span>Properti Aktif</span><strong><?= number_format($activeProperties, 0, ',', '.'); ?></strong></a>
            <a class="metric-card <?= $filters['type'] === 'house' ? 'is-active' : ''; ?>" href="<?= $metricUrl(array('type' => 'house')); ?>"><span>House / GH</span><strong><?= number_format($houseProperties, 0, ',', '.'); ?></strong></a>
            <a class="metric-card <?= $filters['type'] === 'apartment' ? 'is-active' : ''; ?>" href="<?= $metricUrl(array('type' => 'apartment')); ?>"><span>Apartemen</span><strong><?= number_format($apartmentProperties, 0, ',', '.'); ?></strong></a>
        </section>

        <section class="panel property-table-panel">
            <div class="panel-head">
                <div><p>Data</p><h2>Daftar Properti</h2></div>
                <button type="button" class="mini-link" data-open-modal="#property-create-modal">Tambah Properti</button>
            </div>
            <form class="filter-form master-filter-form" method="get" action="<?= base_url('properties'); ?>">
                <label>Jenis
                    <select name="type">
                        <option value="">Semua jenis</option>
                        <?php foreach (array('house' => 'House', 'apartment' => 'Apartment', 'other' => 'Other') as $value => $label): ?>
                            <option value="<?= $value; ?>" <?= $filters['type'] === $value ? 'selected' : ''; ?>><?= $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Status
                    <select name="status">
                        <option value="">Semua status</option>
                        <option value="active" <?= $filters['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                </label>
                <label>Cari
                    <input type="search" name="q" value="<?= html_escape($filters['q']); ?>" placeholder="Kode, nama, kota, alamat">
                </label>
                <button type="submit">Tampilkan</button>
                <a class="mini-link" href="<?= base_url('properties'); ?>">Reset</a>
            </form>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Kode</th><th>Nama</th><th>Kota</th><th>Tipe</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><strong><?= html_escape($row['code']); ?></strong></td>
                                <td><?= html_escape($row['name']); ?></td>
                                <td><?= html_escape(isset($row['city']) && trim((string) $row['city']) !== '' ? $row['city'] : '-'); ?></td>
                                <td><?= html_escape(ucfirst($row['property_type'])); ?></td>
                                <td><span class="badge <?= (int) $row['is_active'] ? 'good' : 'muted'; ?>"><?= (int) $row['is_active'] ? 'Aktif' : 'Nonaktif'; ?></span></td>
                                <td>
                                    <div class="table-actions">
                                        <button type="button" class="table-action" data-open-modal="#property-edit-<?= (int) $row['id']; ?>">Edit</button>
                                        <button type="button" class="table-action danger" data-delete-url="<?= base_url('properties/delete/' . (int) $row['id']); ?>" data-delete-label="<?= html_escape($row['code']); ?>">Hapus</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ( ! $rows): ?><tr><td colspan="6">Belum ada properti.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div class="modal" id="property-create-modal" aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card">
            <div class="modal-head"><h2>Tambah Properti</h2><button type="button" data-close-modal>x</button></div>
            <form class="manage-form sectioned" method="post" action="<?= base_url('properties/save'); ?>" data-confirm-submit>
                <input type="hidden" name="id" value="0">
                <div class="form-section">
                    <h3 class="form-section-title">Identitas Properti</h3>
                    <label>Kode<input type="text" name="code" maxlength="30" required placeholder="GH 3"></label>
                    <label>Nama<input type="text" name="name" maxlength="120" required placeholder="Gul House 3"></label>
                    <label>Tipe Properti
                        <select name="property_type">
                            <option value="house">House</option>
                            <option value="apartment">Apartment</option>
                            <option value="other">Other</option>
                        </select>
                    </label>
                    <label class="check-row"><input type="checkbox" name="is_active" value="1" checked> Aktif</label>
                </div>
                <div class="form-section">
                    <h3 class="form-section-title">Lokasi</h3>
                    <label class="full">Kota<input type="text" name="city" maxlength="120" autocomplete="address-level2" placeholder="Contoh: Jakarta Barat atau Balikpapan"></label>
                    <label class="full">Alamat<textarea name="address" rows="4" placeholder="Alamat properti"></textarea></label>
                </div>
                <div class="form-actions"><button type="submit">Simpan Properti</button></div>
            </form>
        </section>
    </div>

    <?php foreach ($rows as $row): ?>
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
                    <div class="form-section">
                        <h3 class="form-section-title">Lokasi</h3>
                        <label class="full">Kota<input type="text" name="city" maxlength="120" autocomplete="address-level2" value="<?= html_escape(isset($row['city']) ? (string) $row['city'] : ''); ?>" placeholder="Contoh: Jakarta Barat atau Balikpapan"></label>
                        <label class="full">Alamat<textarea name="address" rows="4"><?= html_escape((string) $row['address']); ?></textarea></label>
                    </div>
                    <div class="form-actions"><button type="submit">Simpan Perubahan</button></div>
                </form>
            </section>
        </div>
    <?php endforeach; ?>

    <div class="modal confirm-modal" data-save-confirm-modal aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card small">
            <div class="modal-head"><h2>Simpan perubahan?</h2><button type="button" data-close-modal>x</button></div>
            <p>Data akan disimpan ke database master.</p>
            <div class="modal-actions"><button type="button" class="mini-link" data-close-modal>Batal</button><button type="button" class="danger-solid" data-confirm-save>Simpan</button></div>
        </section>
    </div>

    <div class="modal confirm-modal" data-delete-confirm-modal aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card small">
            <div class="modal-head"><h2>Hapus data?</h2><button type="button" data-close-modal>x</button></div>
            <p><strong data-delete-label>Data ini</strong> akan dinonaktifkan dari master.</p>
            <div class="modal-actions"><button type="button" class="mini-link" data-close-modal>Batal</button><button type="button" class="danger-solid" data-confirm-delete>Hapus</button></div>
        </section>
    </div>

    <script src="<?= base_url('assets/master/js/master.js?v=20261020-admin-ui-1'); ?>"></script>
</body>
</html>
