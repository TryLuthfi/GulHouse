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
        $data = array(
            'title' => 'Dashboard Master | GUL HOUSE',
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'summary' => $this->Dashboard_model->get_summary(),
            'property_rows' => $this->Dashboard_model->get_property_rows(),
            'room_type_rows' => $this->Dashboard_model->get_room_type_rows(),
            'booking_rows' => $this->Dashboard_model->get_recent_bookings(),
            'payment_rows' => $this->Dashboard_model->get_latest_payment_rows(),
            'charts' => $this->Dashboard_model->get_charts(),
        );

        $this->load->view('dashboard/index', $data);
    }

    private function require_login()
    {
        if ( ! $this->session->userdata('gh_admin_logged_in')) {
            redirect('login');
        }
    }
}
