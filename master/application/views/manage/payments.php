<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    function gh_money($value) {
        return $value !== null && $value !== '' ? 'Rp ' . number_format((int) $value, 0, ',', '.') : '-';
    }
    $billStatuses = array(
        '' => 'Semua Status',
        'paid' => 'Paid',
        'partial' => 'Partial',
        'unpaid' => 'Unpaid',
        'overpaid' => 'Overpaid',
        'empty' => 'Kosong',
        'reserved' => 'Reserved',
        'internal' => 'Internal',
        'unknown' => 'Unknown',
    );
    $renderedBillModals = array();
    $typePropertyMap = array();
    foreach ($rooms as $room) {
        $typeId = (int) $room['room_type_id'];
        $propertyId = (int) $room['property_id'];
        if ($typeId <= 0 || $propertyId <= 0) {
            continue;
        }
        if (empty($typePropertyMap[$typeId])) {
            $typePropertyMap[$typeId] = array();
        }
        $typePropertyMap[$typeId][$propertyId] = $propertyId;
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
    <link rel="stylesheet" href="<?= base_url('assets/master/css/master.css?v=20260911-payments'); ?>">
</head>
<body>
    <aside class="sidebar">
        <a class="brand" href="<?= base_url('dashboard'); ?>"><span>GH</span><strong>GUL HOUSE</strong></a>
        <nav>
            <a href="<?= base_url('dashboard'); ?>">Dashboard</a>
            <a href="<?= base_url('properties'); ?>">Properti</a>
            <a href="<?= base_url('room-types'); ?>">Tipe Kamar</a>
            <a href="<?= base_url('rooms'); ?>">Kamar</a>
            <a href="<?= base_url('tenants'); ?>">Penghuni</a>
            <a class="is-active" href="<?= base_url('payments'); ?>">Pembayaran</a>
            <a href="<?= base_url('photos'); ?>">Foto</a>
            <a href="#">Booking</a>
        </nav>
    </aside>

    <main class="main-shell">
        <header class="topbar">
            <div><p>Master Data</p><h1>Pembayaran</h1></div>
            <div class="admin-chip"><span><?= html_escape($admin_name); ?></span><a href="<?= base_url('logout'); ?>">Logout</a></div>
        </header>

        <?php if ($this->session->flashdata('success')): ?><div class="notice success"><?= html_escape($this->session->flashdata('success')); ?></div><?php endif; ?>
        <?php if ($this->session->flashdata('error')): ?><div class="notice error"><?= html_escape($this->session->flashdata('error')); ?></div><?php endif; ?>

        <section class="metric-grid">
            <article><span>Total Bill</span><strong><?= number_format((int) $summary['bills'], 0, ',', '.'); ?></strong></article>
            <article><span>Total Terbayar</span><strong><?= gh_money($summary['paid_total']); ?></strong></article>
            <article><span>Transaksi</span><strong><?= number_format((int) $summary['payment_count'], 0, ',', '.'); ?></strong></article>
            <article><span>Kamar Kosong</span><strong><?= number_format((int) $summary['empty_rooms'], 0, ',', '.'); ?></strong></article>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div><p>Filter</p><h2>Monitoring Pembayaran</h2></div>
            </div>
            <form class="filter-form payment-filter-form" method="get" action="<?= base_url('payments'); ?>" data-payment-cascade-form>
                <label>Periode
                    <select name="period_id">
                        <option value="">Semua periode</option>
                        <?php foreach ($periods as $period): ?>
                            <option value="<?= (int) $period['id']; ?>" <?= (int) $filters['period_id'] === (int) $period['id'] ? 'selected' : ''; ?>><?= html_escape($period['period_label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Properti
                    <select name="property_id" data-cascade-property>
                        <option value="">Semua properti</option>
                        <?php foreach ($properties as $property): ?>
                            <option value="<?= (int) $property['id']; ?>" <?= (int) $filters['property_id'] === (int) $property['id'] ? 'selected' : ''; ?>><?= html_escape($property['code'] . ' - ' . $property['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Tipe Kamar
                    <select name="room_type_id" data-cascade-type>
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
                <label>Kamar
                    <select name="room_id" data-cascade-room>
                        <option value="">Semua kamar</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= (int) $room['id']; ?>" data-property-id="<?= (int) $room['property_id']; ?>" data-type-id="<?= (int) $room['room_type_id']; ?>" <?= (int) $filters['room_id'] === (int) $room['id'] ? 'selected' : ''; ?>><?= html_escape($room['property_code'] . ' - ' . $room['room_label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Status
                    <select name="status">
                        <?php foreach ($billStatuses as $value => $label): ?>
                            <option value="<?= html_escape($value); ?>" <?= $filters['status'] === $value ? 'selected' : ''; ?>><?= html_escape($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Cari
                    <input type="search" name="q" value="<?= html_escape($filters['q']); ?>" placeholder="Kamar, nama, HP">
                </label>
                <button type="submit">Tampilkan</button>
            </form>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div><p>Data</p><h2>Daftar Bill dan Transaksi</h2></div>
                <span class="panel-copy">Maksimal 260 baris per tampilan.</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th>Kamar</th>
                            <th>Penghuni</th>
                            <th>JT</th>
                            <th>Tagihan</th>
                            <th>Terbayar</th>
                            <th>Status</th>
                            <th>Transaksi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><strong><?= html_escape($row['period_label']); ?></strong></td>
                                <td><strong><?= html_escape($row['room_label']); ?></strong><span><?= html_escape($row['property_code'] . ' - ' . $row['type_name']); ?></span></td>
                                <td><?= html_escape($row['tenant_name_snapshot'] ?: '-'); ?><span><?= html_escape($row['tenant_phone_snapshot'] ?: ''); ?></span></td>
                                <td><?= html_escape($row['due_date'] ?: $row['due_date_text'] ?: '-'); ?></td>
                                <td><?= gh_money($row['base_price']); ?></td>
                                <td><?= gh_money($row['paid_total']); ?></td>
                                <td><span class="badge <?= html_escape($row['bill_status']); ?>"><?= html_escape(ucfirst($row['bill_status'])); ?></span></td>
                                <td>
                                    <?php if ($row['payment_id']): ?>
                                        <strong><?= gh_money($row['payment_amount']); ?></strong>
                                        <span><?= html_escape($row['payment_date'] ?: $row['payment_date_text'] ?: '-'); ?></span>
                                    <?php else: ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button type="button" class="table-action" data-open-modal="#bill-edit-<?= (int) $row['id']; ?>">Bill</button>
                                        <button type="button" class="table-action" data-open-modal="#payment-edit-<?= (int) $row['id']; ?>-<?= (int) $row['payment_id']; ?>"><?= $row['payment_id'] ? 'Edit Bayar' : 'Tambah Bayar'; ?></button>
                                        <?php if ($row['payment_id']): ?>
                                            <button type="button" class="table-action danger" data-delete-url="<?= base_url('payments/delete-payment/' . (int) $row['payment_id'] . '?' . http_build_query($filters)); ?>" data-delete-label="pembayaran <?= html_escape($row['room_label'] . ' ' . $row['period_label']); ?>">Hapus</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ( ! $rows): ?>
                            <tr><td colspan="9">Data pembayaran belum tersedia.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($issues): ?>
            <section class="panel">
                <div class="panel-head"><div><p>Import</p><h2>Issue Terbuka</h2></div></div>
                <div class="type-list">
                    <?php foreach ($issues as $issue): ?>
                        <div><span><?= html_escape($issue['issue_type']); ?></span><strong><?= number_format((int) $issue['total'], 0, ',', '.'); ?></strong><p>Perlu mapping/validasi master.</p></div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php foreach ($rows as $row): ?>
        <?php if (empty($renderedBillModals[$row['id']])): $renderedBillModals[$row['id']] = true; ?>
            <div class="modal wide" id="bill-edit-<?= (int) $row['id']; ?>" aria-hidden="true">
                <div class="modal-backdrop" data-close-modal></div>
                <section class="modal-card wide">
                    <div class="modal-head"><h2>Edit Bill <?= html_escape($row['room_label']); ?></h2><button type="button" data-close-modal>x</button></div>
                    <form class="manage-form payment-form" method="post" action="<?= base_url('payments/save-bill'); ?>" data-confirm-submit>
                        <input type="hidden" name="bill_id" value="<?= (int) $row['id']; ?>">
                        <?php foreach ($filters as $key => $value): ?><input type="hidden" name="<?= html_escape($key); ?>" value="<?= html_escape($value); ?>"><?php endforeach; ?>
                        <label>Periode<input type="text" value="<?= html_escape($row['period_label']); ?>" disabled></label>
                        <label>Kamar<input type="text" value="<?= html_escape($row['room_label']); ?>" disabled></label>
                        <label>Harga Dasar<input type="text" name="base_price" inputmode="numeric" value="<?= html_escape((string) $row['base_price']); ?>"></label>
                        <label>Deposit<input type="text" name="deposit_amount" inputmode="numeric" value="<?= html_escape((string) $row['deposit_amount']); ?>"></label>
                        <label>Tanggal JT<input type="date" name="due_date" value="<?= html_escape((string) $row['due_date']); ?>"></label>
                        <label>Total Terbayar<input type="text" name="paid_total" inputmode="numeric" value="<?= html_escape((string) $row['paid_total']); ?>"></label>
                        <label>Sudah JT<input type="text" name="past_due_amount" inputmode="numeric" value="<?= html_escape((string) $row['past_due_amount']); ?>"></label>
                        <label>Belum JT<input type="text" name="future_due_amount" inputmode="numeric" value="<?= html_escape((string) $row['future_due_amount']); ?>"></label>
                        <label>Status
                            <select name="bill_status">
                                <?php foreach ($billStatuses as $value => $label): if ($value === '') continue; ?>
                                    <option value="<?= html_escape($value); ?>" <?= $row['bill_status'] === $value ? 'selected' : ''; ?>><?= html_escape($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="full">Catatan<textarea name="notes" rows="3"><?= html_escape((string) $row['notes']); ?></textarea></label>
                        <button type="submit">Simpan Bill</button>
                    </form>
                </section>
            </div>
        <?php endif; ?>

        <div class="modal wide" id="payment-edit-<?= (int) $row['id']; ?>-<?= (int) $row['payment_id']; ?>" aria-hidden="true">
            <div class="modal-backdrop" data-close-modal></div>
            <section class="modal-card wide">
                <div class="modal-head"><h2><?= $row['payment_id'] ? 'Edit Pembayaran' : 'Tambah Pembayaran'; ?> <?= html_escape($row['room_label']); ?></h2><button type="button" data-close-modal>x</button></div>
                <form class="manage-form payment-form" method="post" action="<?= base_url('payments/save-payment'); ?>" data-confirm-submit>
                    <input type="hidden" name="payment_id" value="<?= (int) $row['payment_id']; ?>">
                    <input type="hidden" name="room_bill_id" value="<?= (int) $row['id']; ?>">
                    <?php foreach ($filters as $key => $value): ?><input type="hidden" name="<?= html_escape($key); ?>" value="<?= html_escape($value); ?>"><?php endforeach; ?>
                    <label>Periode<input type="text" value="<?= html_escape($row['period_label']); ?>" disabled></label>
                    <label>Kamar<input type="text" value="<?= html_escape($row['room_label']); ?>" disabled></label>
                    <label>Tanggal Bayar<input type="date" name="payment_date" value="<?= html_escape((string) $row['payment_date']); ?>"></label>
                    <label>Teks Tanggal<input type="text" name="payment_date_text" value="<?= html_escape((string) $row['payment_date_text']); ?>" placeholder="26-Sep"></label>
                    <label>Nilai<input type="text" name="amount" inputmode="numeric" required value="<?= html_escape((string) $row['payment_amount']); ?>"></label>
                    <label>Tipe
                        <select name="payment_type">
                            <?php foreach (array('rent' => 'Sewa', 'deposit' => 'Deposit', 'other' => 'Lainnya') as $value => $label): ?>
                                <option value="<?= $value; ?>" <?= $row['payment_type'] === $value ? 'selected' : ''; ?>><?= $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Metode<input type="text" name="method" value="<?= html_escape((string) $row['method']); ?>" placeholder="Transfer/Cash"></label>
                    <label>Referensi<input type="text" name="reference_no" value="<?= html_escape((string) $row['reference_no']); ?>"></label>
                    <label class="full">Catatan<textarea name="notes" rows="3"><?= html_escape((string) $row['payment_notes']); ?></textarea></label>
                    <button type="submit">Simpan Pembayaran</button>
                </form>
            </section>
        </div>
    <?php endforeach; ?>

    <div class="modal confirm-modal" data-save-confirm-modal aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card small">
            <div class="modal-head"><h2>Simpan perubahan?</h2><button type="button" data-close-modal>x</button></div>
            <p>Data pembayaran akan disimpan ke database master.</p>
            <div class="modal-actions"><button type="button" class="mini-link" data-close-modal>Batal</button><button type="button" class="danger-solid" data-confirm-save>Simpan</button></div>
        </section>
    </div>

    <div class="modal confirm-modal" data-delete-confirm-modal aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="modal-card small">
            <div class="modal-head"><h2>Hapus pembayaran?</h2><button type="button" data-close-modal>x</button></div>
            <p><strong data-delete-label>Pembayaran ini</strong> akan dihapus dari transaksi.</p>
            <div class="modal-actions"><button type="button" class="mini-link" data-close-modal>Batal</button><button type="button" class="danger-solid" data-confirm-delete>Hapus</button></div>
        </section>
    </div>

    <script src="<?= base_url('assets/master/js/master.js?v=20260911-payments'); ?>"></script>
</body>
</html>
