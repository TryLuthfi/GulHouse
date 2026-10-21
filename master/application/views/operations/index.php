<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    if (! function_exists('gh_ops_money')) {
        function gh_ops_money($value) {
            return 'Rp ' . number_format((int) $value, 0, ',', '.');
        }
    }
    if (! function_exists('gh_ops_value')) {
        function gh_ops_value($row, $field, $rooms, $tenants, $model) {
            $value = isset($row[$field]) ? $row[$field] : null;
            if ($field === 'room_id') {
                return html_escape($model->label_room($value, $rooms));
            }
            if ($field === 'tenant_id') {
                return html_escape($model->label_tenant($value, $tenants));
            }
            if (preg_match('/amount|price|cost|remaining/', $field)) {
                return gh_ops_money($value);
            }
            if ($value === null || $value === '') {
                return '-';
            }
            return html_escape((string) $value);
        }
    }
    if (! function_exists('gh_ops_field_value')) {
        function gh_ops_field_value($row, $field) {
            return isset($row[$field]) ? $row[$field] : '';
        }
    }

    // Labels describe the existing metric subsets; they do not recalculate them.
    $listMetrics = in_array($module_key, array('bookings','billing','receivables','deposits','finance'), TRUE);
    $statusMetricLabels = array(
        'bookings' => array('Belum selesai', 'Selesai / konversi lama'),
        'billing' => array('Belum lunas', 'Lunas'),
        'finance' => array('Belum dibayar', 'Dibayar'),
        'contracts' => array('Draf', 'Kontrak aktif'),
        'documents' => array('Diperlukan / diterima', 'Terverifikasi'),
        'maintenance' => array('Baru dilaporkan', 'Selesai'),
        'complaints' => array('Terbuka', 'Diselesaikan'),
        'facilities' => array('Belum ditindaklanjuti', 'Selesai'),
        'housekeeping' => array('Belum selesai', 'Selesai'),
        'audits' => array('Belum ditindaklanjuti', 'Selesai'),
        'notifications' => array('Draf', 'Terkirim')
    );
    $showAmountMetric = ! empty($config['sum_field']) || in_array($module_key, array('billing','receivables'), TRUE);
    $isReadonly = ! empty($config['readonly']);
    $isBookingModule = $module_key === 'bookings';
    $canManageRetention = $isBookingModule && ! empty($can_manage_retention);
    $canPurgeDemo = $isBookingModule && ! empty($can_purge_demo);
    $archiveFilter = isset($filters['archive']) ? $filters['archive'] : 'active';
    $statusOptions = array();
    if (! empty($config['status_field'])) {
        foreach ($rows as $row) {
            if (isset($row[$config['status_field']]) && $row[$config['status_field']] !== '') {
                $statusOptions[$row[$config['status_field']]] = ucfirst(str_replace('_', ' ', $row[$config['status_field']]));
            }
        }
        if (! empty($config['fields'][$config['status_field']]['options'])) {
            $statusOptions = $config['fields'][$config['status_field']]['options'];
        }
    }
    if ($isBookingModule) {
        $statusOptions = array('new' => 'Baru', 'contacted' => 'Dihubungi', 'survey' => 'Survey', 'done' => 'Selesai', 'cancelled' => 'Batal', 'converted' => 'Konversi lama');
    }
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
    <?php $this->load->view('partials/sidebar', array('active_menu' => $config['base'])); ?>

    <main class="main-shell">
        <header class="topbar">
            <div><p>Operasional</p><h1><?= html_escape($config['title']); ?></h1></div>
            <div class="admin-chip"><span><?= html_escape($admin_name); ?></span><?php $this->load->view('partials/logout'); ?></div>
        </header>

        <?php if ($this->session->flashdata('success')): ?><div class="notice success"><?= html_escape($this->session->flashdata('success')); ?></div><?php endif; ?>
        <?php if ($this->session->flashdata('error')): ?><div class="notice error"><?= html_escape($this->session->flashdata('error')); ?></div><?php endif; ?>

        <?php if ($module_key !== 'reports'): ?>
        <section class="metric-grid">
            <a class="metric-card" href="<?= base_url($config['base']); ?>"><span><?= $listMetrics ? 'Data ditampilkan' : 'Seluruh data'; ?></span><strong><?= number_format((int) $metrics['total'], 0, ',', '.'); ?></strong></a>
            <?php if (isset($statusMetricLabels[$module_key])): ?>
            <a class="metric-card" href="<?= base_url($config['base']); ?>"><span><?= html_escape($statusMetricLabels[$module_key][0]); ?></span><strong><?= number_format((int) $metrics['open'], 0, ',', '.'); ?></strong></a>
            <a class="metric-card" href="<?= base_url($config['base']); ?>"><span><?= html_escape($statusMetricLabels[$module_key][1]); ?></span><strong><?= number_format((int) $metrics['done'], 0, ',', '.'); ?></strong></a>
            <?php endif; ?>
            <?php if ($showAmountMetric): ?><a class="metric-card" href="<?= base_url($config['base']); ?>"><span><?= $listMetrics ? 'Nominal data ditampilkan' : 'Nominal seluruh data'; ?></span><strong><?= gh_ops_money($metrics['amount']); ?></strong></a><?php endif; ?>
        </section>
        <?php endif; ?>

        <section class="panel">
            <div class="panel-head">
                <div><p>Modul</p><h2><?= html_escape($config['title']); ?></h2><span class="panel-hint"><?= html_escape($config['subtitle']); ?></span></div>
                <?php if ($module_key === 'deposits'): ?><p class="panel-hint">Gunakan Proses Kontrak / Checkout untuk menutup masa tinggal. Refund ledger hanya setelah pemeriksaan akhir dan seluruh tagihan relevan selesai; konfirmasi dan referensi pemeriksaan wajib dicatat.</p><?php endif; ?>
                <div class="table-actions">
                    <a class="mini-link" href="<?= base_url('ops/' . $module_key . '/export?' . http_build_query($filters)); ?>">Export CSV</a>
                    <?php if (! $isReadonly): ?><button type="button" class="mini-link" data-open-modal="#ops-create-modal">Tambah Data</button><?php endif; ?>
                </div>
            </div>
            <form class="filter-form master-filter-form" method="get" action="<?= base_url($config['base']); ?>">
                <?php if ($isBookingModule): ?>
                    <label>Penyimpanan booking
                        <select name="archive">
                            <option value="active" <?= $archiveFilter === 'active' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="archived" <?= $archiveFilter === 'archived' ? 'selected' : ''; ?>>Arsip</option>
                            <option value="all" <?= $archiveFilter === 'all' ? 'selected' : ''; ?>>Semua booking</option>
                        </select>
                    </label>
                <?php endif; ?>
                <?php if (in_array($module_key, array('reports','billing','receivables','finance','deposits'), TRUE)): ?><label>Periode<input type="month" name="period" value="<?= html_escape($filters['period']); ?>" <?= $module_key === 'reports' ? 'required' : ''; ?>></label><?php endif; ?>
                <?php if ($statusOptions): ?>
                    <label>Status
                        <select name="status">
                            <option value="">Semua status</option>
                            <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?= html_escape($value); ?>" <?= $filters['status'] === (string) $value ? 'selected' : ''; ?>><?= html_escape($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <label>Cari
                    <input type="search" name="q" value="<?= html_escape($filters['q']); ?>" placeholder="Cari data">
                </label>
                <button type="submit">Tampilkan</button>
                <a class="mini-link" href="<?= base_url($config['base']); ?>">Reset</a>
            </form>
            <?php if ($isBookingModule): ?>
                <p class="panel-hint booking-retention-hint">Arsip menyembunyikan booking dari daftar aktif tanpa menghapus riwayat atau mengubah statusnya. Booking dapat dipulihkan dari daftar Arsip.</p>
                <?php if (isset($retention_counts) && is_array($retention_counts)): ?>
                    <div class="booking-retention-counts" aria-label="Jumlah booking">
                        <?php foreach (array('active' => 'Aktif', 'archived' => 'Arsip', 'total' => 'Seluruh booking') as $key => $label): if (! isset($retention_counts[$key])) { continue; } ?>
                            <span><?= html_escape($label); ?> <strong><?= number_format((int) $retention_counts[$key], 0, ',', '.'); ?></strong></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($module_key === 'billing'): ?><p class="panel-hint">Tagihan bulanan mengikuti masa tinggal aktif dalam alur otomatis. Data lama perlu rekonsiliasi. Rincian hanya dapat dilihat dan deposit tidak ditagihkan ulang setiap bulan.</p><?php endif; ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($config['columns'] as $label): ?><th><?= html_escape($label); ?></th><?php endforeach; ?>
                            <?php if (! $isReadonly): ?><th>Aksi</th><?php endif; ?>
                            <?php if ($module_key === 'bookings'): ?><th>Aksi</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php foreach ($config['columns'] as $field => $label): ?>
                                    <td>
                                        <?php if (! empty($config['status_field']) && $field === $config['status_field']): ?>
                                            <span class="badge <?= html_escape((string) gh_ops_field_value($row, $field)); ?>"><?= $isBookingModule && isset($statusOptions[$row[$field]]) ? html_escape($statusOptions[$row[$field]]) : gh_ops_value($row, $field, $rooms, $tenants, $this->Operations_model); ?></span>
                                        <?php else: ?>
                                            <?= gh_ops_value($row, $field, $rooms, $tenants, $this->Operations_model); ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <?php if (! $isReadonly): ?>
                                    <td>
                                        <div class="table-actions">
                                            <?php if ($module_key === 'contracts'): ?><a class="table-action" href="<?= base_url('contracts/print/' . (int) $row['id']); ?>" >Print</a><?php endif; ?>
                                            <?php if ($module_key === 'contracts' && ! in_array($row['status'], array('ended', 'cancelled'), TRUE)): ?><button type="button" class="table-action" data-open-modal="#contract-flow-<?= (int) $row['id']; ?>">Proses Kontrak</button><?php endif; ?>
                                            <?php if ($module_key === 'documents' && ! empty($row['file_path'])): ?><a class="table-action" href="<?= base_url('operations/download_document/' . (int) $row['id']); ?>">Unduh</a><?php endif; ?>
                                            <?php if ($module_key === 'templates' && (int) $row['is_active'] === 1): ?><a class="table-action" href="<?= base_url('templates/print/' . rawurlencode($row['template_key'])); ?>" >Print</a><?php endif; ?>
                                            <?php if ($module_key === 'complaints' && ! in_array($row['status'], array('resolved','cancelled'), TRUE)): ?><form method="post" action="<?= base_url('operations/escalate_complaint/' . (int) $row['id']); ?>"><button type="submit" class="table-action">Ke Maintenance</button></form><?php endif; ?>
                                            <button type="button" class="table-action" data-open-modal="#ops-edit-<?= (int) $row['id']; ?>">Edit</button>
                                            <?php if (! in_array($module_key, array('contracts', 'deposits'), TRUE)): ?><button type="button" class="table-action danger" data-delete-url="<?= base_url('ops/' . $module_key . '/delete/' . (int) $row['id']); ?>" data-delete-label="<?= html_escape($config['title'] . ' #' . (int) $row['id']); ?>">Hapus</button><?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                        <?php if ($module_key === 'bookings'): ?>
                            <td>
                                <div class="table-actions booking-retention-actions">
                                    <?php $bookingWa = isset($row['wa_url']) ? $row['wa_url'] : (function_exists('gh_booking_whatsapp_url') ? gh_booking_whatsapp_url($row['phone']) : ''); ?>
                                    <?php if ($bookingWa): ?><a class="table-action booking-wa" href="<?= html_escape($bookingWa); ?>" target="_blank" rel="noopener">WhatsApp</a><?php else: ?><span class="table-action booking-wa-disabled" aria-disabled="true" title="Nomor WhatsApp kosong atau tidak valid">WhatsApp tidak tersedia</span><?php endif; ?>
                                    <?php if (empty($row['archived_at'])): ?>
                                        <?php if (! in_array($row['status'], array('converted', 'done', 'cancelled'), TRUE)): ?><button type="button" class="table-action" data-open-modal="#booking-done-<?= (int) $row['id']; ?>">Tandai Selesai</button><?php endif; ?>
                                        <?php if (! in_array($row['status'], array('converted', 'done'), TRUE)): ?>
                                            <form class="booking-status-form" method="post" action="<?= base_url('operations/update_booking_status/' . (int) $row['id']); ?>" data-retention-submit>
                                                <label class="booking-status-label">Status tindak lanjut<select name="status" aria-label="Status booking #<?= (int) $row['id']; ?>"><?php foreach (array('new'=>'Baru','contacted'=>'Dihubungi','survey'=>'Survey','cancelled'=>'Batal') as $statusValue=>$statusLabel): ?><option value="<?= $statusValue; ?>" <?= $row['status']===$statusValue?'selected':''; ?>><?= $statusLabel; ?></option><?php endforeach; ?></select></label>
                                                <button type="submit" class="table-action">Simpan Data</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge">Diarsipkan</span>
                                    <?php endif; ?>
                                    <?php if ($canManageRetention): ?><button type="button" class="table-action" data-open-modal="#booking-retention-<?= (int) $row['id']; ?>"><?= empty($row['archived_at']) ? 'Arsipkan' : 'Pulihkan'; ?></button><?php endif; ?>
                                    <?php if ($canPurgeDemo): ?><a class="table-action" href="<?= base_url('booking/retention/' . (int) $row['id']); ?>">Tinjau data terkait</a><?php endif; ?>
                                </div>
                                <?php if (! empty($row['archived_at'])): ?><small class="booking-retention-meta"><?= html_escape($row['archived_at']); ?><?php if (! empty($row['archive_reason'])): ?> · <?= html_escape($row['archive_reason']); ?><?php endif; ?></small><?php endif; ?>
                            </td>
                        <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (! $rows): ?>
                            <tr><td colspan="<?= count($config['columns']) + ($isReadonly ? 0 : 1) + ($module_key === 'bookings' ? 1 : 0); ?>"><span class="admin-empty-message">Belum ada data yang sesuai. Periksa filter atau tambahkan data bila diperlukan.</span></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <?php if ($canManageRetention): foreach ($rows as $bookingRow): $isArchived = ! empty($bookingRow['archived_at']); ?>
        <div class="modal" id="booking-retention-<?= (int) $bookingRow['id']; ?>" aria-hidden="true">
            <div class="modal-backdrop" data-close-modal></div>
            <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="booking-retention-title-<?= (int) $bookingRow['id']; ?>">
                <div class="modal-head"><h2 id="booking-retention-title-<?= (int) $bookingRow['id']; ?>"><?= $isArchived ? 'Pulihkan' : 'Arsipkan'; ?> booking #<?= (int) $bookingRow['id']; ?>?</h2><button type="button" data-close-modal aria-label="Tutup konfirmasi">×</button></div>
                <p><?= html_escape($bookingRow['fullname']); ?>. <?= $isArchived ? 'Booking akan kembali ke daftar aktif dengan status sebelumnya.' : 'Booking akan disimpan dalam Arsip. Riwayat dan data terkait tetap tersimpan.'; ?></p>
                <form class="manage-form" method="post" action="<?= base_url('booking/' . ($isArchived ? 'restore/' : 'archive/') . (int) $bookingRow['id']); ?>" data-retention-submit>
                    <input type="hidden" name="confirmed" value="1">
                    <label>Alasan <?= $isArchived ? 'pemulihan' : 'pengarsipan'; ?> (5–500 karakter)<textarea name="reason" required minlength="5" maxlength="500"></textarea></label>
                    <div class="modal-actions"><button type="button" class="mini-link" data-close-modal>Batal</button><button type="submit"><?= $isArchived ? 'Ya, pulihkan booking' : 'Ya, arsipkan booking'; ?></button></div>
                </form>
            </section>
        </div>
    <?php endforeach; endif; ?>

    <?php if (! $isReadonly): ?>
        <div class="modal wide" id="ops-create-modal" aria-hidden="true">
            <div class="modal-backdrop" data-close-modal></div>
            <section class="modal-card wide">
                <div class="modal-head"><h2>Tambah <?= html_escape($config['title']); ?></h2><button type="button" data-close-modal>x</button></div>
                <form class="manage-form sectioned" method="post" action="<?= base_url('ops/' . $module_key . '/save'); ?>" <?= in_array($module_key, array('documents', 'templates'), TRUE) ? 'enctype="multipart/form-data"' : ''; ?> data-confirm-submit>
                    <input type="hidden" name="id" value="0">
                    <div class="form-section">
                        <h3 class="form-section-title">Data <?= html_escape($config['title']); ?></h3>
                        <?php $this->load->view('operations/partials/fields', array('fields' => $config['fields'], 'row' => array(), 'rooms' => $rooms, 'tenants' => $tenants, 'contracts' => $contracts, 'stays' => $stays)); ?>
                        <?php if ($module_key === 'documents'): ?><label class="full">Upload File<input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png"></label><?php endif; ?>
                        <?php if ($module_key === 'templates'): ?><label class="full">Lampiran PDF (opsional, maksimal 10 MB)<input type="file" name="template_file" accept=".pdf,application/pdf"></label><p>Lampiran PDF digunakan saat Print. Tanpa lampiran, isi template teks digunakan.</p><?php endif; ?>
                    </div>
                    <div class="form-actions"><button type="submit">Simpan Data</button></div>
                </form>
            </section>
        </div>

        <?php foreach ($rows as $row): ?>
            <div class="modal wide" id="ops-edit-<?= (int) $row['id']; ?>" aria-hidden="true">
                <div class="modal-backdrop" data-close-modal></div>
                <section class="modal-card wide">
                    <div class="modal-head"><h2>Edit <?= html_escape($config['title']); ?> #<?= (int) $row['id']; ?></h2><button type="button" data-close-modal>x</button></div>
                    <form class="manage-form sectioned" method="post" action="<?= base_url('ops/' . $module_key . '/save'); ?>" <?= in_array($module_key, array('documents', 'templates'), TRUE) ? 'enctype="multipart/form-data"' : ''; ?> data-confirm-submit>
                        <input type="hidden" name="id" value="<?= (int) $row['id']; ?>">
                        <div class="form-section">
                            <h3 class="form-section-title">Data <?= html_escape($config['title']); ?></h3>
                            <?php $this->load->view('operations/partials/fields', array('fields' => $config['fields'], 'row' => $row, 'rooms' => $rooms, 'tenants' => $tenants, 'contracts' => $contracts, 'stays' => $stays)); ?>
                            <?php if ($module_key === 'documents'): ?><label class="full">Upload File Baru<input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png"></label><?php endif; ?>
                            <?php if ($module_key === 'templates'): ?>
                                <label class="full">Lampiran PDF (opsional, maksimal 10 MB)<input type="file" name="template_file" accept=".pdf,application/pdf"></label>
                                <p><?= is_file($this->Operations_model->template_pdf_path($row['id'])) ? 'PDF sudah terpasang. Pilih file hanya untuk menggantinya.' : 'Belum ada lampiran PDF. Print menggunakan isi template teks.'; ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="form-actions"><button type="submit">Simpan Perubahan</button></div>
                    </form>
                </section>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($module_key === 'bookings'): foreach ($rows as $row): if (!empty($row['archived_at']) || in_array($row['status'], array('converted','done','cancelled'), TRUE)) { continue; } ?>
        <div class="modal" id="booking-done-<?= (int) $row['id']; ?>" aria-hidden="true">
            <div class="modal-backdrop" data-close-modal></div>
            <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="booking-done-title-<?= (int) $row['id']; ?>">
                <div class="modal-head"><h2 id="booking-done-title-<?= (int) $row['id']; ?>">Tandai booking selesai?</h2><button type="button" data-close-modal aria-label="Tutup konfirmasi">×</button></div>
                <p>Booking #<?= (int) $row['id']; ?> · <?= html_escape($row['fullname']); ?> akan ditandai Selesai. Tindakan ini hanya menutup tindak lanjut survey.</p>
                <p>Pendaftaran penghuni, reservasi dan pembayaran dilanjutkan melalui Daftar Penghuni dan Siklus Penghuni.</p>
                <form method="post" action="<?= base_url('booking/done/'.(int)$row['id']); ?>" data-retention-submit><div class="modal-actions"><button type="button" class="mini-link" data-close-modal>Batal</button><button type="submit" class="table-action admin-primary">Ya, Tandai Selesai</button></div></form>
            </section>
        </div>
    <?php endforeach; endif; ?>

    <?php if ($module_key === 'contracts'): foreach ($rows as $contract): if (in_array($contract['status'], array('ended', 'cancelled'), TRUE)) continue; ?>
    <div class="modal wide" id="contract-flow-<?= (int) $contract['id']; ?>" aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card"><div class="modal-head"><h2>Proses <?= html_escape($contract['contract_no']); ?></h2><button type="button" data-close-modal>x</button></div>
        <?php if ($contract['status'] === 'draft'): ?>
            <form class="manage-form" method="post" action="<?= base_url('operations/contract_action/' . (int) $contract['id'] . '/activate'); ?>" data-confirm-submit>
                <p>Aktivasi kontrak mencatat check-in, mengisi kamar, dan membuat checklist inventaris untuk diverifikasi petugas.</p>
                <label>Tanggal tanda tangan<input type="date" name="signed_at" value="<?= date('Y-m-d'); ?>" required></label>
                <p>Syarat: checklist KTP dan kontrak sudah diterima/diverifikasi. Deposit harus diterima atau penundaan disetujui secara eksplisit.</p>
                <label><input type="checkbox" name="inspection_confirmed" value="1" required> Pemeriksaan kamar dan inventaris check-in telah dilakukan</label>
                <label><input type="checkbox" name="deposit_deferred" value="1"> Saya menyetujui penundaan deposit dan mencatatnya pada kontrak</label>
                <button type="submit">Aktifkan &amp; Check-in</button>
            </form>
            <form class="manage-form" method="post" action="<?= base_url('operations/contract_action/' . (int) $contract['id'] . '/cancel'); ?>" data-confirm-submit><button type="submit">Batalkan Reservasi</button></form>
        <?php else: ?>
            <form class="manage-form" method="post" action="<?= base_url('operations/contract_action/' . (int) $contract['id'] . '/renew'); ?>" data-confirm-submit>
                <h3>Perpanjangan</h3><p>Perpanjangan dimulai setelah kontrak ini berakhir. Tarif baru berlaku untuk periode berikutnya; tagihan tersimpan tetap dipertahankan.</p>
                <label>Akhir kontrak baru<input type="date" name="end_date" min="<?= date('Y-m-d', strtotime($contract['end_date'] . ' +1 day')); ?>" required></label>
                <label>Tarif bulanan baru<input type="number" name="monthly_price" min="1" value="<?= (int) $contract['monthly_price']; ?>" required></label><button type="submit">Perpanjang</button>
            </form>
            <form class="manage-form" method="post" action="<?= base_url('operations/contract_action/' . (int) $contract['id'] . '/checkout'); ?>" data-confirm-submit>
                <h3>Checkout &amp; Penyelesaian Deposit</h3><p>Saldo deposit: Rp <?= number_format($this->Operations_model->deposit_balance($contract['tenant_id'], $contract['stay_id']), 0, ',', '.'); ?>. Sisa deposit dapat diselesaikan melalui ledger deposit.</p>
                <label>Tanggal checkout<input type="date" name="checkout_date" value="<?= date('Y-m-d'); ?>" required></label>
                <label>Potongan deposit<input type="number" name="deduction_amount" min="0" value="0" required></label>
                <label>Refund deposit<input type="number" name="refund_amount" min="0" value="0" required></label>
                <label>Alasan potongan<textarea name="deduction_reason"></textarea></label>
                <label><input type="checkbox" name="inspection_confirmed" value="1" required> Pemeriksaan akhir kamar dan inventaris telah dilakukan</label>
                <label>Metode refund<input type="text" name="method" value="Transfer"></label>
                <label><input type="checkbox" name="needs_maintenance" value="1"> Kamar perlu maintenance sebelum tersedia</label><button type="submit">Checkout</button>
            </form>
        <?php endif; ?>
        </section>
    </div>
    <?php endforeach; endif; ?>
    <div class="modal confirm-modal" data-save-confirm-modal aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card small">
            <div class="modal-head"><h2>Simpan perubahan?</h2><button type="button" data-close-modal>x</button></div>
            <p>Data akan disimpan ke database operasional.</p>
            <div class="modal-actions"><button type="button" class="mini-link" data-close-modal>Batal</button><button type="button" class="danger-solid" data-confirm-save>Simpan</button></div>
        </section>
    </div>

    <div class="modal confirm-modal" data-delete-confirm-modal aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card small">
            <div class="modal-head"><h2>Hapus data?</h2><button type="button" data-close-modal>x</button></div>
            <p><strong data-delete-label>Data ini</strong> akan dihapus dari modul operasional.</p>
            <div class="modal-actions"><button type="button" class="mini-link" data-close-modal>Batal</button><button type="button" class="danger-solid" data-confirm-delete>Hapus</button></div>
        </section>
    </div>

    <script src="<?= base_url('assets/master/js/master.js?v=20261021-modal-stack-1'); ?>"></script>
</body>
</html>
