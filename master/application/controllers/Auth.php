<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Auth_model');
    }

    public function login()
    {
        if (! in_array($this->input->method(TRUE), array('GET', 'POST'), TRUE)) {
            header('Allow: GET, POST');
            show_error('Gunakan formulir login.', 405, 'Metode tidak diizinkan');
            return;
        }
        if ($this->session->userdata('gh_admin_id')) {
            redirect('dashboard', 'location', 303);
            return;
        }

        if ($this->input->method(TRUE) === 'POST') {
            if (! $this->allow_login_attempt()) {
                header('Retry-After: 300');
                show_error('Terlalu banyak percobaan login. Tunggu 5 menit lalu coba kembali.', 429, 'Login dibatasi');
                return;
            }
            $username = trim((string) $this->input->post('username', TRUE));
            $password = (string) $this->input->post('password', FALSE);
            $admin = $this->Auth_model->attempt_login($username, $password);

            if ($admin) {
                @unlink($this->login_attempt_path());
                $this->session->sess_regenerate(TRUE);
                $this->session->set_userdata(array(
                    'gh_admin_id' => (int) $admin['id'],
                    'gh_admin_name' => $admin['fullname'],
                    'gh_admin_role' => $admin['role'],
                    'gh_admin_logged_in' => TRUE,
                ));
                $this->Auth_model->record_login((int) $admin['id']);
                redirect('dashboard', 'location', 303);
                return;
            }

            $this->session->set_flashdata('error', 'Username atau password belum sesuai.');
            redirect('login', 'location', 303);
            return;
        }

        $this->load->view('auth/login', array(
            'title' => 'Login Master | GUL HOUSE',
        ));
    }

    public function logout()
    {
        $this->session->unset_userdata(array(
            'gh_admin_id',
            'gh_admin_name',
            'gh_admin_role',
            'gh_admin_logged_in',
        ));
        $this->session->sess_destroy();
        redirect('login', 'location', 303);
    }

    private function login_attempt_path()
    {
        return rtrim($this->config->item('sess_save_path'), '/\\') . DIRECTORY_SEPARATOR
            . 'gh_login_' . hash('sha256', $this->input->ip_address()) . '.json';
    }

    private function allow_login_attempt()
    {
        $handle = @fopen($this->login_attempt_path(), 'c+');
        if (! $handle || ! flock($handle, LOCK_EX)) {
            if ($handle) { fclose($handle); }
            log_message('error', 'Login throttle storage unavailable.');
            return FALSE;
        }
        $record = json_decode(stream_get_contents($handle), TRUE);
        $now = time();
        if (! is_array($record) || empty($record['until']) || (int) $record['until'] <= $now) {
            $record = array('until' => $now + 300, 'count' => 0);
        }
        $record['count'] = (int) $record['count'] + 1;
        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, json_encode($record));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
        return $record['count'] <= 10;
    }
}
