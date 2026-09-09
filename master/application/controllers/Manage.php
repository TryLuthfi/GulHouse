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

    public function tenants()
    {
        $this->load->view('manage/tenants', array(
            'title' => 'Penghuni | GUL HOUSE',
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'rows' => $this->Master_model->get_tenant_stays(),
            'rooms' => $this->Master_model->get_active_rooms_for_stay(),
        ));
    }

    public function save_tenant()
    {
        $payload = array(
            'stay_id' => (int) $this->input->post('stay_id'),
            'tenant_id' => (int) $this->input->post('tenant_id'),
            'room_id' => (int) $this->input->post('room_id'),
            'stay_status' => trim((string) $this->input->post('stay_status', TRUE)),
            'fullname' => trim((string) $this->input->post('fullname', TRUE)),
            'birth_place_date' => trim((string) $this->input->post('birth_place_date', TRUE)),
            'religion' => trim((string) $this->input->post('religion', TRUE)),
            'identity_number' => preg_replace('/[^0-9]/', '', (string) $this->input->post('identity_number', TRUE)),
            'phone' => trim((string) $this->input->post('phone', TRUE)),
            'marital_status' => trim((string) $this->input->post('marital_status', TRUE)),
            'occupation' => trim((string) $this->input->post('occupation', TRUE)),
            'address' => trim((string) $this->input->post('address', TRUE)),
            'emergency_name' => trim((string) $this->input->post('emergency_name', TRUE)),
            'emergency_relationship' => trim((string) $this->input->post('emergency_relationship', TRUE)),
            'emergency_phone' => trim((string) $this->input->post('emergency_phone', TRUE)),
            'check_in_date' => $this->nullable_date($this->input->post('check_in_date')),
            'check_out_date' => $this->nullable_date($this->input->post('check_out_date')),
            'monthly_price' => $this->nullable_money($this->input->post('monthly_price')),
            'deposit' => $this->nullable_money($this->input->post('deposit')),
            'source_period' => trim((string) $this->input->post('source_period', TRUE)),
            'source_unit_name' => trim((string) $this->input->post('source_unit_name', TRUE)),
            'notes' => trim((string) $this->input->post('notes', TRUE)),
        );

        $ok = $this->Master_model->save_tenant_stay($payload);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Data penghuni tersimpan.' : 'Data penghuni belum tersimpan.');
        redirect('tenants');
    }

    public function end_tenant_stay($id)
    {
        $ok = $this->Master_model->end_tenant_stay((int) $id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Stay penghuni diakhiri dan kamar dibuka kembali.' : 'Stay belum bisa diakhiri.');
        redirect('tenants');
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

    private function nullable_date($value)
    {
        $value = trim((string) $value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    private function require_login()
    {
        if ( ! $this->session->userdata('gh_admin_logged_in')) {
            redirect('login');
        }
    }
}
