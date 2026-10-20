<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'libraries/Mobile_command_bridge.php';

class PaymentProofs extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (! $this->session->userdata('gh_admin_logged_in')) { gh_mobile_redirect('login', 'location', 303); return; }
        $account = $this->db->where('id', (int) $this->session->userdata('gh_admin_id'))->where('is_active', 1)->get('gh_admin_users')->row_array();
        if (! $account) { show_error('Akun tidak aktif.', 403); return; }
        $this->session->set_userdata('gh_admin_role', strtolower((string) $account['role']));
        if (! in_array($this->session->userdata('gh_admin_role'), array('owner','super_admin','admin','manager','finance','viewer'), TRUE)) { show_error('Tidak memiliki akses bukti pembayaran.', 403); return; }
        $this->load->model('Payment_proof_model');
    }

    public function index($paymentId)
    {
        $payment = $this->Payment_proof_model->payment((int) $paymentId);
        if (! $payment) { show_404(); return; }
        if (! $this->db->table_exists('gh_payment_proofs')) { show_error('Modul bukti pembayaran belum diinisialisasi.', 503); return; }
        $this->output->set_header('Cache-Control: no-store');
        $this->load->view('payment_proofs/index', array('payment'=>$payment,'proofs'=>$this->Payment_proof_model->rows($paymentId),'can_write'=>$this->session->userdata('gh_admin_role') !== 'viewer'));
    }

    public function upload($paymentId)
    {
        if (! $this->write_allowed()) return;
        if (! $this->Payment_proof_model->payment($paymentId)) { show_404(); return; }
        $ok = isset($_FILES['proof_file']) ? $this->Payment_proof_model->save_file($paymentId, $_FILES['proof_file'], $this->input->post('description', TRUE)) : FALSE;
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Bukti transfer tersimpan pada transaksi ini.' : 'Bukti ditolak. Pilih PDF, JPG, atau PNG asli dengan ukuran maksimal 10 MB.');
        if ($ok) $this->audit('upload_payment_proof', (int) $ok, (int) $paymentId);
        gh_mobile_redirect('paymentproofs/index/' . (int) $paymentId, 'location', 303);
    }

    public function download($id)
    {
        $proof = $this->Payment_proof_model->proof($id);
        $path = $proof ? $this->Payment_proof_model->path($proof['stored_filename']) : FALSE;
        if (! $path || ! $this->Payment_proof_model->payment($proof['payment_id'])) { show_404(); return; }
        $this->output->set_content_type('application/octet-stream')->set_header('Content-Disposition: attachment; filename="bukti-pembayaran-' . (int) $proof['payment_id'] . '.' . pathinfo($proof['stored_filename'], PATHINFO_EXTENSION) . '"')
            ->set_header('X-Content-Type-Options: nosniff')->set_header('Cache-Control: private, no-store')->set_output(file_get_contents($path));
    }

    public function delete($id)
    {
        if (! $this->write_allowed()) return;
        $proof = $this->Payment_proof_model->proof($id);
        if (! $proof) { show_404(); return; }
        $result = $this->Payment_proof_model->delete_proof($id);
        $ok = $result['deleted'];
        $kind = ! $ok ? 'error' : ($result['cleanup_pending'] ? 'warning' : 'success');
        $message = ! $ok ? 'Bukti belum dihapus; metadata tetap tersimpan.' : ($result['cleanup_pending'] ? 'Bukti dihapus dari transaksi. Pembersihan berkas privat tertunda dan tercatat untuk dicoba kembali.' : 'Bukti transfer dihapus.');
        $this->session->set_flashdata($kind, $message);
        if ($ok) $this->audit($result['cleanup_pending'] ? 'delete_payment_proof_cleanup_pending' : 'delete_payment_proof', (int) $id, (int) $proof['payment_id']);
        gh_mobile_redirect('paymentproofs/index/' . (int) $proof['payment_id'], 'location', 303);
    }

    private function write_allowed()
    {
        if ($this->input->method() !== 'post') { show_error('Aksi memerlukan POST.', 405); return FALSE; }
        if ($this->session->userdata('gh_admin_role') === 'viewer') { show_error('Viewer hanya dapat melihat dan mengunduh bukti.', 403); return FALSE; }
        return TRUE;
    }

    private function audit($action, $id, $paymentId)
    {
        if ($this->db->table_exists('gh_activity_logs')) $this->db->insert('gh_activity_logs', array('admin_user_id'=>(int) $this->session->userdata('gh_admin_id'),'action'=>$action,'description'=>'Bukti #' . $id . ' transaksi #' . $paymentId,'ip_address'=>$this->input->ip_address(),'user_agent'=>substr((string) $this->input->user_agent(),0,255)));
    }
}
