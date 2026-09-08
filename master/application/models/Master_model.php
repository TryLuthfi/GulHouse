<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_model extends CI_Model
{
    public function get_properties()
    {
        return $this->db
            ->order_by('code', 'ASC')
            ->get('gh_properties')
            ->result_array();
    }

    public function get_active_properties()
    {
        return $this->db
            ->where('is_active', 1)
            ->order_by('code', 'ASC')
            ->get('gh_properties')
            ->result_array();
    }

    public function get_property($id)
    {
        return $this->db->where('id', $id)->get('gh_properties')->row_array();
    }

    public function save_property(array $payload)
    {
        if ($payload['code'] === '' || $payload['name'] === '') {
            return FALSE;
        }

        $data = array(
            'code' => $payload['code'],
            'name' => $payload['name'],
            'property_type' => in_array($payload['property_type'], array('house', 'apartment', 'other'), TRUE) ? $payload['property_type'] : 'house',
            'address' => $payload['address'] !== '' ? $payload['address'] : null,
            'is_active' => (int) $payload['is_active'],
        );

        if ($payload['id'] > 0) {
            return $this->db->where('id', $payload['id'])->update('gh_properties', $data);
        }

        return $this->db->insert('gh_properties', $data);
    }

    public function soft_delete_property($id)
    {
        if ($id <= 0) {
            return FALSE;
        }

        return $this->db
            ->where('id', $id)
            ->update('gh_properties', array('is_active' => 0));
    }

    public function get_room_types()
    {
        return $this->db
            ->order_by('name', 'ASC')
            ->get('gh_room_types')
            ->result_array();
    }

    public function get_active_room_types()
    {
        return $this->db
            ->where('is_active', 1)
            ->order_by('name', 'ASC')
            ->get('gh_room_types')
            ->result_array();
    }

    public function get_room_type($id)
    {
        return $this->db->where('id', $id)->get('gh_room_types')->row_array();
    }

    public function save_room_type(array $payload)
    {
        if ($payload['name'] === '') {
            return FALSE;
        }

        $data = array(
            'name' => $payload['name'],
            'description' => $payload['description'] !== '' ? $payload['description'] : null,
            'is_active' => (int) $payload['is_active'],
        );

        if ($payload['id'] > 0) {
            return $this->db->where('id', $payload['id'])->update('gh_room_types', $data);
        }

        return $this->db->insert('gh_room_types', $data);
    }

    public function soft_delete_room_type($id)
    {
        if ($id <= 0) {
            return FALSE;
        }

        return $this->db
            ->where('id', $id)
            ->update('gh_room_types', array('is_active' => 0));
    }

    public function get_rooms()
    {
        return $this->db
            ->select('r.*, p.code AS property_code, rt.name AS type_name')
            ->from('gh_rooms r')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->join('gh_room_types rt', 'rt.id = r.room_type_id', 'left')
            ->order_by('p.code', 'ASC')
            ->order_by('r.room_code', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_room($id)
    {
        return $this->db->where('id', $id)->get('gh_rooms')->row_array();
    }

    public function save_room(array $payload)
    {
        if ($payload['property_id'] <= 0 || $payload['room_type_id'] <= 0 || $payload['room_code'] === '') {
            return FALSE;
        }

        $roomLabel = $payload['room_label'] !== '' ? $payload['room_label'] : trim($payload['room_name'] . ' ' . preg_replace('/\D+/', '', $payload['room_code']));
        $data = array(
            'property_id' => $payload['property_id'],
            'room_type_id' => $payload['room_type_id'],
            'room_code' => $payload['room_code'],
            'room_name' => $payload['room_name'] !== '' ? $payload['room_name'] : $payload['room_code'],
            'room_label' => $roomLabel !== '' ? $roomLabel : $payload['room_code'],
            'floor_no' => $payload['floor_no'],
            'deposit' => $payload['deposit'],
            'monthly_price' => $payload['monthly_price'],
            'status' => $this->normalize_room_status($payload['status']),
            'is_public' => (int) $payload['is_public'],
        );

        if ($data['status'] === 'mess') {
            $data['is_public'] = 0;
        }

        if ($payload['id'] > 0) {
            return $this->db->where('id', $payload['id'])->update('gh_rooms', $data);
        }

        return $this->db->insert('gh_rooms', $data);
    }

    public function toggle_room_public($id)
    {
        $room = $this->get_room($id);
        if ( ! $room || $room['status'] === 'mess') {
            return FALSE;
        }

        return $this->db
            ->where('id', $id)
            ->update('gh_rooms', array('is_public' => (int) ! (int) $room['is_public']));
    }

    public function soft_delete_room($id)
    {
        if ($id <= 0) {
            return FALSE;
        }

        return $this->db
            ->where('id', $id)
            ->update('gh_rooms', array(
                'status' => 'inactive',
                'is_public' => 0,
            ));
    }

    private function normalize_room_status($status)
    {
        $allowed = array('available', 'occupied', 'reserved', 'maintenance', 'mess', 'inactive');
        return in_array($status, $allowed, TRUE) ? $status : 'available';
    }
}
