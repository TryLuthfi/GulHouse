<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    function gh_money($value) {
        return $value !== null && $value !== '' ? 'Rp ' . number_format((int) $value, 0, ',', '.') : '-';
    }
    function gh_money_input($value) {
        return $value !== null && $value !== '' ? 'Rp ' . number_format((int) $value, 0, ',', '.') : '';
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
    $propertyTypeMap = array();
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

        return base_url('payments' . ($params ? '?' . http_build_query($params) : ''));
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
    <link rel="stylesheet" href="<?= base_url('assets/master/css/payments-layout.css?v=20261020-4'); ?>">
<link rel="stylesheet" href="<?= base_url('assets/master/css/admin-guidelines.css?v=20261020-6'); ?>"><script src="<?= base_url('assets/master/js/admin-guidelines.js?v=20261020-2'); ?>" defer></script></head>
<body class="gh-admin-ui">
    <?php $this->load->view('partials/sidebar', array('active_menu' => 'payments')); ?>

    <main class="main-shell">
        <header class="topbar">
            <div><p>Master Data</p><h1>Pembayaran</h1></div>
            <div class="admin-chip"><span><?= html_escape($admin_name); ?></span><?php $this->load->view('partials/logout'); ?></div>
        </header>

        <?php if ($this->session->flashdata('success')): ?><div class="notice success"><?= html_escape($this->session->flashdata('success')); ?></div><?php endif; ?>
        <?php if ($this->session->flashdata('error')): ?><div class="notice error"><?= html_escape($this->session->flashdata('error')); ?></div><?php endif; ?>
        <?php if ($this->session->flashdata('warning')): ?><div class="notice warning"><?= html_escape($this->session->flashdata('warning')); ?></div><?php endif; ?>

        <section class="metric-grid">
            <a class="metric-card" href="<?= base_url('payments'); ?>"><span>Total Bill</span><strong><?= number_format((int) $summary['bills'], 0, ',', '.'); ?></strong></a>
            <a class="metric-card <?= $filters['status'] === 'paid' ? 'is-active' : ''; ?>" href="<?= $metricUrl(array('status' => 'paid')); ?>"><span>Total Terbayar</span><strong><?= gh_money($summary['paid_total']); ?></strong></a>
            <a class="metric-card <?= isset($filters['has_payment']) && $filters['has_payment'] === '1' ? 'is-active' : ''; ?>" href="<?= $metricUrl(array('has_payment' => '1')); ?>"><span>Transaksi</span><strong><?= number_format((int) $summary['payment_count'], 0, ',', '.'); ?></strong></a>
            <a class="metric-card <?= $filters['status'] === 'empty' ? 'is-active' : ''; ?>" href="<?= $metricUrl(array('status' => 'empty')); ?>"><span>Kamar Kosong</span><strong><?= number_format((int) $summary['empty_rooms'], 0, ',', '.'); ?></strong></a>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div><p>Filter</p><h2>Monitoring Pembayaran</h2></div>
            </div>
            <form class="filter-form payment-filter-form" method="get" action="<?= base_url('payments'); ?>" data-payment-cascade-form>
                <?php if (! empty($filters['has_payment'])): ?><input type="hidden" name="has_payment" value="<?= html_escape($filters['has_payment']); ?>"><?php endif; ?>
                <label>Periode
                    <select name="period_id">
                        <option value="">Semua periode</option>
                        <?php foreach ($periods as $period): ?>
                            <option value="<?= (int) $period['id']; ?>" <?= (int) $filters['period_id'] === (int) $period['id'] ? 'selected' : ''; ?>><?= html_escape($period['period_label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Properti
                    <select name="property_id" data-cascade-property data-cascade-template="payment-filter-properties">
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
                                        <button type="button" class="table-action" data-open-modal="#bill-edit-<?= (int) $row['id']; ?>">Rincian Bill</button>
                                        <button type="button" class="table-action" data-open-modal="#payment-add-<?= (int) $row['id']; ?>">Tambah Transaksi</button>
                                        <?php if ($row['payment_id']): ?><button type="button" class="table-action" data-open-modal="#payment-edit-<?= (int) $row['id']; ?>-<?= (int) $row['payment_id']; ?>">Edit Catatan</button><?php endif; ?>
                                        <?php if ($row['payment_id']): ?>
                                            <a class="table-action" href="<?= base_url('operations/print_receipt/' . (int) $row['payment_id']); ?>">Kuitansi</a>
                                            <a class="table-action" href="<?= base_url('paymentproofs/index/' . (int) $row['payment_id']); ?>">Bukti Transfer</a>
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
                    <div class="modal-head"><h2>Rincian Bill <?= html_escape($row['room_label']); ?></h2><button type="button" data-close-modal aria-label="Tutup rincian tagihan">×</button></div>
                    <p class="panel-hint">Rincian tagihan hanya dapat dilihat. Tagihan baru mengikuti alur otomatis.</p>
                    <div class="manage-form payment-form sectioned">
                        <div class="form-section"><h3 class="form-section-title">Identitas tagihan</h3>
                            <label>Periode<input readonly value="<?= html_escape($row['period_label']); ?>"></label>
                            <label>Kamar<input readonly value="<?= html_escape($row['property_code'].' · '.$row['room_label']); ?>"></label>
                            <label>Penghuni<input readonly value="<?= html_escape($row['tenant_name_snapshot'] ?: '-'); ?>"></label>
                            <label>Jatuh tempo<input readonly value="<?= html_escape($row['due_date'] ?: $row['due_date_text'] ?: '-'); ?>"></label>
                            <label>Status<input readonly value="<?= html_escape(isset($billStatuses[$row['bill_status']])?$billStatuses[$row['bill_status']]:$row['bill_status']); ?>"></label>
                        </div>
                        <div class="form-section"><h3 class="form-section-title">Rincian nominal</h3>
                            <label>Sewa pada tagihan<input readonly value="<?= html_escape(gh_money($row['base_price'])); ?>"></label>
                            <label>Deposit pada tagihan<input readonly value="<?= html_escape(gh_money($row['deposit_amount'])); ?>"></label>
                            <label>Total sewa terbayar<input readonly value="<?= html_escape(gh_money($row['paid_total'])); ?>"></label>
                            <label>Sewa sudah jatuh tempo<input readonly value="<?= html_escape(gh_money($row['past_due_amount'])); ?>"></label>
                            <label>Sewa belum jatuh tempo<input readonly value="<?= html_escape(gh_money($row['future_due_amount'])); ?>"></label>
                            <p class="panel-hint full">Deposit dicatat saat reservasi awal. Tagihan bulanan baru tidak menagih ulang deposit. Nilai deposit pada tagihan lama tetap ditampilkan sesuai riwayat.</p>
                            <label class="full">Catatan tagihan<textarea readonly rows="3"><?= html_escape((string)$row['notes']); ?></textarea></label>
                        </div>
                    </div>
                </section>
            </div>
        <?php endif; ?>

        <?php if (!empty($row['payment_id'])): ?>
        <div class="modal wide" id="payment-edit-<?= (int) $row['id']; ?>-<?= (int) $row['payment_id']; ?>" aria-hidden="true">
            <div class="modal-backdrop" data-close-modal></div>
            <section class="modal-card wide">
                <div class="modal-head"><h2>Catatan Pembayaran <?= html_escape($row['room_label']); ?></h2><button type="button" data-close-modal aria-label="Tutup catatan pembayaran">×</button></div>
                <p class="panel-hint">Nilai dan rincian transaksi lama tetap tersimpan. Hanya catatan yang dapat diubah melalui formulir ini.</p>
                <form class="manage-form payment-form sectioned" method="post" action="<?= base_url('payments/save-payment'); ?>" data-confirm-submit>
                    <input type="hidden" name="payment_id" value="<?= (int)$row['payment_id']; ?>">
                    <input type="hidden" name="room_bill_id" value="<?= (int)$row['id']; ?>">
                    <input type="hidden" name="request_key" value="<?= bin2hex(random_bytes(16)); ?>">
                    <?php foreach ($filters as $key=>$value): ?><input type="hidden" name="<?= html_escape($key); ?>" value="<?= html_escape($value); ?>"><?php endforeach; ?>
                    <div class="form-section"><h3 class="form-section-title">Riwayat transaksi</h3>
                        <label>Invoice<input readonly value="<?= html_escape('#'.$row['id'].' · '.$row['period_label'].' · '.$row['room_label']); ?>"></label>
                        <label>Nominal tercatat<input readonly value="<?= html_escape(gh_money($row['payment_amount'])); ?>"></label>
                        <label>Jenis pembayaran<input readonly value="<?= html_escape($row['payment_type']==='rent'?'Sewa':($row['payment_type']==='deposit'?'Deposit':'Lainnya')); ?>"></label>
                        <label>Metode tercatat<input readonly value="<?= html_escape((string)$row['method']); ?>"></label>
                        <label>Tanggal pembayaran<input readonly value="<?= html_escape($row['payment_date'] ?: $row['payment_date_text'] ?: '-'); ?>"></label>
                        <div class="full"><strong>Referensi transaksi lama</strong><p class="panel-hint"><?= html_escape($row['reference_no'] ?: 'Tidak ada referensi'); ?></p></div>
                        <label class="full">Catatan<textarea name="notes" rows="3"><?= html_escape((string)$row['payment_notes']); ?></textarea></label>
                    </div>
                    <div class="form-actions"><button type="submit">Simpan Catatan</button></div>
                </form>
            </section>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php $addBills = array(); foreach ($rows as $paymentBill): if (isset($addBills[$paymentBill['id']])) continue; $addBills[$paymentBill['id']] = TRUE;
        $settlementQuote = isset($payment_quotes[(int)$paymentBill['id']]) && is_array($payment_quotes[(int)$paymentBill['id']]) ? $payment_quotes[(int)$paymentBill['id']] : array();
        $canPayRent = !empty($settlementQuote['can_pay_rent']) && (string)$settlementQuote['can_pay_rent'] !== '0';
        $canPayDeposit = !empty($settlementQuote['can_pay_deposit']) && (string)$settlementQuote['can_pay_deposit'] !== '0';
        $settlementType = $canPayRent ? 'rent' : ($canPayDeposit ? 'deposit' : 'rent');
        $settlementAmount = max(0,(int)(isset($settlementQuote[$settlementType.'_remaining'])?$settlementQuote[$settlementType.'_remaining']:0));
        $settlementAvailable = !empty($settlementQuote[$settlementType.'_available']) && (string)$settlementQuote[$settlementType.'_available'] !== '0';
        $canSettle = !empty($settlementQuote) && $settlementAvailable && $settlementAmount>0 && ($settlementType==='rent'?$canPayRent:$canPayDeposit);
        $settlementMessage = empty($settlementQuote) ? 'Rincian pembayaran belum tersedia. Muat ulang halaman.' : (!$settlementAvailable ? (isset($settlementQuote[$settlementType.'_reason']) ? $settlementQuote[$settlementType.'_reason'] : 'Pembayaran belum tersedia untuk invoice ini.') : ($settlementAmount===0 ? 'Sudah lunas. Tidak ada sisa pembayaran untuk jenis ini.' : ($canSettle ? 'Nominal mengikuti sisa pembayaran yang tercatat.' : 'Pembayaran belum tersedia untuk invoice ini.')));
    ?>
    <div class="modal wide" id="payment-add-<?= (int)$paymentBill['id']; ?>" aria-hidden="true"><div class="modal-backdrop" data-close-modal></div><section class="modal-card"><div class="modal-head"><h2>Tambah Pembayaran <?= html_escape($paymentBill['room_label']); ?></h2><button type="button" data-close-modal aria-label="Tutup tambah pembayaran">×</button></div>
        <form class="manage-form sectioned" method="post" action="<?= base_url('payments/save-payment'); ?>" data-confirm-submit data-payment-settlement data-settlement-quote="<?= html_escape(json_encode($settlementQuote)); ?>">
            <input type="hidden" name="payment_id" value="0"><input type="hidden" name="room_bill_id" value="<?= (int)$paymentBill['id']; ?>"><input type="hidden" name="request_key" value="<?= bin2hex(random_bytes(16)); ?>">
            <?php foreach($filters as $key=>$value): ?><input type="hidden" name="<?= html_escape($key); ?>" value="<?= html_escape($value); ?>"><?php endforeach; ?>
            <div class="form-section"><h3 class="form-section-title">Pembayaran Transfer</h3>
                <p class="panel-hint full">Nominal diisi otomatis sesuai sisa pembayaran jenis yang dipilih. Catat hanya setelah transfer benar-benar diterima.</p>
                <label>Periode / invoice<input readonly value="<?= html_escape($paymentBill['period_label'].' · #'.$paymentBill['id']); ?>"></label>
                <label>Tanggal bayar<input type="date" name="payment_date" value="<?= (new DateTimeImmutable('now',new DateTimeZone('Asia/Jakarta')))->format('Y-m-d'); ?>" required></label>
                <label>Jenis pembayaran<select name="payment_type" required data-settlement-type><option value="rent" <?= $settlementType==='rent'?'selected':''; ?>>Sewa</option><option value="deposit" <?= $settlementType==='deposit'?'selected':''; ?>>Deposit</option></select></label>
                <label>Nominal sisa pembayaran<input type="text" name="amount" required readonly aria-readonly="true" data-settlement-amount value="<?= html_escape(gh_money_input($settlementAmount)); ?>"></label>
                <input type="hidden" name="method" value="Transfer"><label>Metode<input readonly value="Transfer"></label>
                <label class="full">Catatan<textarea name="notes" rows="3"></textarea></label>
                <input type="hidden" name="payment_date_text" value="">
                <p class="panel-hint full" role="status" data-settlement-status><?= html_escape($settlementMessage); ?></p>
            </div><div class="form-actions"><button type="submit" data-settlement-save <?= $canSettle?'':'disabled'; ?>>Simpan Pembayaran</button></div>
        </form></section></div>
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

    <script src="<?= base_url('assets/master/js/master.js?v=20261021-modal-stack-1'); ?>"></script>
    <script src="<?= base_url('assets/master/js/payment-settlement.js?v=20261020-1'); ?>"></script>
</body>
</html>
