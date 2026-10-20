<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Access extends CI_Controller
{
    public function reset_code($id)
    {
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate')
            ->set_header('Pragma: no-cache')->set_header('Referrer-Policy: no-referrer');
        if ($this->input->method(TRUE)!=='POST') { show_error('Gunakan POST.',405);return; }
        if (!$this->session->userdata('gh_admin_logged_in')) { redirect('login','location',303);return; }
        $actor=$this->db->where('id',(int)$this->session->userdata('gh_admin_id'))->where('is_active',1)->get('gh_admin_users')->row_array();
        if (!$actor || $actor['role']!=='super_admin') { show_error('Hanya super admin dapat mereset kode akses.',403);return; }
        $this->load->model('Resident_access_model');
        try {
            $code=$this->Resident_access_model->reset((int)$id,(int)$actor['id'],$this->input->post('reason',TRUE),$this->input->post('identity_verified',TRUE));
            $this->session->set_flashdata('resident_access_code',$code);
            $this->session->set_flashdata('success','Kode akses diganti. Sampaikan kode baru secara pribadi setelah verifikasi identitas. Kode dan sesi lama telah dibatalkan.');
        } catch (Throwable $e) {
            $this->session->set_flashdata('error',$e instanceof RuntimeException?$e->getMessage():'Reset belum dapat diproses. Coba kembali.');
        }
        redirect('lifecycle/detail/'.(int)$id,'location',303);
    }
}
