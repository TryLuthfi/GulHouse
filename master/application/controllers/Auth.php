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
        if ($this->session->userdata('gh_admin_id')) {
            redirect('dashboard');
            return;
        }

        if ($this->input->method(TRUE) === 'POST') {
            $username = trim((string) $this->input->post('username', TRUE));
            $password = (string) $this->input->post('password', FALSE);
            $admin = $this->Auth_model->attempt_login($username, $password);

            if ($admin) {
                $this->session->sess_regenerate(TRUE);
                $this->session->set_userdata(array(
                    'gh_admin_id' => (int) $admin['id'],
                    'gh_admin_name' => $admin['fullname'],
                    'gh_admin_role' => $admin['role'],
                    'gh_admin_logged_in' => TRUE,
                ));
                $this->Auth_model->record_login((int) $admin['id']);
                redirect('dashboard');
                return;
            }

            $this->session->set_flashdata('error', 'Username atau password belum sesuai.');
            redirect('login');
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
        redirect('login');
    }
}
