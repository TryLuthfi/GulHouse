<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->require_login();
        $this->load->model('Dashboard_model');
    }

    public function index()
    {
        $selectedPeriod = $this->Dashboard_model->set_period($this->input->get('period', TRUE));
        $data = array(
            'selected_period' => $selectedPeriod,
            'title' => 'Dashboard Master | GUL HOUSE',
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'summary' => $this->Dashboard_model->get_summary(),
            'property_rows' => $this->Dashboard_model->get_property_rows(),
            'room_type_rows' => $this->Dashboard_model->get_room_type_rows(),
            'booking_rows' => $this->Dashboard_model->get_recent_bookings(),
            'payment_rows' => $this->Dashboard_model->get_latest_payment_rows(),
            'payment_issue_rows' => $this->Dashboard_model->get_payment_issue_rows(),
            'charts' => $this->Dashboard_model->get_charts(),
        );

        $role = $this->session->userdata('gh_admin_role');
        if (in_array($role, array('finance', 'maintenance'), TRUE)) $data['booking_rows'] = array();
        if ($role === 'maintenance') {
            $data['payment_rows'] = array();
            $data['payment_issue_rows'] = array();
        }

        $this->load->view('dashboard/index', $data);
    }

    private function require_login()
    {
        if ( ! $this->session->userdata('gh_admin_logged_in')) {
            $this->session->set_flashdata('error', 'Sesi belum aktif. Silakan login terlebih dahulu.');
            redirect('login', 'location', 303);
        }
    }
}
