<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'helpers/booking_helper.php';
require_once APPPATH.'models/Billing_policy_model.php';

class Operations_model extends CI_Model
{
    private $booking_conversion_error = '';
    private $contract_action_error = '';

    public function get_contract_action_error()
    {
        return $this->contract_action_error;
    }

    public function get_booking_conversion_error()
    {
        return $this->booking_conversion_error;
    }

    public function modules()
    {
        return array(
            'bookings' => array(
                'title' => 'Booking',
                'subtitle' => 'Follow-up request booking dari landing page.',
                'table' => 'gh_booking_requests',
                'base' => 'booking',
                'readonly' => TRUE,
                'status_field' => 'status',
                'columns' => array('created_at' => 'Tanggal', 'fullname' => 'Nama', 'phone' => 'WhatsApp', 'room_interest' => 'Minat', 'status' => 'Status'),
                'fields' => array(),
            ),
            'contracts' => array(
                'title' => 'Kontrak',
                'subtitle' => 'Perjanjian sewa, masa kontrak, deposit, dan status dokumen.',
                'table' => 'gh_contracts',
                'base' => 'contracts',
                'status_field' => 'status',
                'number_field' => 'contract_no',
                'number_prefix' => 'GH-CTR',
                'columns' => array('contract_no' => 'No Kontrak', 'tenant_id' => 'Penghuni', 'room_id' => 'Kamar', 'start_date' => 'Mulai', 'end_date' => 'Berakhir', 'status' => 'Status'),
                'fields' => array(
                    'contract_no' => array('label' => 'No Kontrak', 'type' => 'text', 'placeholder' => 'Otomatis jika kosong'),
                    'tenant_id' => array('label' => 'Penghuni', 'type' => 'tenant'),
                    'stay_id' => array('label' => 'Masa Tinggal Penghuni', 'type' => 'stay', 'required' => TRUE),
                    'room_id' => array('label' => 'Kamar', 'type' => 'room'),
                    'start_date' => array('label' => 'Mulai Sewa', 'type' => 'date'),
                    'end_date' => array('label' => 'Akhir Sewa', 'type' => 'date'),
                    'monthly_price' => array('label' => 'Harga Sewa', 'type' => 'money'),
                    'deposit_amount' => array('label' => 'Deposit', 'type' => 'money'),
                    'status' => array('label' => 'Status', 'type' => 'select', 'options' => array('draft' => 'Draft', 'active' => 'Active', 'expiring' => 'Expiring', 'ended' => 'Ended', 'cancelled' => 'Cancelled')),
                    'signed_at' => array('label' => 'Tanggal TTD', 'type' => 'date'),
                    'notes' => array('label' => 'Catatan', 'type' => 'textarea'),
                ),
            ),
            'documents' => array(
                'title' => 'Dokumen',
                'subtitle' => 'Checklist dokumen pendukung: KTP, kontrak, surat pernyataan, serah-terima, inventaris.',
                'table' => 'gh_contract_documents',
                'base' => 'documents',
                'status_field' => 'status',
                'columns' => array('document_title' => 'Dokumen', 'document_type' => 'Tipe', 'tenant_id' => 'Penghuni', 'status' => 'Status', 'received_at' => 'Diterima'),
                'fields' => array(
                    'contract_id' => array('label' => 'Kontrak Penghuni', 'type' => 'contract'),
                    'tenant_id' => array('label' => 'Penghuni', 'type' => 'tenant'),
                    'document_type' => array('label' => 'Tipe Dokumen', 'type' => 'select', 'options' => array('ktp' => 'KTP', 'contract' => 'Kontrak', 'statement' => 'Surat Pernyataan', 'handover' => 'Serah Terima', 'inventory' => 'Inventaris', 'vehicle' => 'Kendaraan', 'other' => 'Lainnya')),
                    'document_title' => array('label' => 'Judul Dokumen', 'type' => 'text', 'required' => TRUE),
                    'file_path' => array('label' => 'Path/Link File', 'type' => 'text'),
                    'status' => array('label' => 'Status', 'type' => 'select', 'options' => array('needed' => 'Needed', 'received' => 'Received', 'verified' => 'Verified', 'rejected' => 'Rejected')),
                    'received_at' => array('label' => 'Tanggal Diterima', 'type' => 'date'),
                    'notes' => array('label' => 'Catatan', 'type' => 'textarea'),
                ),
            ),
            'deposits' => array(
                'title' => 'Deposit',
                'subtitle' => 'Ledger deposit masuk, potongan, refund, dan adjustment.',
                'table' => 'gh_deposit_transactions',
                'base' => 'deposits',
                'status_field' => 'transaction_type',
                'sum_field' => 'amount',
                'columns' => array('transaction_date' => 'Tanggal', 'tenant_id' => 'Penghuni', 'room_id' => 'Kamar', 'transaction_type' => 'Tipe', 'amount' => 'Nominal'),
                'fields' => array(
                    'tenant_id' => array('label' => 'Penghuni', 'type' => 'tenant'),
                    'stay_id' => array('label' => 'Masa Tinggal Penghuni', 'type' => 'stay'),
                    'room_id' => array('label' => 'Kamar', 'type' => 'room'),
                    'transaction_date' => array('label' => 'Tanggal', 'type' => 'date'),
                    'transaction_type' => array('label' => 'Tipe', 'type' => 'select', 'options' => array('received' => 'Deposit Masuk', 'deduction' => 'Potongan', 'refund' => 'Refund', 'adjustment' => 'Adjustment')),
                    'amount' => array('label' => 'Nominal', 'type' => 'money', 'required' => TRUE),
                    'method' => array('label' => 'Metode', 'type' => 'text'),
                    'reference_no' => array('label' => 'Referensi', 'type' => 'text'),
                    'description' => array('label' => 'Deskripsi', 'type' => 'textarea'),
                    'refund_inspection_confirmed' => array('label' => 'Untuk refund: pemeriksaan akhir telah selesai dan seluruh kewajiban telah diperiksa', 'type' => 'checkbox', 'default' => 0),
                    'refund_reason' => array('label' => 'Alasan / referensi pemeriksaan akhir (wajib untuk refund)', 'type' => 'textarea'),
                ),
            ),
            'receivables' => array(
                'title' => 'Piutang',
                'subtitle' => 'Monitoring tagihan belum lunas dan aging jatuh tempo.',
                'table' => 'gh_room_bills',
                'base' => 'receivables',
                'readonly' => TRUE,
                'columns' => array('period_label' => 'Periode', 'room_label' => 'Kamar', 'tenant_name_snapshot' => 'Penghuni', 'due_date' => 'Jatuh Tempo', 'remaining_amount' => 'Sisa', 'aging_bucket' => 'Aging'),
                'fields' => array(),
            ),
            'billing' => array(
                'title' => 'Billing',
                'subtitle' => 'Monitoring tagihan kamar per periode sebelum masuk pembayaran.',
                'table' => 'gh_room_bills',
                'base' => 'billing',
                'readonly' => TRUE,
                'status_field' => 'bill_status',
                'columns' => array('period_label' => 'Periode', 'room_label' => 'Kamar', 'tenant_name_snapshot' => 'Penghuni', 'base_price' => 'Tagihan', 'paid_total' => 'Terbayar', 'bill_status' => 'Status'),
                'fields' => array(),
            ),
            'maintenance' => array(
                'title' => 'Maintenance',
                'subtitle' => 'Ticket kerusakan dengan PIC, deadline, biaya, dan status.',
                'table' => 'gh_maintenance_tickets',
                'base' => 'maintenance',
                'status_field' => 'status',
                'sum_field' => 'cost_amount',
                'number_field' => 'ticket_no',
                'number_prefix' => 'GH-MNT',
                'columns' => array('ticket_no' => 'Ticket', 'room_id' => 'Kamar', 'category' => 'Kategori', 'issue_title' => 'Masalah', 'pic_name' => 'PIC', 'status' => 'Status', 'deadline_at' => 'Deadline'),
                'fields' => array(
                    'ticket_no' => array('label' => 'No Ticket', 'type' => 'text', 'placeholder' => 'Otomatis jika kosong'),
                    'room_id' => array('label' => 'Kamar', 'type' => 'room'),
                    'tenant_id' => array('label' => 'Penghuni', 'type' => 'tenant'),
                    'category' => array('label' => 'Kategori', 'type' => 'select', 'options' => array('ac' => 'AC', 'electric' => 'Listrik', 'plumbing' => 'Plumbing', 'key' => 'Kunci', 'furniture' => 'Furniture', 'cleaning' => 'Cleaning', 'internet' => 'Internet', 'other' => 'Lainnya')),
                    'issue_title' => array('label' => 'Judul Masalah', 'type' => 'text', 'required' => TRUE),
                    'issue_detail' => array('label' => 'Detail Masalah', 'type' => 'textarea'),
                    'pic_name' => array('label' => 'PIC', 'type' => 'text'),
                    'vendor_name' => array('label' => 'Vendor', 'type' => 'text'),
                    'priority' => array('label' => 'Prioritas', 'type' => 'select', 'options' => array('low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent')),
                    'status' => array('label' => 'Status', 'type' => 'select', 'options' => array('open' => 'Open', 'assigned' => 'Assigned', 'in_progress' => 'In Progress', 'done' => 'Done', 'cancelled' => 'Cancelled')),
                    'reported_at' => array('label' => 'Tanggal Lapor', 'type' => 'date'),
                    'deadline_at' => array('label' => 'Deadline', 'type' => 'date'),
                    'finished_at' => array('label' => 'Selesai', 'type' => 'date'),
                    'cost_amount' => array('label' => 'Biaya', 'type' => 'money'),
                    'notes' => array('label' => 'Catatan', 'type' => 'textarea'),
                ),
            ),
            'inventory' => array(
                'title' => 'Inventaris',
                'subtitle' => 'Kontrol item kamar, jumlah aktual, kondisi awal/akhir, dan biaya pemulihan.',
                'table' => 'gh_inventory_checks',
                'base' => 'inventory',
                'status_field' => 'condition_status',
                'sum_field' => 'repair_cost',
                'columns' => array('check_date' => 'Tanggal', 'room_id' => 'Kamar', 'item_name_snapshot' => 'Item', 'check_stage' => 'Tahap', 'actual_qty' => 'Aktual', 'condition_status' => 'Kondisi'),
                'fields' => array(
                    'room_id' => array('label' => 'Kamar', 'type' => 'room'),
                    'item_id' => array('label' => 'Item Master ID', 'type' => 'number'),
                    'item_name_snapshot' => array('label' => 'Nama Item', 'type' => 'text', 'required' => TRUE),
                    'check_stage' => array('label' => 'Tahap', 'type' => 'select', 'options' => array('check_in' => 'Check-in', 'routine' => 'Routine', 'check_out' => 'Check-out')),
                    'standard_qty' => array('label' => 'Qty Standar', 'type' => 'number'),
                    'actual_qty' => array('label' => 'Qty Aktual', 'type' => 'number'),
                    'condition_status' => array('label' => 'Kondisi', 'type' => 'select', 'options' => array('good' => 'Baik', 'needs_repair' => 'Perlu Perbaikan', 'damaged' => 'Rusak', 'missing' => 'Hilang')),
                    'check_date' => array('label' => 'Tanggal Cek', 'type' => 'date'),
                    'officer_name' => array('label' => 'Petugas', 'type' => 'text'),
                    'repair_cost' => array('label' => 'Biaya Pemulihan', 'type' => 'money'),
                    'notes' => array('label' => 'Catatan', 'type' => 'textarea'),
                ),
            ),
            'complaints' => array(
                'title' => 'Komplain',
                'subtitle' => 'Ticket keluhan penghuni dengan SLA, PIC, dan penyelesaian.',
                'table' => 'gh_complaints',
                'base' => 'complaints',
                'status_field' => 'status',
                'number_field' => 'ticket_no',
                'number_prefix' => 'GH-CMP',
                'columns' => array('ticket_no' => 'Ticket', 'tenant_id' => 'Penghuni', 'room_id' => 'Kamar', 'category' => 'Kategori', 'complaint_title' => 'Keluhan', 'status' => 'Status', 'deadline_at' => 'Deadline'),
                'fields' => array(
                    'ticket_no' => array('label' => 'No Ticket', 'type' => 'text', 'placeholder' => 'Otomatis jika kosong'),
                    'tenant_id' => array('label' => 'Penghuni', 'type' => 'tenant'),
                    'room_id' => array('label' => 'Kamar', 'type' => 'room'),
                    'category' => array('label' => 'Kategori', 'type' => 'select', 'options' => array('facility' => 'Fasilitas', 'noise' => 'Kebisingan', 'billing' => 'Billing', 'security' => 'Keamanan', 'cleanliness' => 'Kebersihan', 'other' => 'Lainnya')),
                    'complaint_title' => array('label' => 'Judul Keluhan', 'type' => 'text', 'required' => TRUE),
                    'complaint_detail' => array('label' => 'Detail Keluhan', 'type' => 'textarea'),
                    'pic_name' => array('label' => 'PIC', 'type' => 'text'),
                    'status' => array('label' => 'Status', 'type' => 'select', 'options' => array('open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'cancelled' => 'Cancelled')),
                    'reported_at' => array('label' => 'Tanggal Lapor', 'type' => 'date'),
                    'deadline_at' => array('label' => 'Deadline', 'type' => 'date'),
                    'resolved_at' => array('label' => 'Selesai', 'type' => 'date'),
                    'resolution_notes' => array('label' => 'Solusi/Catatan', 'type' => 'textarea'),
                ),
            ),
            'facilities' => array(
                'title' => 'Fasilitas',
                'subtitle' => 'Checklist fasilitas kamar dan area umum.',
                'table' => 'gh_facility_checks',
                'base' => 'facilities',
                'status_field' => 'followup_status',
                'columns' => array('check_date' => 'Tanggal', 'facility_name' => 'Fasilitas', 'location_label' => 'Lokasi', 'condition_status' => 'Kondisi', 'followup_status' => 'Follow-up', 'deadline_at' => 'Deadline'),
                'fields' => array(
                    'facility_name' => array('label' => 'Fasilitas', 'type' => 'text', 'required' => TRUE),
                    'location_label' => array('label' => 'Lokasi', 'type' => 'text'),
                    'room_id' => array('label' => 'Kamar', 'type' => 'room'),
                    'quantity' => array('label' => 'Jumlah', 'type' => 'number'),
                    'condition_status' => array('label' => 'Kondisi', 'type' => 'select', 'options' => array('good' => 'Baik', 'needs_maintenance' => 'Perlu Maintenance', 'damaged' => 'Rusak')),
                    'followup_status' => array('label' => 'Status Tindak Lanjut', 'type' => 'select', 'options' => array('open' => 'Open', 'in_progress' => 'In Progress', 'done' => 'Done', 'cancelled' => 'Cancelled')),
                    'check_date' => array('label' => 'Tanggal Cek', 'type' => 'date'),
                    'officer_name' => array('label' => 'Petugas', 'type' => 'text'),
                    'pic_name' => array('label' => 'PIC', 'type' => 'text'),
                    'deadline_at' => array('label' => 'Deadline', 'type' => 'date'),
                    'photo_link' => array('label' => 'Foto/Link', 'type' => 'text'),
                    'issue_notes' => array('label' => 'Catatan Masalah', 'type' => 'textarea'),
                ),
            ),
            'housekeeping' => array(
                'title' => 'Housekeeping',
                'subtitle' => 'Checklist kebersihan harian/mingguan kamar dan area umum.',
                'table' => 'gh_housekeeping_checks',
                'base' => 'housekeeping',
                'status_field' => 'status',
                'columns' => array('check_date' => 'Tanggal', 'location_label' => 'Lokasi', 'area_name' => 'Area', 'check_item' => 'Item', 'condition_status' => 'Kondisi', 'status' => 'Status'),
                'fields' => array(
                    'check_date' => array('label' => 'Tanggal', 'type' => 'date'),
                    'location_label' => array('label' => 'Lokasi/Kamar', 'type' => 'text', 'required' => TRUE),
                    'area_name' => array('label' => 'Area', 'type' => 'text'),
                    'check_item' => array('label' => 'Checklist', 'type' => 'text', 'required' => TRUE),
                    'condition_status' => array('label' => 'Kondisi', 'type' => 'select', 'options' => array('good' => 'Baik', 'needs_attention' => 'Perlu Perhatian', 'dirty' => 'Kotor', 'damaged' => 'Rusak')),
                    'status' => array('label' => 'Status', 'type' => 'select', 'options' => array('open' => 'Open', 'done' => 'Done', 'cancelled' => 'Cancelled')),
                    'officer_name' => array('label' => 'Petugas', 'type' => 'text'),
                    'notes' => array('label' => 'Catatan', 'type' => 'textarea'),
                ),
            ),
            'audits' => array(
                'title' => 'Audit Bulanan',
                'subtitle' => 'Audit rutin owner untuk kamar, fasilitas, keamanan, dan kebersihan.',
                'table' => 'gh_monthly_audits',
                'base' => 'audits',
                'status_field' => 'status',
                'columns' => array('period_label' => 'Periode', 'location_label' => 'Lokasi', 'audit_item' => 'Item Audit', 'result_status' => 'Hasil', 'status' => 'Status', 'deadline_at' => 'Deadline'),
                'fields' => array(
                    'audit_date' => array('label' => 'Tanggal Audit', 'type' => 'date'),
                    'period_label' => array('label' => 'Periode', 'type' => 'text'),
                    'location_label' => array('label' => 'Kamar/Area', 'type' => 'text', 'required' => TRUE),
                    'audit_item' => array('label' => 'Item Audit', 'type' => 'text', 'required' => TRUE),
                    'result_status' => array('label' => 'Hasil', 'type' => 'select', 'options' => array('ok' => 'OK', 'finding' => 'Temuan', 'critical' => 'Critical')),
                    'finding_notes' => array('label' => 'Temuan', 'type' => 'textarea'),
                    'action_plan' => array('label' => 'Tindakan', 'type' => 'textarea'),
                    'pic_name' => array('label' => 'PIC', 'type' => 'text'),
                    'deadline_at' => array('label' => 'Deadline', 'type' => 'date'),
                    'status' => array('label' => 'Status', 'type' => 'select', 'options' => array('open' => 'Open', 'in_progress' => 'In Progress', 'done' => 'Done', 'cancelled' => 'Cancelled')),
                ),
            ),
            'finance' => array(
                'title' => 'Keuangan',
                'subtitle' => 'Biaya operasional, maintenance, vendor, dan pengeluaran owner.',
                'table' => 'gh_expenses',
                'base' => 'finance',
                'status_field' => 'payment_status',
                'sum_field' => 'amount',
                'columns' => array('expense_date' => 'Tanggal', 'category' => 'Kategori', 'description' => 'Keterangan', 'amount' => 'Nominal', 'payment_status' => 'Status', 'vendor_name' => 'Vendor'),
                'fields' => array(
                    'expense_date' => array('label' => 'Tanggal', 'type' => 'date'),
                    'category' => array('label' => 'Kategori', 'type' => 'select', 'options' => array('maintenance' => 'Maintenance', 'operational' => 'Operasional', 'utility' => 'Utility', 'salary' => 'Gaji', 'supplies' => 'Supplies', 'tax' => 'Pajak', 'other' => 'Lainnya')),
                    'description' => array('label' => 'Keterangan', 'type' => 'text', 'required' => TRUE),
                    'property_id' => array('label' => 'Properti ID', 'type' => 'number'),
                    'room_id' => array('label' => 'Kamar', 'type' => 'room'),
                    'amount' => array('label' => 'Nominal', 'type' => 'money', 'required' => TRUE),
                    'payment_status' => array('label' => 'Status Pembayaran', 'type' => 'select', 'options' => array('unpaid' => 'Unpaid', 'paid' => 'Paid', 'cancelled' => 'Cancelled')),
                    'vendor_name' => array('label' => 'Vendor/Penerima', 'type' => 'text'),
                    'reference_no' => array('label' => 'Referensi', 'type' => 'text'),
                    'notes' => array('label' => 'Catatan', 'type' => 'textarea'),
                ),
            ),
            'notifications' => array(
                'title' => 'WhatsApp',
                'subtitle' => 'Template dan draft reminder WhatsApp/manual notification.',
                'table' => 'gh_notification_queue',
                'base' => 'notifications',
                'status_field' => 'status',
                'columns' => array('scheduled_at' => 'Jadwal', 'recipient_name' => 'Penerima', 'recipient_phone' => 'WhatsApp', 'subject' => 'Subjek', 'status' => 'Status'),
                'fields' => array(
                    'recipient_name' => array('label' => 'Nama Penerima', 'type' => 'text'),
                    'recipient_phone' => array('label' => 'WhatsApp', 'type' => 'text'),
                    'subject' => array('label' => 'Subjek', 'type' => 'text'),
                    'message_body' => array('label' => 'Isi Pesan', 'type' => 'textarea', 'required' => TRUE),
                    'status' => array('label' => 'Status', 'type' => 'select', 'options' => array('draft' => 'Draft', 'queued' => 'Queued', 'sent' => 'Sent', 'failed' => 'Failed', 'cancelled' => 'Cancelled')),
                    'scheduled_at' => array('label' => 'Jadwal Kirim', 'type' => 'datetime'),
                    'sent_at' => array('label' => 'Terkirim', 'type' => 'datetime'),
                    'error_message' => array('label' => 'Error', 'type' => 'textarea'),
                ),
            ),
            'templates' => array(
                'title' => 'Template Dokumen',
                'subtitle' => 'Template kontrak, surat pernyataan, tata tertib, dan serah-terima.',
                'table' => 'gh_document_templates',
                'base' => 'templates',
                'status_field' => 'document_type',
                'columns' => array('title' => 'Judul', 'document_type' => 'Tipe', 'template_key' => 'Key', 'is_active' => 'Aktif'),
                'fields' => array(
                    'template_key' => array('label' => 'Template Key', 'type' => 'text', 'required' => TRUE),
                    'title' => array('label' => 'Judul', 'type' => 'text', 'required' => TRUE),
                    'document_type' => array('label' => 'Tipe', 'type' => 'select', 'options' => array('contract' => 'Kontrak', 'statement' => 'Surat Pernyataan', 'rules' => 'Tata Tertib', 'handover' => 'Serah Terima', 'tenant_data' => 'Data Penghuni', 'receipt' => 'Receipt', 'other' => 'Lainnya')),
                    'body_template' => array('label' => 'Isi Template', 'type' => 'textarea', 'required' => TRUE),
                    'is_active' => array('label' => 'Aktif', 'type' => 'checkbox'),
                ),
            ),
            'users' => array(
                'title' => 'Admin & Role',
                'subtitle' => 'Monitoring akun admin dan role. Perubahan password tetap dilakukan terpisah.',
                'table' => 'gh_admin_users',
                'base' => 'users',
                'readonly' => TRUE,
                'status_field' => 'role',
                'columns' => array('fullname' => 'Nama', 'username' => 'Username', 'email' => 'Email', 'role' => 'Role', 'is_active' => 'Aktif', 'last_login_at' => 'Login Terakhir'),
                'fields' => array(),
            ),
            'reports' => array(
                'title' => 'Laporan',
                'subtitle' => 'Ringkasan owner dari occupancy, pembayaran, piutang, maintenance, komplain, dan biaya.',
                'table' => '',
                'base' => 'reports',
                'readonly' => TRUE,
                'columns' => array('metric' => 'Metrik', 'value' => 'Nilai', 'notes' => 'Catatan'),
                'fields' => array(),
            ),
        );
    }

    public function get_config($key)
    {
        $modules = $this->modules();
        return isset($modules[$key]) ? $modules[$key] : null;
    }

    public function get_rows($key, array $filters = array())
    {
        if ($key === 'receivables') {
            return $this->filter_operational_rows($this->get_receivables(), $filters);
        }
        if ($key === 'billing') {
            return $this->filter_operational_rows($this->get_billing_rows(), $filters);
        }
        if ($key === 'reports') {
            $rows = $this->get_report_rows(! empty($filters['period']) ? $filters['period'] : '2026-11');
            if (! empty($filters['q'])) $rows = array_values(array_filter($rows, function ($row) use ($filters) { return mb_stripos(implode(' ', array_map('strval', $row)), $filters['q'], 0, 'UTF-8') !== FALSE; }));
            return $rows;
        }

        $config = $this->get_config($key);
        if (! $config || empty($config['table']) || ! $this->db->table_exists($config['table'])) {
            return array();
        }

        $this->db->from($config['table']);
        if($key==='bookings' && $this->db->field_exists('archived_at','gh_booking_requests')) {
            $archive=$filters['archive']??'active';
            if($archive==='archived') $this->db->where('archived_at IS NOT NULL',NULL,FALSE);
            elseif($archive!=='all') $this->db->where('archived_at',NULL);
        }
        if (! empty($filters['period']) && $this->valid_period($filters['period']) && in_array($key, array('finance', 'deposits'), TRUE)) {
            $dateField = $key === 'finance' ? 'expense_date' : 'transaction_date';
            $this->db->where($dateField . ' >=', $filters['period'] . '-01')->where($dateField . ' <=', date('Y-m-t', strtotime($filters['period'] . '-01')));
        }
        if (! empty($filters['status']) && ! empty($config['status_field'])) {
            $this->db->where($config['status_field'], $filters['status']);
        }

        $this->db->order_by('id', 'DESC');
        $rows = $this->db->get()->result_array();
        if (! empty($filters['q'])) {
            $tenants = $this->get_tenants();
            $rooms = $this->get_rooms();
            $rows = array_values(array_filter($rows, function ($row) use ($filters, $tenants, $rooms) {
                $text = implode(' ', array_map('strval', $row));
                if (isset($row['tenant_id'])) $text .= ' ' . $this->label_tenant($row['tenant_id'], $tenants);
                if (isset($row['room_id'])) $text .= ' ' . $this->label_room($row['room_id'], $rooms);
                foreach ($tenants as $tenant) if (isset($row['tenant_id']) && (int) $tenant['id'] === (int) $row['tenant_id']) $text .= ' ' . $tenant['phone'];
                return stripos($text, $filters['q']) !== FALSE;
            }));
        }
        if($key==='bookings' && $this->db->table_exists('gh_booking_demo_registry')) {
            $registered=array_column($this->db->select('booking_id')->get('gh_booking_demo_registry')->result_array(),'booking_id');
            foreach($rows as &$row) $row['is_demo']=in_array($row['id'],$registered); unset($row);
        }
        if($key==='bookings') {foreach($rows as &$row)$row['wa_url']=gh_booking_whatsapp_url($row['phone']);unset($row);}
        return $rows;
    }

    public function booking_retention_counts(array $filters=array())
    {
        $filters['archive']='all';$rows=$this->get_rows('bookings',$filters);$out=array('active'=>0,'archived'=>0,'total'=>count($rows));
        foreach($rows as $r) $out[empty($r['archived_at'])?'active':'archived']++;
        return $out;
    }

    public function get_metrics($key, array $filters = array())
    {
        $config = $this->get_config($key);
        $metrics = array('total' => 0, 'open' => 0, 'done' => 0, 'amount' => 0);
        if (! $config) {
            return $metrics;
        }
        if ($key === 'reports') {
            return $metrics;
        }
        if($key==='bookings') {
            $rows=$this->get_rows($key,$filters);$metrics['total']=count($rows);
            foreach($rows as $r) { if(in_array($r['status'],array('new','contacted','survey'),TRUE))$metrics['open']++;if(in_array($r['status'],array('converted','done'),TRUE))$metrics['done']++; }
            return $metrics;
        }
        if (in_array($key, array('billing', 'receivables', 'finance', 'deposits'), TRUE)) {
            $rows = $this->get_rows($key, $filters);
            $metrics['total'] = count($rows);
            foreach ($rows as $row) {
                $status = isset($config['status_field'], $row[$config['status_field']]) ? $row[$config['status_field']] : '';
                if (in_array($status, array('open','draft','needed','unpaid','partial'), TRUE)) $metrics['open']++;
                if (in_array($status, array('done','active','verified','paid','resolved'), TRUE)) $metrics['done']++;
                if ($key === 'billing') $metrics['amount'] += (int) $row['base_price'];
                elseif ($key === 'receivables') $metrics['amount'] += (int) $row['remaining_amount'];
                elseif ($key === 'deposits') $metrics['amount'] += in_array($row['transaction_type'], array('received','adjustment'), TRUE) ? (int) $row['amount'] : -(int) $row['amount'];
                elseif ($row['payment_status'] !== 'cancelled') $metrics['amount'] += (int) $row['amount'];
            }
            return $metrics;
        }
        if ($key === 'receivables') {
            $rows = $this->get_receivables();
            $metrics['total'] = count($rows);
            foreach ($rows as $row) {
                $metrics['amount'] += (int) $row['remaining_amount'];
            }
            return $metrics;
        }
        if (empty($config['table']) || ! $this->db->table_exists($config['table'])) {
            return $metrics;
        }

        $metrics['total'] = (int) $this->db->count_all_results($config['table']);
        if (! empty($config['status_field'])) {
            $field = $config['status_field'];
            $metrics['open'] = (int) $this->db->where_in($field, array('open', 'draft', 'needed', 'unpaid', 'received'))->count_all_results($config['table']);
            $metrics['done'] = (int) $this->db->where_in($field, array('done', 'active', 'verified', 'paid', 'resolved', 'sent'))->count_all_results($config['table']);
        }
        if (! empty($config['sum_field'])) {
            $row = $this->db->select('COALESCE(SUM(' . $config['sum_field'] . '), 0) AS total_amount', FALSE)->get($config['table'])->row_array();
            $metrics['amount'] = $row ? (int) $row['total_amount'] : 0;
        }

        return $metrics;
    }

    private function filter_operational_rows(array $rows, array $filters)
    {
        return array_values(array_filter($rows, function ($row) use ($filters) {
            if (! empty($filters['status']) && isset($row['bill_status']) && $row['bill_status'] !== $filters['status']) return FALSE;
            if (! empty($filters['period']) && isset($row['period_key']) && $row['period_key'] !== $filters['period']) return FALSE;
            if (! empty($filters['q']) && stripos(implode(' ', array_map('strval', $row)), $filters['q']) === FALSE) return FALSE;
            return TRUE;
        }));
    }

    public function valid_period($period)
    {
        return preg_match('/^(\d{4})-(\d{2})$/', (string) $period, $parts) && checkdate((int) $parts[2], 1, (int) $parts[1]);
    }

    public function get_row($key, $id)
    {
        $config = $this->get_config($key);
        if (! $config || empty($config['table']) || ! $this->db->table_exists($config['table'])) {
            return null;
        }
        return $this->db->where('id', (int) $id)->get($config['table'])->row_array();
    }

    public function save($key, array $payload)
    {
        $config = $this->get_config($key);
        if (! $config || ! empty($config['readonly']) || empty($config['table']) || ! $this->db->table_exists($config['table'])) {
            return FALSE;
        }

        $id = (int) (isset($payload['id']) ? $payload['id'] : 0);
        $data = array();
        foreach ($config['fields'] as $field => $meta) {
            $value = isset($payload[$field]) ? $payload[$field] : null;
            if ((! empty($meta['required']) && trim((string) $value) === '') || ($meta['type'] === 'money' && strpos((string) $value, '-') !== FALSE) || ($meta['type'] === 'date' && $value !== null && $value !== '' && ! $this->valid_date($value)) || ($meta['type'] === 'select' && ! isset($meta['options'][$value]))) {
                return FALSE;
            }
            $data[$field] = $this->normalize_value($value, $meta);
            if ($meta['type'] === 'money' && $data[$field] < 0) return FALSE;
            if ($meta['type'] === 'number' && $value !== null && $value !== '' && (! is_scalar($value) || ! preg_match('/^\d+$/', (string) $value))) return FALSE;
            if (in_array($meta['type'], array('stay','contract'), TRUE) && $value !== null && $value !== '' && (! is_scalar($value) || ! preg_match('/^[1-9]\d*$/', (string) $value))) return FALSE;
            if (! empty($meta['required']) && $meta['type'] === 'money' && $data[$field] <= 0) return FALSE;
            if ($meta['type'] === 'room' && $data[$field] && $this->db->where('id', $data[$field])->count_all_results('gh_rooms') === 0) return FALSE;
            if ($meta['type'] === 'tenant' && $data[$field] && $this->db->where('id', $data[$field])->count_all_results('gh_tenants') === 0) return FALSE;
        }
        if ($key === 'documents') {
            $document = $id ? $this->get_row('documents', $id) : null;
            $data['file_path'] = $document ? $document['file_path'] : null;
            if (! empty($data['contract_id'])) {
                $contract = $this->get_row('contracts', $data['contract_id']);
                if (! $contract || (int) $contract['tenant_id'] !== (int) $data['tenant_id']) return FALSE;
            }
        }

        if ($key === 'contracts') {
            $old = $id ? $this->get_row($key, $id) : null;
            $stay = ! empty($data['stay_id']) ? $this->db->where('id', $data['stay_id'])->get('gh_tenant_stays')->row_array() : null;
            if (! $stay || (int) $stay['tenant_id'] !== (int) $data['tenant_id'] || (int) $stay['room_id'] !== (int) $data['room_id'] || ! $this->valid_date($data['start_date']) || ! $this->valid_date($data['end_date']) || $data['end_date'] < $data['start_date'] || $data['monthly_price'] <= 0 || (! $old && $data['status'] !== 'draft') || ($old && $data['status'] !== $old['status'])) {
                return FALSE;
            }
            if ($old && $old['status'] !== 'draft') {
                foreach (array('tenant_id', 'stay_id', 'room_id', 'start_date', 'end_date', 'monthly_price', 'deposit_amount') as $fixed) {
                    if ((string) $data[$fixed] !== (string) $old[$fixed]) {
                        return FALSE;
                    }
                }
            }
            if ($this->db->where('stay_id', $data['stay_id'])->where('id !=', $id)->where('status !=', 'cancelled')->where('start_date <=', $data['end_date'])->where('end_date >=', $data['start_date'])->count_all_results('gh_contracts') > 0) return FALSE;
        }
        if ($key === 'deposits') {
            return $this->save_deposit($id, $data);
        }

        if (! empty($config['number_field']) && empty($data[$config['number_field']])) {
            $data[$config['number_field']] = $this->next_number($config['number_prefix']);
        }

        if ($key === 'maintenance' && $data['status'] === 'done' && ! $this->valid_date($data['finished_at'])) return FALSE;
        if ($key === 'complaints' && $id && $data['status'] === 'resolved' && $this->db->field_exists('complaint_id', 'gh_maintenance_tickets') && $this->db->where('complaint_id', $id)->where_not_in('status', array('done','cancelled'))->count_all_results('gh_maintenance_tickets') > 0) return FALSE;
        if ($key === 'finance' && $id) {
            $sourceExpense = $this->get_row('finance', $id);
            if ($sourceExpense && ! empty($sourceExpense['maintenance_ticket_id']) && (int) $data['amount'] !== (int) $sourceExpense['amount']) return FALSE;
        }
        if ($key === 'maintenance' && $id && $this->db->field_exists('maintenance_ticket_id', 'gh_expenses')) {
            $paidExpense = $this->db->where('maintenance_ticket_id', $id)->where('payment_status', 'paid')->get('gh_expenses')->row_array();
            if ($paidExpense && ($data['status'] !== 'done' || (int) $paidExpense['amount'] !== (int) $data['cost_amount'])) return FALSE;
        }
        $this->db->trans_start();
        if ($id > 0) {
            $this->db->where('id', $id)->update($config['table'], $data);
        } else {
            $this->db->insert($config['table'], $data);
            $id = (int) $this->db->insert_id();
        }
        if ($key === 'maintenance' && $this->db->field_exists('maintenance_ticket_id', 'gh_expenses')) {
            $ticket = $this->get_row('maintenance', $id);
            $expense = $this->db->where('maintenance_ticket_id', $id)->get('gh_expenses')->row_array();
            if ($data['status'] === 'done' && $data['cost_amount'] > 0) {
                $expenseData = array('maintenance_ticket_id' => $id, 'expense_date' => $data['finished_at'], 'category' => 'maintenance', 'description' => 'Maintenance ' . $data['ticket_no'] . ': ' . $data['issue_title'], 'room_id' => $data['room_id'], 'amount' => $data['cost_amount'], 'vendor_name' => $data['vendor_name']);
                if ($expense) $this->db->where('id', $expense['id'])->update('gh_expenses', $expenseData);
                else { $expenseData['payment_status'] = 'unpaid'; $this->db->insert('gh_expenses', $expenseData); }
            } elseif ($expense && $expense['payment_status'] === 'unpaid') {
                $this->db->where('id', $expense['id'])->update('gh_expenses', array('payment_status' => 'cancelled'));
            }
            if ($ticket && ! empty($ticket['complaint_id']) && $data['status'] === 'done') $this->db->where('id', $ticket['complaint_id'])->update('gh_complaints', array('status' => 'resolved', 'resolved_at' => $data['finished_at']));
        }
        $this->db->trans_complete();
        return $this->db->trans_status() ? $id : FALSE;
    }

    public function escalate_complaint($id)
    {
        if (! $this->db->field_exists('complaint_id', 'gh_maintenance_tickets')) return FALSE;
        $this->db->trans_begin();
        $complaint = $this->db->query('SELECT * FROM gh_complaints WHERE id = ? FOR UPDATE', array((int) $id))->row_array();
        if (! $complaint || in_array($complaint['status'], array('resolved', 'cancelled'), TRUE)) { $this->db->trans_rollback(); return FALSE; }
        $existing = $this->db->where('complaint_id', $id)->get('gh_maintenance_tickets')->row_array();
        if (! $existing) {
            $this->db->insert('gh_maintenance_tickets', array('complaint_id' => $id, 'ticket_no' => $this->next_number('GH-MNT'), 'room_id' => $complaint['room_id'], 'tenant_id' => $complaint['tenant_id'], 'category' => 'other', 'issue_title' => $complaint['complaint_title'], 'issue_detail' => $complaint['complaint_detail'], 'pic_name' => $complaint['pic_name'], 'reported_at' => $complaint['reported_at'], 'deadline_at' => $complaint['deadline_at'], 'status' => 'open', 'notes' => 'Dari komplain ' . $complaint['ticket_no']));
        }
        $this->db->where('id', $id)->update('gh_complaints', array('status' => 'in_progress'));
        if (! $this->db->trans_status()) { $this->db->trans_rollback(); return FALSE; }
        $this->db->trans_commit();
        return TRUE;
    }

    public function update_booking_status($id, $status)
    {
        if (! in_array($status, array('new','contacted','survey','cancelled','done'), TRUE)) return FALSE;
        if($status==='done' && !$this->booking_done_available())return FALSE;
        $this->db->trans_begin();
        $booking = $this->db->query('SELECT * FROM gh_booking_requests WHERE id = ? FOR UPDATE', array((int) $id))->row_array();
        if (! $booking || !empty($booking['archived_at']) || $booking['status'] === 'converted') { $this->db->trans_rollback(); return FALSE; }
        if($booking['status']==='done'){ $this->db->trans_commit();return $status==='done'; }
        $this->db->where('id', (int) $id)->update('gh_booking_requests', array('status' => $status));
        if (! $this->db->trans_status()) { $this->db->trans_rollback(); return FALSE; }
        $this->db->trans_commit();
        return TRUE;
    }

    private function booking_done_available()
    {
        $col=$this->db->query("SHOW COLUMNS FROM gh_booking_requests LIKE 'status'")->row_array();
        return $col && strpos($col['Type'],"'done'")!==FALSE;
    }

    public function update_document_file($id, $filePath)
    {
        if (! $this->db->table_exists('gh_contract_documents') || (int) $id <= 0 || trim((string) $filePath) === '') {
            return FALSE;
        }

        return $this->db
            ->where('id', (int) $id)
            ->update('gh_contract_documents', array(
                'file_path' => $filePath,
                'status' => 'received',
                'received_at' => date('Y-m-d'),
            ));
    }

    public function delete_row($key, $id)
    {
        $config = $this->get_config($key);
        if (! $config || ! empty($config['readonly']) || empty($config['table']) || ! $this->db->table_exists($config['table'])) {
            return FALSE;
        }
        if (in_array($key, array('contracts', 'deposits'), TRUE)) {
            // Financial and contract history is retained; use cancellation/reversal.
            return FALSE;
        }
        if ($key === 'maintenance' && $this->db->field_exists('maintenance_ticket_id', 'gh_expenses') && $this->db->where('maintenance_ticket_id', $id)->count_all_results('gh_expenses') > 0) return FALSE;
        if ($key === 'complaints' && $this->db->field_exists('complaint_id', 'gh_maintenance_tickets') && $this->db->where('complaint_id', $id)->count_all_results('gh_maintenance_tickets') > 0) return FALSE;
        if ($key === 'finance') { $expense = $this->get_row('finance', $id); if ($expense && ! empty($expense['maintenance_ticket_id'])) return FALSE; }
        return $this->db->where('id', (int) $id)->delete($config['table']);
    }

    public function get_rooms()
    {
        if (! $this->db->table_exists('gh_rooms')) {
            return array();
        }
        return $this->db
            ->select('r.id, r.room_label, r.room_code, p.code AS property_code')
            ->from('gh_rooms r')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->order_by('p.code', 'ASC')
            ->order_by('r.room_code', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_tenants()
    {
        if (! $this->db->table_exists('gh_tenants')) {
            return array();
        }
        return $this->db
            ->select('id, fullname, phone')
            ->order_by('fullname', 'ASC')
            ->get('gh_tenants')
            ->result_array();
    }

    public function get_contract_options()
    {
        if (! $this->db->table_exists('gh_contracts')) return array();
        return $this->db->select('c.id, c.contract_no, c.tenant_id, c.room_id, c.status, t.fullname, r.room_label, p.code AS property_code')->from('gh_contracts c')
            ->join('gh_tenants t', 't.id=c.tenant_id', 'left')->join('gh_rooms r', 'r.id=c.room_id', 'left')->join('gh_properties p', 'p.id=r.property_id', 'left')->order_by('c.id', 'DESC')->get()->result_array();
    }

    public function get_stay_options()
    {
        if (! $this->db->table_exists('gh_tenant_stays')) return array();
        return $this->db->select('s.id, s.tenant_id, s.room_id, s.stay_status, s.check_in_date, s.check_out_date, t.fullname, r.room_label, p.code AS property_code')->from('gh_tenant_stays s')
            ->join('gh_tenants t', 't.id=s.tenant_id', 'left')->join('gh_rooms r', 'r.id=s.room_id', 'left')->join('gh_properties p', 'p.id=r.property_id', 'left')->order_by('s.id', 'DESC')->get()->result_array();
    }

    public function label_room($id, array $rooms)
    {
        foreach ($rooms as $room) {
            if ((int) $room['id'] === (int) $id) {
                return trim($room['property_code'] . ' - ' . $room['room_label']);
            }
        }
        return $id ? '#' . (int) $id : '-';
    }

    public function label_tenant($id, array $tenants)
    {
        foreach ($tenants as $tenant) {
            if ((int) $tenant['id'] === (int) $id) {
                return $tenant['fullname'];
            }
        }
        return $id ? '#' . (int) $id : '-';
    }

    public function convert_booking($bookingId, array $payload = array())
    {
        // Compatibility endpoint: completing a survey lead never creates resident administration.
        $this->booking_conversion_error = '';
        if (!$this->booking_done_available()) {
            $this->booking_conversion_error = 'Status Selesai belum tersedia. Jalankan migrasi additive booking done.';
            return FALSE;
        }
        $ok=$this->update_booking_status((int)$bookingId,'done');
        if(!$ok)$this->booking_conversion_error='Booking tidak dapat diselesaikan: periksa arsip, status konversi lama, atau data yang berubah.';
        return $ok;
    }
    public function generate_billing($periodKey)
    {
        $lock=$this->billing_lock_name();$held=$this->db->query('SELECT GET_LOCK(?,10) AS held',array($lock))->row_array();
        if(!$held||(int)$held['held']!==1)return FALSE;
        try{return $this->generate_billing_rows($periodKey);}finally{$this->db->query('SELECT RELEASE_LOCK(?)',array($lock));}
    }
    private function billing_lock_name(){return 'gh_issue_'.substr(hash('sha256',$this->db->database),0,48);}
    public function automatic_billing($asOf=NULL,$leadDays=7,$apply=FALSE)
    {
        $asOf=$asOf===NULL?Billing_policy_model::today():$asOf;
        if(!$this->valid_date($asOf)||!is_int($leadDays)||$leadDays<2||$leadDays>31)throw new RuntimeException('Tanggal dan horizon penerbitan otomatis tidak valid.');
        $policy=new Billing_policy_model($this->db);if(!$policy->ready())throw new RuntimeException('Kebijakan tagihan belum dimigrasikan.');
        $horizon=(new DateTimeImmutable($asOf,new DateTimeZone('Asia/Jakarta')))->modify('+'.$leadDays.' days')->format('Y-m-d');
        if(!$this->valid_date($horizon))throw new RuntimeException('Horizon penerbitan melewati batas tanggal yang didukung.');
        $result=array('ok'=>TRUE,'mode'=>$apply?'apply':'preview','timezone'=>'Asia/Jakarta','as_of'=>$asOf,'lead_days'=>$leadDays,'horizon'=>$horizon,'candidate_cases'=>0,'eligible_cases'=>0,'planned_bills'=>0,'created'=>0,'existing'=>0,'skipped'=>0,'errors'=>array());
        $lock=$this->billing_lock_name();$held=FALSE;
        if($apply){$row=$this->db->query('SELECT GET_LOCK(?,10) AS held',array($lock))->row_array();if(!$row||(int)$row['held']!==1)throw new RuntimeException('Penerbitan tagihan lain sedang berjalan. Coba kembali.');$held=TRUE;}
        try {
            $cases=$this->db->where('rolling_monthly',1)->where('billing_policy_version',Billing_policy_model::VERSION)->where_in('status',array('active','notice'))->order_by('id')->get('gh_resident_cases')->result_array();$result['candidate_cases']=count($cases);
            foreach($cases as $candidate){
                $createdBefore=$result['created'];
                if($apply)$this->db->trans_begin();
                try{
                    $c=$apply?$this->db->query('SELECT * FROM gh_resident_cases WHERE id=? FOR UPDATE',array($candidate['id']))->row_array():$candidate;
                    $s=$c&&!empty($c['stay_id'])?($apply?$this->db->query('SELECT * FROM gh_tenant_stays WHERE id=? FOR UPDATE',array($c['stay_id']))->row_array():$this->db->where('id',$c['stay_id'])->get('gh_tenant_stays')->row_array()):NULL;
                    $q=$c?$this->db->where('case_id',$c['id'])->get('gh_checkout_requests')->row_array():NULL;
                    if(!$c||!$s||$c['billing_policy_version']!==Billing_policy_model::VERSION||!(int)$c['rolling_monthly']||!in_array($c['status'],array('active','notice'),TRUE)||$s['stay_status']!=='active'||!empty($q['actual_date'])||(int)$c['tenant_id']!==(int)$s['tenant_id']||(int)$c['room_id']!==(int)$s['room_id']||!$this->valid_date($c['billing_anchor_date'])||$s['monthly_price']===NULL||(int)$s['monthly_price']<1){$result['skipped']++;if($apply)$this->db->trans_commit();continue;}
                    $result['eligible_cases']++;
                    for($offset=1;$offset<=1200;$offset++){
                        $due=Billing_policy_model::boundary($c['billing_anchor_date'],$offset);if(!$this->valid_date($due)||$due>$horizon)break;
                        $periodKey=substr($due,0,7);$period=$this->db->where('period_key',$periodKey)->get('gh_billing_periods')->row_array();
                        $exists=$period?($apply?$this->db->query('SELECT * FROM gh_room_bills WHERE period_id=? AND stay_id=? FOR UPDATE',array($period['id'],$s['id']))->row_array():$this->db->where('period_id',$period['id'])->where('stay_id',$s['id'])->get('gh_room_bills')->row_array()):NULL;
                        if($exists){$result['existing']++;continue;}
                        if($period){$ambiguous=$this->db->where('period_id',$period['id'])->where('room_id',$s['room_id'])->where('stay_id',NULL)->get('gh_room_bills')->result_array();foreach($ambiguous as $legacyBill)if(!in_array($legacyBill['bill_status'],array('empty','reserved','internal'),TRUE)||(int)$legacyBill['base_price']>0||(int)$legacyBill['paid_total']>0||$this->db->where('room_bill_id',$legacyBill['id'])->count_all_results('gh_payments'))throw new RuntimeException('Tagihan lama tanpa hubungan masa tinggal perlu direkonsiliasi.');}
                        $result['planned_bills']++;if(!$apply)continue;
                        if(!$period){$this->db->query('INSERT INTO gh_billing_periods (period_key,period_label,period_month,period_year,source_file) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)',array($periodKey,strtoupper(date('F Y',strtotime($periodKey.'-01'))),(int)substr($periodKey,5,2),(int)substr($periodKey,0,4),'AUTOMATIC_LIFECYCLE'));$period=$this->db->where('period_key',$periodKey)->get('gh_billing_periods')->row_array();if(!$period)throw new RuntimeException('Periode tagihan belum tersedia.');}
                        $price=(int)$s['monthly_price'];$data=array('period_id'=>$period['id'],'room_id'=>$s['room_id'],'stay_id'=>$s['id'],'tenant_id'=>$s['tenant_id'],'tenant_name_snapshot'=>$c['full_name'],'tenant_phone_snapshot'=>$c['phone'],'base_price'=>$price,'deposit_amount'=>0,'due_date'=>$due,'paid_total'=>0,'past_due_amount'=>$due<$asOf?$price:0,'future_due_amount'=>$due>=$asOf?$price:0,'vacant_amount'=>0,'bill_status'=>'unpaid','source_sheet'=>'AUTOMATIC_LIFECYCLE','source_period'=>strtoupper(date('F Y',strtotime($periodKey.'-01'))),'notes'=>'Sewa bulanan otomatis dari tarif kesepakatan masa tinggal; deposit hanya ditagihkan pada reservasi awal.');
                        if(!$this->db->insert('gh_room_bills',$data))throw new RuntimeException('Tagihan belum dapat diterbitkan.');$billId=(int)$this->db->insert_id();$data['id']=$billId;$policy->attach_new_bill($c,$data);$result['created']++;
                    }
                    if($apply){if(!$this->db->trans_status())throw new RuntimeException('Transaksi penerbitan tagihan gagal.');$this->db->trans_commit();}
                }catch(Throwable $e){if($apply)$this->db->trans_rollback();$result['created']=$createdBefore;$result['ok']=FALSE;$result['errors'][]=array('case_id'=>(int)$candidate['id'],'message'=>'Tagihan belum diterbitkan; periksa hubungan masa tinggal dan periode.');}
            }
        }finally{if($held)$this->db->query('SELECT RELEASE_LOCK(?)',array($lock));}
        return $result;
    }
    private function generate_billing_rows($periodKey)
    {
        if (! preg_match('/^(\d{4})-(\d{2})$/', (string) $periodKey, $parts) || ! checkdate((int) $parts[2], 1, (int) $parts[1]) || ! $this->db->field_exists('stay_id', 'gh_room_bills')) {
            return FALSE;
        }
        $start = $periodKey . '-01';
        $end = date('Y-m-t', strtotime($start));
        $policy=new Billing_policy_model();$policy->db=$this->db;$policyReady=$policy->ready();
        $this->db->trans_begin();
        $period = $this->db->where('period_key', $periodKey)->get('gh_billing_periods')->row_array();
        if (! $period) {
            $this->db->insert('gh_billing_periods', array('period_key' => $periodKey, 'period_label' => strtoupper(date('F Y', strtotime($start))), 'period_month' => (int) $parts[2], 'period_year' => (int) $parts[1], 'source_file' => 'MASTER_GENERATOR'));
            $periodId = (int) $this->db->insert_id();
        } else {
            $periodId = (int) $period['id'];
        }
        $this->db->query('SELECT id FROM gh_billing_periods WHERE id = ? FOR UPDATE', array($periodId));
        $created = 0;
        foreach ($this->db->where('status !=', 'inactive')->get('gh_rooms')->result_array() as $room) {
            $this->db->where('room_id', $room['id'])->where_in('stay_status', array('active', 'ended', 'reserved', 'internal'))
                ->group_start()->where('check_in_date <=', $end)->or_where('check_in_date', null)->group_end()->group_start()->where('check_out_date >=', $start)->or_where('check_out_date', null);
            if($policyReady)$this->db->or_where("EXISTS (SELECT 1 FROM gh_resident_cases rc WHERE rc.stay_id=gh_tenant_stays.id AND rc.rolling_monthly=1 AND rc.billing_policy_version IS NOT NULL AND rc.status IN ('active','notice') AND gh_tenant_stays.stay_status='active')",NULL,FALSE);
            $stays=$this->db->group_end()->order_by('check_in_date', 'ASC')->get('gh_tenant_stays')->result_array();
            $eligible = array();
            foreach ($stays as $stay) {
                $rolling=$policyReady&&$stay['stay_status']==='active'?$this->db->where('stay_id',$stay['id'])->where('rolling_monthly',1)->where('billing_policy_version IS NOT NULL',NULL,FALSE)->where_in('status',array('active','notice'))->get('gh_resident_cases')->row_array():NULL;
                $contract = $this->db->where('stay_id', $stay['id'])->where_in('status', array('active', 'expiring', 'ended'))->where('start_date <=', $end)->where('end_date >=', $start)
                    ->order_by('start_date', 'DESC')->order_by('id', 'DESC')->get('gh_contracts')->row_array();
                if(!$contract&&$rolling)$contract=$this->db->where('stay_id',$stay['id'])->where_in('status',array('active','expiring'))->where('start_date <=',$end)->order_by('start_date','DESC')->order_by('id','DESC')->get('gh_contracts')->row_array();
                if (! $contract && $stay['stay_status'] === 'active' && ! empty($stay['source_period']) && $stay['source_period'] !== 'Booking' && $this->db->where('stay_id', $stay['id'])->where_in('status', array('active','expiring','ended'))->count_all_results('gh_contracts') === 0) {
                    // Imported active stays retain operational billing without claiming a signed legal contract.
                    $legacyStart = $this->valid_date($stay['check_in_date']) ? $stay['check_in_date'] : (strtotime($stay['source_period']) !== FALSE ? date('Y-m-01', strtotime($stay['source_period'])) : null);
                    if ($legacyStart && $legacyStart <= $end) $contract = array('start_date' => $legacyStart, 'monthly_price' => $stay['monthly_price'] !== null ? $stay['monthly_price'] : $room['monthly_price'], 'deposit_amount' => $stay['deposit'] !== null ? $stay['deposit'] : $room['deposit'], 'legacy' => TRUE);
                }
                if ($contract && $stay['stay_status'] !== 'internal') {
                    $eligible[] = array($stay, $contract, $rolling);
                }
            }
            if (! $eligible) {
                $exists = $this->db->where('period_id', $periodId)->where('room_id', $room['id'])->get('gh_room_bills')->row_array();
                if (! $exists) {
                    $status = 'empty';
                    foreach ($stays as $stay) {
                        if ($stay['stay_status'] === 'internal') $status = 'internal';
                        elseif ($stay['stay_status'] === 'reserved') $status = 'reserved';
                    }
                    $this->db->insert('gh_room_bills', array('period_id' => $periodId, 'room_id' => $room['id'], 'base_price' => 0, 'deposit_amount' => 0, 'paid_total' => 0, 'past_due_amount' => 0, 'future_due_amount' => 0, 'vacant_amount' => $status === 'empty' ? (int) $room['monthly_price'] : 0, 'bill_status' => $status, 'source_sheet' => 'MASTER_GENERATOR', 'source_period' => strtoupper(date('F Y', strtotime($start)))));
                    $created++;
                }
                continue;
            }
            foreach ($eligible as $pair) {
                list($stay, $contract, $rolling) = $pair;
                $exists = $this->db->where('period_id', $periodId)->where('room_id', $room['id'])->where('stay_id', $stay['id'])->get('gh_room_bills')->row_array();
                if (! $exists) {
                    // Reuse unallocated placeholders; bind legacy invoices only when the matching stay is unambiguous.
                    $candidates = $this->db->where('period_id', $periodId)->where('room_id', $room['id'])->where('stay_id', null)->get('gh_room_bills')->result_array();
                    foreach ($candidates as $candidate) {
                        if (in_array($candidate['bill_status'], array('empty', 'reserved', 'internal'), TRUE) && (int) $candidate['paid_total'] === 0 && $this->db->where('room_bill_id', $candidate['id'])->count_all_results('gh_payments') === 0) {
                            $exists = $candidate;
                            break;
                        }
                        if ((int) $candidate['tenant_id'] === (int) $stay['tenant_id']) {
                            $matching = 0;
                            foreach ($eligible as $entry) if ((int) $entry[0]['tenant_id'] === (int) $stay['tenant_id']) $matching++;
                            if ($matching === 1) {
                                $this->db->where('id', $candidate['id'])->update('gh_room_bills', array('stay_id' => $stay['id']));
                                $exists = $candidate;
                                break;
                            }
                        }
                    }
                }
                if ($exists && ! in_array($exists['bill_status'], array('empty', 'reserved', 'internal'), TRUE)) continue;
                $tenant = $this->db->where('id', $stay['tenant_id'])->get('gh_tenants')->row_array();
                $price = (int) $contract['monthly_price'];
                $dueDay = (int) getenv('GULHOUSE_BILLING_DUE_DAY');
                if ($dueDay < 1 || $dueDay > 31) $dueDay = (int) substr($contract['start_date'], 8, 2);
                $due = $periodKey . '-' . sprintf('%02d', min($dueDay, (int) date('t', strtotime($start))));
                if($rolling)$due=Billing_policy_model::due_in_period($rolling['billing_anchor_date'],$periodKey);
                $initialDeposit=substr((string)$stay['check_in_date'],0,7)===$periodKey?(int)$contract['deposit_amount']:0;
                $data = array('period_id' => $periodId, 'room_id' => $room['id'], 'stay_id' => $stay['id'], 'tenant_id' => $stay['tenant_id'], 'tenant_name_snapshot' => $tenant ? $tenant['fullname'] : null, 'tenant_phone_snapshot' => $tenant ? $tenant['phone'] : null, 'base_price' => $price, 'deposit_amount' => $initialDeposit, 'due_date' => $due, 'paid_total' => 0, 'past_due_amount' => $due < date('Y-m-d') ? $price : 0, 'future_due_amount' => $due >= date('Y-m-d') ? $price : 0, 'vacant_amount' => 0, 'bill_status' => 'unpaid', 'source_sheet' => 'MASTER_GENERATOR', 'source_period' => strtoupper(date('F Y', strtotime($start))), 'notes' => (! empty($contract['legacy']) ? 'Tarif stay impor aktif; kontrak legal belum direkonsiliasi.' : 'Tarif kontrak;') . ' Default satu bulan penuh untuk stay yang overlap periode.');
                if ($exists) $this->db->where('id', $exists['id'])->update('gh_room_bills', $data);
                else $this->db->insert('gh_room_bills', $data);
                $billId=$exists?(int)$exists['id']:(int)$this->db->insert_id();
                if($rolling)$policy->attach_new_bill($rolling,$this->db->where('id',$billId)->get('gh_room_bills')->row_array());
                $created++;
            }
        }
        if (! $this->db->trans_status()) {
            $this->db->trans_rollback();
            return FALSE;
        }
        $this->db->trans_commit();
        return $created;
    }
    public function deposit_balance($tenantId, $stayId, $excludeId = 0)
    {
        $rows = $this->db->where('tenant_id', (int) $tenantId)->where('stay_id', (int) $stayId)->where('id !=', (int) $excludeId)->get('gh_deposit_transactions')->result_array();
        $balance = 0;
        foreach ($rows as $row) {
            $balance += in_array($row['transaction_type'], array('received', 'adjustment'), TRUE) ? (int) $row['amount'] : -(int) $row['amount'];
        }
        return $balance;
    }

    public function create_draft_for_stay($stayId, array $payload = array())
    {
        $stay = $this->db->where('id', (int) $stayId)->get('gh_tenant_stays')->row_array();
        if (! $stay || ! $this->db->table_exists('gh_contracts')) return FALSE;
        if ($this->db->where('stay_id', $stayId)->where('status !=', 'cancelled')->count_all_results('gh_contracts') > 0) return FALSE;
        foreach (array('check_in_date', 'check_out_date', 'monthly_price', 'deposit') as $field) if (isset($payload[$field])) $stay[$field] = $payload[$field];
        foreach (array('monthly_price', 'deposit') as $field) { $stay[$field] = $this->normalize_value($stay[$field], array('type' => 'money')); if ($stay[$field] < 0) return FALSE; }
        if (! $this->valid_date($stay['check_in_date']) || ! $this->valid_date($stay['check_out_date']) || $stay['check_out_date'] < $stay['check_in_date'] || (int) $stay['monthly_price'] <= 0 || (int) $stay['deposit'] < 0) return FALSE;
        $this->db->trans_start();
        $this->db->query('SELECT id FROM gh_tenant_stays WHERE id = ? FOR UPDATE', array((int) $stayId));
        if ($this->db->where('stay_id', $stayId)->where('status !=', 'cancelled')->count_all_results('gh_contracts') > 0) { $this->db->trans_complete(); return FALSE; }
        $this->db->insert('gh_contracts', array('contract_no' => $this->next_number('GH-CTR'), 'tenant_id' => $stay['tenant_id'], 'stay_id' => $stay['id'], 'room_id' => $stay['room_id'], 'start_date' => $stay['check_in_date'], 'end_date' => $stay['check_out_date'], 'monthly_price' => $stay['monthly_price'], 'deposit_amount' => $stay['deposit'], 'status' => 'draft', 'notes' => 'Dibuat dari input penghuni.'));
        $id = (int) $this->db->insert_id();
        $this->seed_contract_documents($id, $stay['tenant_id']);
        $this->db->trans_complete();
        return $this->db->trans_status() ? $id : FALSE;
    }

    private function save_deposit($id, array $data)
    {
        $inspectionConfirmed = ! empty($data['refund_inspection_confirmed']);
        $refundReason = isset($data['refund_reason']) ? trim((string) $data['refund_reason']) : '';
        unset($data['refund_inspection_confirmed'], $data['refund_reason']);
        if ($data['transaction_type'] === 'refund' && (! $inspectionConfirmed || $refundReason === '')) return FALSE;
        if (empty($data['tenant_id']) || empty($data['room_id']) || ! $this->valid_date($data['transaction_date']) || $data['amount'] <= 0) {
            return FALSE;
        }
        if (empty($data['stay_id'])) {
            $stay = $this->db->where('tenant_id', $data['tenant_id'])->where('room_id', $data['room_id'])->order_by('id', 'DESC')->get('gh_tenant_stays')->row_array();
            $data['stay_id'] = $stay ? $stay['id'] : null;
        }
        if ($this->db->table_exists('gh_resident_cases')) {
            $lifecycleCase = $this->db->where('stay_id',(int)$data['stay_id'])->get('gh_resident_cases')->row_array();
            $oldLifecycleDeposit = $id ? $this->get_row('deposits',$id) : NULL;
            if ($lifecycleCase && ($id || in_array($data['transaction_type'],array('refund','deduction'),TRUE) || $lifecycleCase['status']==='checked_out')) return FALSE;
            if ($oldLifecycleDeposit && $this->db->where('stay_id',(int)$oldLifecycleDeposit['stay_id'])->count_all_results('gh_resident_cases')) return FALSE;
        }
        $this->db->trans_start();
        $stay = $this->db->query('SELECT * FROM gh_tenant_stays WHERE id = ? FOR UPDATE', array((int) $data['stay_id']))->row_array();
        $old = $id ? $this->get_row('deposits', $id) : null;
        if (! $stay || (int) $stay['tenant_id'] !== (int) $data['tenant_id'] || (int) $stay['room_id'] !== (int) $data['room_id'] || ($old && ! empty($old['source_payment_id']))) {
            $this->db->trans_complete();
            return FALSE;
        }
        $balance = $this->deposit_balance($data['tenant_id'], $data['stay_id'], $id);
        $delta = in_array($data['transaction_type'], array('received', 'adjustment'), TRUE) ? $data['amount'] : -$data['amount'];
        if ($data['transaction_type'] === 'refund') {
            $this->db->select('COALESCE(SUM(GREATEST(CAST(rb.base_price AS DECIMAL(20,0))-CAST(rb.paid_total AS DECIMAL(20,0)),0)),0) AS debt', FALSE)->from('gh_room_bills rb')
                ->where('rb.tenant_id', $data['tenant_id'])->where('rb.room_id', $data['room_id'])->where_in('rb.bill_status', array('unpaid','partial','paid','overpaid'));
            if ($this->db->field_exists('stay_id', 'gh_room_bills')) $this->db->group_start()->where('rb.stay_id', $data['stay_id'])->or_where('rb.stay_id', null)->group_end();
            $debt = $this->db->get()->row_array();
            if ((int) $debt['debt'] > 0) { $this->db->trans_complete(); return FALSE; }
            $data['description'] = trim((string) $data['description'] . "\nRefund final: pemeriksaan akhir dan kewajiban dikonfirmasi admin. Referensi: " . $refundReason);
        }
        if ($balance + $delta < 0 || ($old && ((int) $old['stay_id'] !== (int) $data['stay_id'] || (int) $old['tenant_id'] !== (int) $data['tenant_id']))) {
            $this->db->trans_complete();
            return FALSE;
        }
        if ($id) {
            $this->db->where('id', $id)->update('gh_deposit_transactions', $data);
        } else {
            $this->db->insert('gh_deposit_transactions', $data);
            $id = (int) $this->db->insert_id();
        }
        $this->db->trans_complete();
        return $this->db->trans_status() ? $id : FALSE;
    }

    public function contract_action($id, $action, array $payload)
    {
        $this->contract_action_error = 'Proses ditolak. Periksa status kontrak, tanggal, bentrok kamar, atau saldo deposit.';
        foreach (array('monthly_price', 'deduction_amount', 'refund_amount') as $field) {
            if (isset($payload[$field])) { $payload[$field] = $this->normalize_value($payload[$field], array('type' => 'money')); if ($payload[$field] < 0) return FALSE; }
        }
        if (! in_array($action, array('activate', 'renew', 'checkout', 'cancel'), TRUE)) {
            return FALSE;
        }
        $this->db->trans_begin();
        $contract = $this->db->query('SELECT * FROM gh_contracts WHERE id = ? FOR UPDATE', array((int) $id))->row_array();
        if (! $contract) {
            $this->db->trans_rollback();
            return FALSE;
        }
        $room = $this->db->query('SELECT * FROM gh_rooms WHERE id = ? FOR UPDATE', array((int) $contract['room_id']))->row_array();
        $stay = $this->db->query('SELECT * FROM gh_tenant_stays WHERE id = ? FOR UPDATE', array((int) $contract['stay_id']))->row_array();
        if (! $stay || ! $room || (int) $stay['tenant_id'] !== (int) $contract['tenant_id'] || (int) $stay['room_id'] !== (int) $contract['room_id']) {
            $this->db->trans_rollback();
            return FALSE;
        }
        if ($action === 'activate') {
            $this->load->model('Lifecycle_model');
            $lifecycleError = $this->Lifecycle_model->activation_error($stay['id']);
            if ($lifecycleError !== '') { $this->contract_action_error = $lifecycleError; $this->db->trans_rollback(); return FALSE; }
            if ($contract['status'] !== 'draft' || ! $this->valid_date($contract['start_date']) || ! $this->valid_date($contract['end_date']) || $contract['end_date'] < $contract['start_date'] || $this->room_overlap($room['id'], $contract['start_date'], $contract['end_date'], $stay['id']) || in_array($room['status'], array('maintenance', 'inactive', 'mess'), TRUE) || $this->db->where('stay_id', $stay['id'])->where('id !=', $id)->where_in('status', array('active','expiring'))->where('start_date <=', $contract['end_date'])->where('end_date >=', $contract['start_date'])->count_all_results('gh_contracts') > 0) {
                $this->db->trans_rollback();
                return FALSE;
            }
            $signed = isset($payload['signed_at']) ? $payload['signed_at'] : date('Y-m-d');
            if (! $this->valid_date($signed)) {
                $this->db->trans_rollback();
                return FALSE;
            }
            foreach (array('ktp', 'contract') as $requiredDoc) {
                if ($this->db->where('contract_id', $id)->where('document_type', $requiredDoc)->where_in('status', array('received', 'verified'))->count_all_results('gh_contract_documents') === 0) {
                    $this->contract_action_error = 'Aktivasi ditolak: checklist ' . ($requiredDoc === 'ktp' ? 'KTP' : 'kontrak') . ' belum diterima/diverifikasi. Lengkapi status dokumen di menu Dokumen.';
                    $this->db->trans_rollback(); return FALSE;
                }
            }
            if (empty($payload['inspection_confirmed'])) {
                $this->contract_action_error = 'Aktivasi ditolak: konfirmasi pemeriksaan kamar dan inventaris check-in wajib dicentang setelah pemeriksaan dilakukan.';
                $this->db->trans_rollback(); return FALSE;
            }
            if ($this->deposit_balance($contract['tenant_id'], $stay['id']) < (int) $contract['deposit_amount'] && empty($payload['deposit_deferred'])) {
                $this->contract_action_error = 'Aktivasi ditolak: saldo deposit belum mencukupi. Catat penerimaan di menu Deposit atau setujui penundaan deposit secara eksplisit pada form aktivasi.';
                $this->db->trans_rollback(); return FALSE;
            }
            if (! empty($payload['deposit_deferred'])) $this->db->where('id', $id)->update('gh_contracts', array('notes' => trim($contract['notes'] . "\nDeposit ditunda dengan persetujuan admin saat aktivasi.")));
            $this->db->where('id', $id)->update('gh_contracts', array('status' => 'active', 'signed_at' => $signed));
            $this->db->where('id', $stay['id'])->update('gh_tenant_stays', array('stay_status' => 'active', 'check_in_date' => $contract['start_date'], 'check_out_date' => $contract['end_date'], 'monthly_price' => $contract['monthly_price'], 'deposit' => $contract['deposit_amount']));
            if ($this->db->table_exists('gh_resident_cases')) $this->db->where('stay_id',$stay['id'])->update('gh_resident_cases',array('status'=>'active'));
            $this->db->where('id', $room['id'])->update('gh_rooms', array('status' => 'occupied', 'is_public' => 0));
            $this->seed_inventory($room['id'], 'check_in', $contract['start_date']);
        } elseif ($action === 'renew') {
            $end = isset($payload['end_date']) ? $payload['end_date'] : '';
            $price = isset($payload['monthly_price']) ? (int) $payload['monthly_price'] : (int) $contract['monthly_price'];
            $start = date('Y-m-d', strtotime($contract['end_date'] . ' +1 day'));
            if (! in_array($contract['status'], array('active', 'expiring'), TRUE) || ! $this->valid_date($end) || $end < $start || $price <= 0 || $this->room_overlap($room['id'], $start, $end, $stay['id'])) {
                $this->db->trans_rollback();
                return FALSE;
            }
            $renewal = $contract;
            unset($renewal['id'], $renewal['created_at'], $renewal['updated_at']);
            $renewal['contract_no'] = $this->next_number('GH-CTR');
            $renewal['start_date'] = $start;
            $renewal['end_date'] = $end;
            $renewal['monthly_price'] = $price;
            $renewal['status'] = 'active';
            $renewal['notes'] = 'Perpanjangan ' . $contract['contract_no'];
            $this->db->where('id', $id)->update('gh_contracts', array('status' => 'ended'));
            $this->db->insert('gh_contracts', $renewal);
            $this->seed_contract_documents((int) $this->db->insert_id(), $contract['tenant_id']);
            $this->db->where('id', $stay['id'])->update('gh_tenant_stays', array('check_out_date' => $end));
        } elseif ($action === 'cancel') {
            if ($contract['status'] !== 'draft') {
                $this->db->trans_rollback();
                return FALSE;
            }
            $this->db->where('id', $id)->update('gh_contracts', array('status' => 'cancelled'));
            $this->db->where('id', $stay['id'])->update('gh_tenant_stays', array('stay_status' => 'ended'));
            $this->release_room($room['id']);
        } else {
            $date = isset($payload['checkout_date']) ? $payload['checkout_date'] : date('Y-m-d');
            if ($this->db->table_exists('gh_resident_cases') && $this->db->where('stay_id',$stay['id'])->count_all_results('gh_resident_cases')) {
                $this->contract_action_error = 'Gunakan Siklus Penghuni untuk checkout, pemeriksaan final dan pengembalian deposit terpisah.';
                $this->db->trans_rollback(); return FALSE;
            }
            $deduction = isset($payload['deduction_amount']) ? (int) $payload['deduction_amount'] : 0;
            $refund = isset($payload['refund_amount']) ? (int) $payload['refund_amount'] : 0;
            $balance = $this->deposit_balance($contract['tenant_id'], $stay['id']);
            if (! in_array($contract['status'], array('active', 'expiring'), TRUE) || ! $this->valid_date($date) || $date < $stay['check_in_date'] || $deduction < 0 || $refund < 0 || $deduction + $refund > $balance || empty($payload['inspection_confirmed']) || ($deduction > 0 && empty($payload['deduction_reason']))) {
                $this->db->trans_rollback();
                return FALSE;
            }
            $debt = $this->db->select('COALESCE(SUM(GREATEST(CAST(rb.base_price AS DECIMAL(20,0)) - CAST(rb.paid_total AS DECIMAL(20,0)),0)),0) AS debt', FALSE)->from('gh_room_bills rb')->join('gh_billing_periods bp', 'bp.id=rb.period_id')->where('rb.tenant_id', $contract['tenant_id'])->where('rb.room_id', $room['id'])->where('bp.period_key <=', substr($date, 0, 7))->where_in('rb.bill_status', array('unpaid', 'partial'))->get()->row_array();
            if ($refund > 0 && (int) $debt['debt'] > 0) {
                $this->db->trans_rollback();
                return FALSE;
            }
            $futureBills = $this->db->select('rb.*')->from('gh_room_bills rb')->join('gh_billing_periods bp', 'bp.id=rb.period_id')->where('rb.stay_id', $stay['id'])->where('bp.period_key >', substr($date, 0, 7))->get()->result_array();
            foreach ($futureBills as $futureBill) {
                if ((int) $futureBill['paid_total'] > 0 || $this->db->where('room_bill_id', $futureBill['id'])->count_all_results('gh_payments') > 0) {
                    $this->db->trans_rollback();
                    return FALSE;
                }
                $this->db->where('id', $futureBill['id'])->update('gh_room_bills', array('base_price' => 0, 'past_due_amount' => 0, 'future_due_amount' => 0, 'bill_status' => 'empty', 'notes' => 'Tagihan mendatang dibatalkan karena checkout ' . $date));
            }
            foreach (array('deduction' => $deduction, 'refund' => $refund) as $type => $amount) {
                if ($amount > 0) {
                    $this->db->insert('gh_deposit_transactions', array('tenant_id' => $contract['tenant_id'], 'stay_id' => $stay['id'], 'room_id' => $room['id'], 'transaction_date' => $date, 'transaction_type' => $type, 'amount' => $amount, 'method' => isset($payload['method']) ? $payload['method'] : 'Manual', 'description' => 'Checkout ' . $contract['contract_no'] . ($type === 'deduction' ? ': ' . $payload['deduction_reason'] : '')));
                }
            }
            $this->db->where('stay_id', $stay['id'])->where('start_date >', $date)->where_in('status', array('active', 'expiring', 'draft', 'ended'))->update('gh_contracts', array('status' => 'cancelled'));
            $this->db->where('stay_id', $stay['id'])->where('start_date <=', $date)->where('end_date >=', $date)->where_in('status', array('active', 'expiring', 'ended'))->update('gh_contracts', array('status' => 'ended', 'end_date' => $date));
            $this->db->where('id', $stay['id'])->update('gh_tenant_stays', array('stay_status' => 'ended', 'check_out_date' => $date));
            $this->release_room($room['id'], ! empty($payload['needs_maintenance']));
            $this->seed_inventory($room['id'], 'check_out', $date);
        }
        if (! $this->db->trans_status()) {
            $this->db->trans_rollback();
            return FALSE;
        }
        $this->db->trans_commit();
        return TRUE;
    }

    private function release_room($roomId, $maintenance = FALSE)
    {
        $other = $this->db->where('room_id', $roomId)->where_in('stay_status', array('active', 'reserved', 'internal'))->order_by('id', 'DESC')->get('gh_tenant_stays')->row_array();
        $status = $maintenance ? 'maintenance' : ($other ? ($other['stay_status'] === 'active' ? 'occupied' : ($other['stay_status'] === 'internal' ? 'mess' : 'reserved')) : 'available');
        $this->db->where('id', $roomId)->update('gh_rooms', array('status' => $status, 'is_public' => $status === 'available' ? 1 : 0));
    }

    private function seed_inventory($roomId, $stage, $date)
    {
        // Lifecycle inspections require signed scans and explicit observations; never seed "good".
        if ($this->db->table_exists('gh_resident_cases') && $this->db->where('room_id',(int)$roomId)->count_all_results('gh_resident_cases')) return;
        if (! $this->db->table_exists('gh_inventory_items')) {
            return;
        }
        foreach ($this->db->where('is_active', 1)->get('gh_inventory_items')->result_array() as $item) {
            $this->db->insert('gh_inventory_checks', array('room_id' => $roomId, 'item_id' => $item['id'], 'item_name_snapshot' => $item['item_name'], 'check_stage' => $stage, 'standard_qty' => $item['standard_qty'], 'actual_qty' => $item['standard_qty'], 'condition_status' => 'good', 'check_date' => $date, 'notes' => 'Checklist otomatis; petugas wajib memverifikasi kondisi fisik.'));
        }
    }

    public function get_contract_print($id)
    {
        if (! $this->db->table_exists('gh_contracts')) {
            return null;
        }

        return $this->db
            ->select('c.*, t.fullname, t.identity_number, t.phone, t.address, r.room_label, r.room_code, p.code AS property_code')
            ->from('gh_contracts c')
            ->join('gh_tenants t', 't.id = c.tenant_id', 'left')
            ->join('gh_rooms r', 'r.id = c.room_id', 'left')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->where('c.id', (int) $id)
            ->get()
            ->row_array();
    }

    public function get_payment_receipt($id)
    {
        return $this->db->select('pay.*, rb.tenant_name_snapshot, rb.tenant_phone_snapshot, rb.base_price, rb.paid_total, rb.bill_status, bp.period_label, r.room_label, r.room_code, p.name AS property_name, p.code AS property_code')
            ->select("COALESCE(NULLIF(rb.tenant_name_snapshot,''), t.fullname) AS receipt_tenant_name", FALSE)
            ->from('gh_payments pay')->join('gh_room_bills rb', 'rb.id=pay.room_bill_id')->join('gh_billing_periods bp', 'bp.id=rb.period_id')
            ->join('gh_tenants t', 't.id=rb.tenant_id', 'left')
            ->join('gh_rooms r', 'r.id=rb.room_id')->join('gh_properties p', 'p.id=r.property_id', 'left')->where('pay.id', (int) $id)->get()->row_array();
    }

    public function template_pdf_path($id)
    {
        // Keep files separate across real and synthetic databases, even when IDs match.
        return APPPATH . 'private_documents/template_' . substr(hash('sha256', $this->db->database), 0, 16) . '_' . (int) $id . '.pdf';
    }

    public function get_document_template($key)
    {
        if (! $this->db->table_exists('gh_document_templates')) {
            return null;
        }

        return $this->db->where('template_key', $key)->where('is_active', 1)->get('gh_document_templates')->row_array();
    }

    public function render_template($body, array $data)
    {
        $map = array(
            '{contract_no}' => isset($data['contract_no']) ? $data['contract_no'] : '',
            '{owner_name}' => 'GUL HOUSE',
            '{tenant_name}' => isset($data['fullname']) ? $data['fullname'] : '',
            '{identity_number}' => isset($data['identity_number']) ? $data['identity_number'] : '',
            '{phone}' => isset($data['phone']) ? $data['phone'] : '',
            '{room_label}' => trim((isset($data['property_code']) ? $data['property_code'] : '') . ' - ' . (isset($data['room_label']) ? $data['room_label'] : '')),
            '{start_date}' => isset($data['start_date']) ? $data['start_date'] : '',
            '{end_date}' => isset($data['end_date']) ? $data['end_date'] : '',
            '{monthly_price}' => 'Rp ' . number_format((int) (isset($data['monthly_price']) ? $data['monthly_price'] : 0), 0, ',', '.'),
            '{deposit_amount}' => 'Rp ' . number_format((int) (isset($data['deposit_amount']) ? $data['deposit_amount'] : 0), 0, ',', '.'),
            '{emergency_contact}' => '',
        );

        return strtr((string) $body, $map);
    }

    private function seed_contract_documents($contractId, $tenantId)
    {
        if (! $this->db->table_exists('gh_contract_documents')) {
            return;
        }

        $docs = array(
            array('ktp', 'Foto/Salinan KTP'),
            array('contract', 'Perjanjian sewa ditandatangani'),
            array('statement', 'Surat pernyataan penghuni'),
            array('handover', 'Form serah terima kamar'),
            array('inventory', 'Checklist inventaris awal'),
        );

        foreach ($docs as $doc) {
            $this->db->insert('gh_contract_documents', array(
                'contract_id' => (int) $contractId,
                'tenant_id' => (int) $tenantId,
                'document_type' => $doc[0],
                'document_title' => $doc[1],
                'status' => 'needed',
            ));
        }
    }

    private function get_receivables()
    {
        if (! $this->db->table_exists('gh_room_bills')) {
            return array();
        }

        $rows = $this->db
            ->select('rb.*, bp.period_label, bp.period_key, r.room_label, p.code AS property_code')
            ->select('GREATEST(CAST(COALESCE(rb.base_price, 0) AS DECIMAL(20,0)) - CAST(COALESCE(rb.paid_total, 0) AS DECIMAL(20,0)), 0) AS remaining_amount', FALSE)
            ->from('gh_room_bills rb')
            ->join('gh_billing_periods bp', 'bp.id = rb.period_id')
            ->join('gh_rooms r', 'r.id = rb.room_id')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->where('GREATEST(CAST(COALESCE(rb.base_price, 0) AS DECIMAL(20,0)) - CAST(COALESCE(rb.paid_total, 0) AS DECIMAL(20,0)), 0) >', 0, FALSE)
            ->where_in('rb.bill_status', array('unpaid', 'partial'))
            ->order_by('rb.due_date', 'ASC')
            ->get()
            ->result_array();

        foreach ($rows as &$row) {
            $row['room_label'] = trim($row['property_code'] . ' - ' . $row['room_label']);
            $row['aging_bucket'] = $this->aging_bucket($row['due_date']);
        }

        return $rows;
    }

    private function get_billing_rows()
    {
        if (! $this->db->table_exists('gh_room_bills')) {
            return array();
        }

        $rows = $this->db
            ->select('rb.*, bp.period_label, bp.period_key, r.room_label, p.code AS property_code')
            ->from('gh_room_bills rb')
            ->join('gh_billing_periods bp', 'bp.id = rb.period_id')
            ->join('gh_rooms r', 'r.id = rb.room_id')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->order_by('bp.period_key', 'DESC')
            ->order_by('p.code', 'ASC')
            ->order_by('r.room_code', 'ASC')
            ->get()
            ->result_array();

        foreach ($rows as &$row) {
            $row['room_label'] = trim($row['property_code'] . ' - ' . $row['room_label']);
        }

        return $rows;
    }

    private function get_report_rows($period = '2026-11')
    {
        if (! $this->valid_period($period)) return array();
        $start = $period . '-01';
        $end = date('Y-m-t', strtotime($start));
        $billRows = $this->db->select('rb.*')->from('gh_room_bills rb')->join('gh_billing_periods bp', 'bp.id=rb.period_id')->where('bp.period_key', $period)->where_in('rb.bill_status', array('paid','partial','unpaid','overpaid'))->get()->result_array();
        $invoice = $settled = $outstanding = 0;
        foreach ($billRows as $bill) {
            $invoice += (int) $bill['base_price'];
            $settled += (int) $bill['paid_total'];
            $outstanding += max(0, (int) $bill['base_price'] - (int) $bill['paid_total']);
        }
        $cashRow = $this->db->select('COALESCE(SUM(amount),0) AS amount', FALSE)->where('payment_type', 'rent')->where('payment_date >=', $start)->where('payment_date <=', $end)->get('gh_payments')->row_array();
        $cash = (int) $cashRow['amount'];
        $feeIncome=0;if($this->db->table_exists('gh_lifecycle_penalty_payments')){$feeRow=$this->db->select('COALESCE(SUM(amount),0) AS amount',FALSE)->where('payment_date >=',$start)->where('payment_date <=',$end)->get('gh_lifecycle_penalty_payments')->row_array();$feeIncome=(int)$feeRow['amount'];}
        $expenseRow = $this->db->select('COALESCE(SUM(amount),0) AS amount', FALSE)->where('payment_status', 'paid')->where('expense_date >=', $start)->where('expense_date <=', $end)->get('gh_expenses')->row_array();
        $expenses = (int) $expenseRow['amount'];
        $depositRow = $this->db->select("COALESCE(SUM(CASE WHEN transaction_type IN ('received','adjustment') THEN amount ELSE -CAST(amount AS SIGNED) END),0) AS amount", FALSE)->where('transaction_date <=', $end)->get('gh_deposit_transactions')->row_array();
        $movementRow = $this->db->select("COALESCE(SUM(CASE WHEN transaction_type IN ('received','adjustment') THEN amount ELSE -CAST(amount AS SIGNED) END),0) AS amount", FALSE)->where('transaction_date >=', $start)->where('transaction_date <=', $end)->get('gh_deposit_transactions')->row_array();
        $rooms = (int) $this->db->where('status !=', 'inactive')->count_all_results('gh_rooms');
        $occupied = (int) $this->db->where('status', 'occupied')->count_all_results('gh_rooms');
        $maintenance = (int) $this->db->where_in('status', array('open','assigned','in_progress'))->count_all_results('gh_maintenance_tickets');
        $complaints = (int) $this->db->where_in('status', array('open','in_progress'))->count_all_results('gh_complaints');
        $money = function ($value) { return 'Rp ' . number_format((int) $value, 0, ',', '.'); };
        return array(
            array('metric' => 'Periode laporan', 'value' => $period, 'notes' => 'Invoice mengikuti periode tagihan; penerimaan sewa mengikuti tanggal pembayaran.'),
            array('metric' => 'Tagihan sewa periode', 'value' => $money($invoice), 'notes' => 'Invoice chargeable; kamar kosong, internal, reservasi dan deposit dikecualikan.'),
            array('metric' => 'Terbayar pada invoice periode', 'value' => $money($settled), 'notes' => 'Saldo ledger rent untuk invoice periode; bukan kas yang diterima bulan ini.'),
            array('metric' => 'Piutang invoice periode', 'value' => $money($outstanding), 'notes' => 'Sisa dihitung per invoice agar lebih bayar tidak menutup piutang penghuni lain.'),
            array('metric' => 'Penerimaan sewa periode', 'value' => $money($cash), 'notes' => 'Hanya transaksi rent dengan tanggal pembayaran di periode ini.'),
            array('metric' => 'Penerimaan denda riil periode', 'value' => $money($feeIncome), 'notes' => 'Ledger pembayaran denda terpisah menurut tanggal transaksi; bukan sewa/deposit atau denda yang baru terakru.'),
            array('metric' => 'Penerimaan sewa dan denda periode', 'value' => $money($cash+$feeIncome), 'notes' => 'Total kas sewa dan denda riil; deposit tetap merupakan kewajiban terpisah.'),
            array('metric' => 'Biaya operasional dibayar periode', 'value' => $money($expenses), 'notes' => 'Expense status paid menurut expense_date; schema belum memiliki tanggal pelunasan terpisah.'),
            array('metric' => 'Selisih sewa dan biaya periode', 'value' => $money($cash - $expenses), 'notes' => 'Ringkasan operasional; deposit dikecualikan dari pendapatan.'),
            array('metric' => 'Saldo deposit akhir periode', 'value' => $money($depositRow['amount']), 'notes' => 'Ledger sampai akhir bulan: masuk + adjustment - potongan - refund. Kewajiban, bukan pendapatan.'),
            array('metric' => 'Perubahan deposit periode', 'value' => $money($movementRow['amount']), 'notes' => 'Mutasi bersih deposit selama periode terpilih.'),
            array('metric' => 'Total kamar aktif', 'value' => number_format($rooms,0,',','.'), 'notes' => 'Snapshot master saat laporan dibuka.'),
            array('metric' => 'Kamar occupied', 'value' => number_format($occupied,0,',','.'), 'notes' => 'Snapshot saat ini, bukan rekonstruksi okupansi historis.'),
            array('metric' => 'Maintenance open', 'value' => number_format($maintenance,0,',','.'), 'notes' => 'Snapshot ticket open/assigned/in progress saat ini.'),
            array('metric' => 'Komplain open', 'value' => number_format($complaints,0,',','.'), 'notes' => 'Snapshot komplain open/in progress saat ini.')
        );
    }
    private function normalize_value($value, array $meta)
    {
        $type = isset($meta['type']) ? $meta['type'] : 'text';
        if ($type === 'checkbox') {
            return $value ? 1 : 0;
        }
        if ($type === 'money') {
            if (! is_scalar($value) && $value !== null) return -1;
            $value = trim((string) $value);
            if ($value === '') return 0;
            if (! preg_match('/^(?:Rp\s*)?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,00)?$/i', $value)) return -1;
            $digits = str_replace('.', '', preg_replace('/,00$/', '', preg_replace('/^Rp\s*/i', '', $value)));
            return strlen($digits) > 15 ? -1 : (int) $digits;
        }
        if ($type === 'number' && strpos($meta['label'], 'Qty') === 0) {
            return max(0, (int) $value);
        }
        if (in_array($type, array('number','room','tenant','stay','contract'), TRUE)) {
            $number = trim((string) $value);
            return $number === '' || (int) $number <= 0 ? null : (int) $number;
        }
        if ($type === 'date') {
            $value = trim((string) $value);
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
        }
        if ($type === 'datetime') {
            $value = trim((string) $value);
            return $value === '' ? null : str_replace('T', ' ', $value);
        }

        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function valid_date($value)
    {
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) $value, $parts) && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }

    public function room_overlap($roomId, $start, $end, $excludeStay = 0)
    {
        $rollingReady = $this->db->table_exists('gh_resident_cases')
            && $this->db->field_exists('rolling_monthly', 'gh_resident_cases')
            && $this->db->field_exists('billing_policy_version', 'gh_resident_cases');
        $this->db->where('room_id', (int) $roomId)->where('id !=', (int) $excludeStay)
            ->where_in('stay_status', array('active', 'reserved', 'internal'))
            ->group_start()->where('check_in_date <=', $end)->or_where('check_in_date', null)->group_end()
            ->group_start()->where('check_out_date >=', $start)->or_where('check_out_date', null);
        // A monthly billing boundary is not evidence of a physical departure.
        if ($rollingReady) {
            $this->db->or_where("EXISTS (SELECT 1 FROM gh_resident_cases rc WHERE rc.stay_id=gh_tenant_stays.id AND rc.rolling_monthly=1 AND rc.billing_policy_version=".$this->db->escape(Billing_policy_model::VERSION)." AND rc.status IN ('active','notice') AND gh_tenant_stays.stay_status='active')", NULL, FALSE);
        }
        return $this->db->group_end()->count_all_results('gh_tenant_stays') > 0;
    }

    private function next_number($prefix)
    {
        return $prefix . '-' . date('Ymd') . '-' . random_int(1000, 9999);
    }

    private function apply_search(array $config, $query)
    {
        $query = trim((string) $query);
        if ($query === '') {
            return;
        }
        $columns = array_keys($config['columns']);
        $hasLike = FALSE;
        $this->db->group_start();
        foreach ($columns as $column) {
            if (preg_match('/_id$|amount|qty|date|at$/', $column)) {
                continue;
            }
            if (! $hasLike) {
                $this->db->like($column, $query);
                $hasLike = TRUE;
            } else {
                $this->db->or_like($column, $query);
            }
        }
        $this->db->group_end();
    }

    private function aging_bucket($dueDate)
    {
        if (! $dueDate) {
            return 'Tanpa jatuh tempo';
        }
        $days = (int) floor((time() - strtotime($dueDate)) / 86400);
        if ($days <= 0) {
            return 'Belum jatuh tempo';
        }
        if ($days <= 30) {
            return '1-30 hari';
        }
        if ($days <= 60) {
            return '31-60 hari';
        }
        if ($days <= 90) {
            return '61-90 hari';
        }
        return '>90 hari';
    }
}
