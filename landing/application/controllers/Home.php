<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Landing_model');
    }

    public function index()
    {
        $data = array(
            'title' => 'GUL HOUSE - Kos Premium Siap Huni',
            'summary' => $this->Landing_model->get_summary(),
            'room_types' => $this->Landing_model->get_room_types(),
            'featured_rooms' => $this->Landing_model->get_featured_rooms(),
            'all_rooms' => $this->Landing_model->get_all_public_rooms(),
            'property_stats' => $this->Landing_model->get_property_stats(),
            'amenities' => $this->Landing_model->get_amenities(),
            'testimonials' => $this->Landing_model->get_testimonials(),
            'cost_items' => $this->Landing_model->get_cost_items(),
            'house_rules' => $this->Landing_model->get_house_rules(),
            'nearby_places' => $this->Landing_model->get_nearby_places(),
        );

        $this->load->view('landing_home', $data);
    }

    public function booking()
    {
        $payload = array(
            'fullname' => trim((string) $this->input->post('fullname', TRUE)),
            'phone' => trim((string) $this->input->post('phone', TRUE)),
            'room_interest' => trim((string) $this->input->post('room_interest', TRUE)),
            'move_in_plan' => trim((string) $this->input->post('move_in_plan', TRUE)),
            'message' => trim((string) $this->input->post('message', TRUE)),
        );

        $saved = $this->Landing_model->save_booking_request($payload);
        $this->session->set_flashdata($saved ? 'success' : 'warning', $saved
            ? 'Terima kasih. Permintaan booking sudah kami terima.'
            : 'Permintaan belum tersimpan ke database, silakan hubungi WhatsApp GUL HOUSE.');

        redirect(base_url('#booking'));
    }

    public function room($id = null)
    {
        $room = $this->Landing_model->get_room_detail((int) $id);

        if ( ! $room) {
            show_404();
            return;
        }

        $data = array(
            'title' => $room['code'] . ' - ' . $room['name'] . ' | GUL HOUSE',
            'room' => $room,
            'gallery' => $this->Landing_model->get_room_gallery($room),
            'similar_rooms' => $this->Landing_model->get_similar_rooms($room),
        );

        $this->load->view('room_detail', $data);
    }
}
