<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Manage extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->require_login();
        $this->load->model('Master_model');
    }

    public function properties()
    {
        $id = (int) $this->input->get('id');
        $this->load->view('manage/properties', array(
            'title' => 'Properti | GUL HOUSE',
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'rows' => $this->Master_model->get_properties(),
            'edit' => $id > 0 ? $this->Master_model->get_property($id) : null,
        ));
    }

    public function save_property()
    {
        $payload = array(
            'id' => (int) $this->input->post('id'),
            'code' => strtoupper(trim((string) $this->input->post('code', TRUE))),
            'name' => trim((string) $this->input->post('name', TRUE)),
            'property_type' => trim((string) $this->input->post('property_type', TRUE)),
            'address' => trim((string) $this->input->post('address', TRUE)),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
        );

        $ok = $this->Master_model->save_property($payload);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Properti tersimpan.' : 'Properti belum tersimpan. Cek kode dan nama.');
        redirect('properties');
    }

    public function delete_property($id)
    {
        $ok = $this->Master_model->soft_delete_property((int) $id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Properti dinonaktifkan.' : 'Properti belum bisa dinonaktifkan.');
        redirect('properties');
    }

    public function room_types()
    {
        $id = (int) $this->input->get('id');
        $this->load->view('manage/room_types', array(
            'title' => 'Tipe Kamar | GUL HOUSE',
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'rows' => $this->Master_model->get_room_types(),
            'edit' => $id > 0 ? $this->Master_model->get_room_type($id) : null,
        ));
    }

    public function save_room_type()
    {
        $payload = array(
            'id' => (int) $this->input->post('id'),
            'name' => trim((string) $this->input->post('name', TRUE)),
            'description' => trim((string) $this->input->post('description', TRUE)),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
        );

        $ok = $this->Master_model->save_room_type($payload);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Tipe kamar tersimpan.' : 'Tipe kamar belum tersimpan.');
        redirect('room-types');
    }

    public function delete_room_type($id)
    {
        $ok = $this->Master_model->soft_delete_room_type((int) $id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Tipe kamar dinonaktifkan.' : 'Tipe kamar belum bisa dinonaktifkan.');
        redirect('room-types');
    }

    public function rooms()
    {
        $id = (int) $this->input->get('id');
        $this->load->view('manage/rooms', array(
            'title' => 'Kamar | GUL HOUSE',
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'rows' => $this->Master_model->get_rooms(),
            'properties' => $this->Master_model->get_active_properties(),
            'room_types' => $this->Master_model->get_active_room_types(),
            'edit' => $id > 0 ? $this->Master_model->get_room($id) : null,
        ));
    }

    public function save_room()
    {
        $payload = array(
            'id' => (int) $this->input->post('id'),
            'property_id' => (int) $this->input->post('property_id'),
            'room_type_id' => (int) $this->input->post('room_type_id'),
            'room_code' => strtoupper(trim((string) $this->input->post('room_code', TRUE))),
            'room_name' => trim((string) $this->input->post('room_name', TRUE)),
            'room_label' => trim((string) $this->input->post('room_label', TRUE)),
            'floor_no' => $this->nullable_int($this->input->post('floor_no')),
            'deposit' => $this->nullable_money($this->input->post('deposit')),
            'monthly_price' => $this->nullable_money($this->input->post('monthly_price')),
            'status' => trim((string) $this->input->post('status', TRUE)),
            'is_public' => $this->input->post('is_public') ? 1 : 0,
        );

        $ok = $this->Master_model->save_room($payload);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Kamar tersimpan.' : 'Kamar belum tersimpan.');
        redirect('rooms');
    }

    public function delete_room($id)
    {
        $ok = $this->Master_model->soft_delete_room((int) $id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Kamar diarsipkan.' : 'Kamar belum bisa diarsipkan.');
        redirect('rooms');
    }

    public function toggle_room_public($id)
    {
        $this->Master_model->toggle_room_public((int) $id);
        redirect('rooms');
    }

    private function nullable_int($value)
    {
        $value = trim((string) $value);
        return $value === '' ? null : (int) $value;
    }

    private function nullable_money($value)
    {
        $value = preg_replace('/[^0-9]/', '', (string) $value);
        return $value === '' ? null : (int) $value;
    }

    private function require_login()
    {
        if ( ! $this->session->userdata('gh_admin_logged_in')) {
            redirect('login');
        }
    }
}
