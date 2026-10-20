<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'libraries/Mobile_command_bridge.php';

class Operations extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->require_login();
        $this->load->model('Operations_model');
    }

    public function index($module = 'contracts')
    {
        $config = $this->Operations_model->get_config($module);
        if (! $config) {
            show_404();
            return;
        }

        $filters = array(
            'archive' => in_array($this->input->get('archive', TRUE), array('active','archived','all'), TRUE) ? $this->input->get('archive', TRUE) : 'active',
            'status' => trim((string) $this->input->get('status', TRUE)),
            'q' => trim((string) $this->input->get('q', TRUE)),
            'period' => trim((string) $this->input->get('period', TRUE)),
        );
        if ($module === 'reports' && $filters['period'] === '') $filters['period'] = '2026-11';
        if ($filters['period'] !== '' && ! $this->Operations_model->valid_period($filters['period'])) { show_error('Periode harus YYYY-MM yang valid.', 400); return; }

        $this->load->model('Booking_retention_model');
        $actor=(int)$this->session->userdata('gh_admin_id');
        $this->load->view('operations/index', array(
            'can_manage_retention' => $module==='bookings' && $this->Booking_retention_model->ready() && $this->Booking_retention_model->can_manage($actor),
            'can_purge_demo' => $module==='bookings' && $this->Booking_retention_model->ready() && $this->Booking_retention_model->can_manage($actor,TRUE),
            'retention_counts' => $module==='bookings' ? $this->Operations_model->booking_retention_counts($filters) : array(),
            'title' => $config['title'] . ' | GUL HOUSE',
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'module_key' => $module,
            'config' => $config,
            'modules' => $this->Operations_model->modules(),
            'rows' => $this->Operations_model->get_rows($module, $filters),
            'metrics' => $this->Operations_model->get_metrics($module, $filters),
            'filters' => $filters,
            'rooms' => $this->Operations_model->get_rooms(),
            'tenants' => $this->Operations_model->get_tenants(),
            'contracts' => $this->Operations_model->get_contract_options(),
            'stays' => $this->Operations_model->get_stay_options(),
        ));
    }

    public function save($module)
    {
        $config = $this->Operations_model->get_config($module);
        if (! $config || ! empty($config['readonly'])) {
            show_404();
            return;
        }
        if (! $this->can_write()) {
            $this->session->set_flashdata('error', 'Akun viewer hanya bisa melihat data.');
            gh_mobile_redirect($config['base'], 'location', 303);
            return;
        }

        $payload = $this->input->post(NULL, TRUE);
        $ok = $this->Operations_model->save($module, is_array($payload) ? $payload : array());
        if ($module === 'templates' && $ok && isset($_FILES['template_file']) && (int) $_FILES['template_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['template_file'];
            $valid = (int) $file['error'] === UPLOAD_ERR_OK
                && strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'pdf'
                && (int) $file['size'] > 0 && (int) $file['size'] <= 10 * 1024 * 1024
                && (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) === 'application/pdf'
                && file_get_contents($file['tmp_name'], FALSE, NULL, 0, 5) === '%PDF-';
            $path = $this->Operations_model->template_pdf_path((int) $ok);
            if (! is_dir(dirname($path))) mkdir(dirname($path), 0775, TRUE);
            if (! $valid || ! move_uploaded_file($file['tmp_name'], $path)) {
                $this->session->set_flashdata('error', 'Metadata template tersimpan, tetapi lampiran ditolak. Gunakan PDF asli maksimal 10 MB. Lampiran sebelumnya tetap tersedia.');
                gh_mobile_redirect($config['base'], 'location', 303);
                return;
            }
        }
        if ($module === 'documents' && $ok) {
            $uploadPath = $this->store_document_file();
            if ($uploadPath !== '') {
                $this->Operations_model->update_document_file((int) $ok, $uploadPath);
            } elseif (isset($_FILES['document_file']) && (int) $_FILES['document_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $this->session->set_flashdata('error', 'Metadata dokumen tersimpan, tetapi file ditolak. Gunakan PDF, JPG, atau PNG asli dengan ukuran maksimal 10 MB.');
                gh_mobile_redirect($config['base'], 'location', 303);
                return;
            }
        }
        if ($ok) {
            $this->log_action('save_' . $module, 'Menyimpan data ' . $config['title'] . ' #' . (int) $ok . '.');
        }
        if (! $ok && $module === 'deposits' && isset($payload['transaction_type']) && $payload['transaction_type'] === 'refund') {
            $this->session->set_flashdata('error', 'Refund ditolak. Konfirmasi pemeriksaan akhir dan isi alasan/referensi, selesaikan seluruh tagihan relevan, lalu pastikan nominal tidak melebihi saldo deposit.');
            gh_mobile_redirect($config['base'], 'location', 303);
            return;
        }
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? $config['title'] . ' tersimpan.' : $config['title'] . ' belum tersimpan. Cek data wajib, tanggal, dan nominal. Gunakan rupiah nonnegatif, misalnya 1500000 atau Rp 1.500.000; notasi ilmiah tidak diterima.');
        gh_mobile_redirect($config['base'], 'location', 303);
    }

    public function retention_preview($id)
    {
        $this->load->model('Booking_retention_model');
        $actor=(int)$this->session->userdata('gh_admin_id');
        if(!$this->Booking_retention_model->can_manage($actor,TRUE)) { show_error('Pratinjau hanya untuk super admin aktif.',403); return; }
        $booking=$this->Operations_model->get_row('bookings',(int)$id);
        if(!$booking) { show_404();return; }
        try { $plan=$this->Booking_retention_model->preview((int)$id,$actor);$error=''; }
        catch(Throwable $e) { $plan=array();$error=get_class($e)==='RuntimeException'?$e->getMessage():'Pemeriksaan gagal. Hubungi operator; tidak ada data yang dihapus.'; }
        $this->load->view('operations/booking_retention',array('title'=>'Tinjau booking | GUL HOUSE','admin_name'=>$this->session->userdata('gh_admin_name'),'booking'=>$booking,'plan'=>$plan,'error'=>$error));
    }
    public function archive_booking($id) { $this->retention_write($id,'archive'); }
    public function restore_booking($id) { $this->retention_write($id,'restore'); }
    public function purge_booking($id) { $this->retention_write($id,'purge'); }
    public function verify_booking_demo($id)
    {
        if($this->input->method(TRUE)!=='POST'){show_error('Gunakan POST dengan token keamanan.',405);return;}
        $this->load->model('Booking_retention_model');$actor=(int)$this->session->userdata('gh_admin_id');
        if(!$this->Booking_retention_model->can_manage($actor,TRUE)){show_error('Verifikasi demo hanya untuk super admin aktif.',403);return;}
        $p=$this->input->post(NULL,TRUE);
        try {
            $this->Booking_retention_model->verify_demo((int)$id,$actor,is_array($p)?$p:array());
            $this->session->set_flashdata('success','Cakupan booking terverifikasi sebagai demo berdasarkan pernyataan super admin. Belum ada data yang dihapus.');
        }catch(Throwable $e){$this->session->set_flashdata('error',get_class($e)==='RuntimeException'?$e->getMessage():'Verifikasi gagal; seluruh perubahan dibatalkan.');}
        gh_mobile_redirect('booking/retention/'.(int)$id,'location',303);
    }
    private function retention_write($id,$action)
    {
        if($this->input->method(TRUE)!=='POST') { show_error('Gunakan POST dengan token keamanan.',405);return; }
        $this->load->model('Booking_retention_model');$actor=(int)$this->session->userdata('gh_admin_id');
        if(!$this->Booking_retention_model->can_manage($actor,$action==='purge')) { show_error('Peran akun tidak mengizinkan tindakan ini.',403);return; }
        $p=$this->input->post(NULL,TRUE);$p=is_array($p)?$p:array();
        try {
            $gcPending=FALSE;
            if($action==='purge') {
                $this->Booking_retention_model->purge((int)$id,$actor,$p);
                try {$gc=$this->Booking_retention_model->collect_files((int)$id);$gcPending=$gc['pending']>0;} catch(Throwable $e) {$gcPending=TRUE;}
            }
            else $this->Booking_retention_model->archive((int)$id,$actor,$p,$action==='restore');
            $this->session->set_flashdata('success',$action==='purge'?'Demo dan seluruh data terkait yang terverifikasi telah dihapus. Backup terenkripsi tersimpan.':($action==='restore'?'Booking dipulihkan.':'Booking diarsipkan.'));
            if($gcPending)$this->session->set_flashdata('error','Data demo sudah dihapus. Sebagian file masih dilindungi atau menunggu pembersihan ulang oleh operator.');
        } catch(Throwable $e) { $this->session->set_flashdata('error',get_class($e)==='RuntimeException'?$e->getMessage():'Tindakan gagal; transaksi dibatalkan. Hubungi operator.'); }
        gh_mobile_redirect('booking?archive=all','location',303);
    }

    public function convert_booking($id)
    {
        if (! $this->can_write()) {
            $this->session->set_flashdata('error', 'Akun viewer hanya bisa melihat data.');
            gh_mobile_redirect('booking', 'location', 303);
            return;
        }
        $ok = $this->Operations_model->convert_booking((int) $id, array(
            'room_id' => (int) $this->input->post('room_id'),
            'start_date' => trim((string) $this->input->post('start_date', TRUE)),
            'end_date' => trim((string) $this->input->post('end_date', TRUE)),
            'stay_status' => trim((string) $this->input->post('stay_status', TRUE)),
        ));
        if ($ok) {
            $this->log_action('complete_booking', 'Selesaikan tindak lanjut booking #' . (int) $id . ' tanpa membuat administrasi penghuni.');
        }
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Booking ditandai Selesai. Pendaftaran penghuni diproses terpisah melalui Daftar Penghuni.' : ($this->Operations_model->get_booking_conversion_error() ?: 'Konversi gagal disimpan. Coba kembali atau hubungi administrator.'));
        gh_mobile_redirect('booking', 'location', 303);
    }

    public function generate_billing()
    {
        if (! $this->can_write()) {
            $this->session->set_flashdata('error', 'Akun viewer hanya bisa melihat data.');
            gh_mobile_redirect('billing', 'location', 303);
            return;
        }
        $this->session->set_flashdata('error','Tagihan diterbitkan otomatis sesuai kesepakatan dan jadwal jatuh tempo. Penerbitan manual melalui halaman ini dinonaktifkan.');
        gh_mobile_redirect('billing', 'location', 303);
    }

    public function export($module)
    {
        $config = $this->Operations_model->get_config($module);
        if (! $config) {
            show_404();
            return;
        }
        $period = trim((string) $this->input->get('period', TRUE)) ?: ($module === 'reports' ? '2026-11' : '');
        if ($period !== '' && ! $this->Operations_model->valid_period($period)) { show_error('Periode harus YYYY-MM yang valid.', 400); return; }

        $rows = $this->Operations_model->get_rows($module, array(
            'archive' => in_array($this->input->get('archive', TRUE),array('active','archived','all'),TRUE)?$this->input->get('archive',TRUE):'active',
            'status' => trim((string) $this->input->get('status', TRUE)),
            'q' => trim((string) $this->input->get('q', TRUE)),
            'period' => $period,
        ));
        $filename = 'gulhouse-' . $module . '-' . date('Ymd-His') . '.csv';
        $this->output
            ->set_content_type('text/csv')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"');

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, array_values($config['columns']));
        foreach ($rows as $row) {
            $line = array();
            foreach (array_keys($config['columns']) as $field) {
                $value = isset($row[$field]) ? $row[$field] : '';
                // Escape spreadsheet formulas in exported text; retain the original stored value.
                if (is_string($value) && preg_match('/^[\x00-\x20\x7f\p{Z}\x{FEFF}]*[=+\-@]/u', $value)) {
                    $value = "'" . $value;
                }
                $line[] = $value;
            }
            fputcsv($handle, $line);
        }
        rewind($handle);
        $this->output->set_output(stream_get_contents($handle));
        fclose($handle);
    }

    public function print_contract($id)
    {
        $contract = $this->Operations_model->get_contract_print((int) $id);
        if (! $contract) {
            show_404();
            return;
        }

        $template = $this->Operations_model->get_document_template('rental_contract');
        if (! $template) { show_error('Template kontrak aktif belum tersedia. Aktifkan template sebelum mencetak kontrak.', 404); return; }
        $body = $template ? $template['body_template'] : '';
        $this->load->view('operations/print_document', array(
            'title' => 'Print Kontrak ' . $contract['contract_no'],
            'heading' => 'Perjanjian Sewa Kamar Kos',
            'body' => $this->Operations_model->render_template($body, $contract),
        ));
    }

    public function print_receipt($id)
    {
        $receipt = $this->Operations_model->get_payment_receipt((int) $id);
        if (! $receipt) { show_404(); return; }
        $this->output->set_header('Cache-Control: no-store');
        $this->load->view('operations/print_receipt', array('receipt' => $receipt));
    }

    public function contract_action($id, $action)
    {
        if ($this->input->method() !== 'post' || ! $this->can_write()) {
            show_error('Aksi memerlukan POST dan hak tulis.', 403);
            return;
        }
        $payload = $this->input->post(NULL, TRUE);
        $ok = $this->Operations_model->contract_action((int) $id, $action, is_array($payload) ? $payload : array());
        if ($ok) {
            $this->log_action('contract_' . $action, 'Kontrak #' . (int) $id . ': ' . $action);
        }
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Proses kontrak berhasil.' : $this->Operations_model->get_contract_action_error());
        gh_mobile_redirect('contracts', 'location', 303);
    }

    public function escalate_complaint($id)
    {
        if ($this->input->method() !== 'post' || ! $this->can_write()) { show_error('POST dan hak tulis diperlukan.', 403); return; }
        $ok = $this->Operations_model->escalate_complaint((int) $id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Komplain terhubung ke ticket maintenance.' : 'Komplain tidak dapat diteruskan.');
        if ($ok) $this->log_action('escalate_complaint', 'Komplain #' . (int) $id . ' diteruskan ke maintenance.');
        gh_mobile_redirect('complaints', 'location', 303);
    }

    public function update_booking_status($id)
    {
        if ($this->input->method() !== 'post' || ! $this->can_write()) { show_error('POST dan hak tulis diperlukan.', 403); return; }
        $ok = $this->Operations_model->update_booking_status((int) $id, $this->input->post('status', TRUE));
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Status follow-up booking tersimpan.' : 'Status tidak dapat diubah.');
        if ($ok) $this->log_action('update_booking_status', 'Status booking #' . (int) $id . ' diubah.');
        gh_mobile_redirect('booking', 'location', 303);
    }

    public function draft_contract_from_stay($id)
    {
        if ($this->input->method() !== 'post' || ! $this->can_write()) { show_error('POST dan hak tulis diperlukan.', 403); return; }
        $ok = $this->Operations_model->create_draft_for_stay((int) $id, array('check_in_date' => $this->input->post('start_date', TRUE), 'check_out_date' => $this->input->post('end_date', TRUE), 'monthly_price' => $this->input->post('monthly_price', TRUE), 'deposit' => $this->input->post('deposit_amount', TRUE)));
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Kontrak draft dan checklist dokumen dibuat. Verifikasi sebelum aktivasi.' : 'Draft belum dibuat. Cek tanggal/tarif atau kontrak existing.');
        if ($ok) $this->log_action('draft_contract_from_stay', 'Draft kontrak dari stay #' . (int) $id);
        gh_mobile_redirect('contracts', 'location', 303);
    }

    public function download_document($id)
    {
        $document = $this->Operations_model->get_row('documents', (int) $id);
        $directory = $document && strpos((string) $document['file_path'], 'uploads/DOCUMENTS/') === 0 ? FCPATH . '../uploads/DOCUMENTS' : APPPATH . 'private_documents';
        $path = $document && ! empty($document['file_path']) ? realpath($directory . DIRECTORY_SEPARATOR . basename($document['file_path'])) : FALSE;
        $root = realpath($directory);
        if (! $path || ! $root || strpos($path, $root . DIRECTORY_SEPARATOR) !== 0 || ! is_file($path)) {
            show_404();
            return;
        }
        $this->output->set_content_type('application/octet-stream')->set_header('X-Content-Type-Options: nosniff')
            ->set_header('Cache-Control: no-store')->set_header('Content-Disposition: attachment; filename="' . basename($path) . '"')->set_output(file_get_contents($path));
    }

    public function print_template($key)
    {
        $template = $this->Operations_model->get_document_template($key);
        if (! $template) {
            show_404();
            return;
        }

        $pdfPath = $this->Operations_model->template_pdf_path($template['id']);
        if (is_file($pdfPath)) {
            $this->output->set_content_type('application/pdf')
                ->set_header('X-Content-Type-Options: nosniff')
                ->set_header('Cache-Control: no-store')
                ->set_header('Content-Disposition: inline; filename="template-' . (int) $template['id'] . '.pdf"')
                ->set_output(file_get_contents($pdfPath));
            return;
        }

        $this->load->view('operations/print_document', array(
            'title' => 'Print ' . $template['title'],
            'heading' => $template['title'],
            'body' => $template['body_template'],
        ));
    }

    public function delete($module, $id)
    {
        $config = $this->Operations_model->get_config($module);
        if (! $config || ! empty($config['readonly'])) {
            show_404();
            return;
        }
        if (! $this->can_write()) {
            $this->session->set_flashdata('error', 'Akun viewer hanya bisa melihat data.');
            gh_mobile_redirect($config['base'], 'location', 303);
            return;
        }

        $ok = $this->Operations_model->delete_row($module, (int) $id);
        if ($ok) {
            $this->log_action('delete_' . $module, 'Menghapus data ' . $config['title'] . ' #' . (int) $id . '.');
        }
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? $config['title'] . ' dihapus.' : $config['title'] . ' belum bisa dihapus.');
        gh_mobile_redirect($config['base'], 'location', 303);
    }

    private function require_login()
    {
        if ( ! $this->session->userdata('gh_admin_logged_in')) {
            $this->session->set_flashdata('error', 'Sesi belum aktif. Silakan login terlebih dahulu.');
            gh_mobile_redirect('login', 'location', 303);
        }
    }

    private function can_write()
    {
        return $this->session->userdata('gh_admin_role') !== 'viewer';
    }

    private function log_action($action, $description)
    {
        if (! $this->db->table_exists('gh_activity_logs')) {
            return;
        }

        $this->db->insert('gh_activity_logs', array(
            'admin_user_id' => (int) $this->session->userdata('gh_admin_id'),
            'action' => $action,
            'description' => $description,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => substr((string) $this->input->user_agent(), 0, 255),
        ));
    }

    private function store_document_file()
    {
        if (empty($_FILES['document_file']['name']) || (int) $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
            return '';
        }

        $allowed = array('pdf', 'jpg', 'jpeg', 'png');
        $extension = strtolower(pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION));
        if (! in_array($extension, $allowed, TRUE)) {
            return '';
        }
        if ((int) $_FILES['document_file']['size'] > 10 * 1024 * 1024 || (int) $_FILES['document_file']['size'] <= 0) {
            return '';
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['document_file']['tmp_name']);
        $mimes = array('pdf' => array('application/pdf'), 'jpg' => array('image/jpeg'), 'jpeg' => array('image/jpeg'), 'png' => array('image/png'));
        if (! in_array($mime, $mimes[$extension], TRUE)) {
            return '';
        }
        $directory = APPPATH . 'private_documents';
        if (! is_dir($directory)) {
            mkdir($directory, 0775, TRUE);
        }

        $filename = 'document_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)) . '.' . $extension;
        $target = $directory . DIRECTORY_SEPARATOR . $filename;
        if (! move_uploaded_file($_FILES['document_file']['tmp_name'], $target)) {
            return '';
        }

        return 'private/' . $filename;
    }
}
