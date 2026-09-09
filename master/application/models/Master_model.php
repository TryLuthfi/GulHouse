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

    public function get_active_rooms_for_stay()
    {
        return $this->db
            ->select('r.id, r.room_code, r.room_name, r.room_label, r.monthly_price, r.deposit, r.status, p.code AS property_code, rt.name AS type_name')
            ->from('gh_rooms r')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->join('gh_room_types rt', 'rt.id = r.room_type_id', 'left')
            ->where('p.is_active', 1)
            ->where('r.status !=', 'inactive')
            ->order_by('p.code', 'ASC')
            ->order_by('r.room_code', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_tenant_stays()
    {
        return $this->db
            ->select('s.*, t.fullname, t.birth_place_date, t.religion, t.identity_number, t.phone, t.marital_status, t.occupation, t.address, ec.contact_name, ec.relationship, ec.phone AS emergency_phone, r.room_code, r.room_name, r.room_label, r.status AS room_status, r.is_public, p.code AS property_code, rt.name AS type_name')
            ->from('gh_tenant_stays s')
            ->join('gh_tenants t', 't.id = s.tenant_id', 'left')
            ->join('gh_tenant_emergency_contacts ec', 'ec.tenant_id = t.id', 'left')
            ->join('gh_rooms r', 'r.id = s.room_id', 'left')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->join('gh_room_types rt', 'rt.id = r.room_type_id', 'left')
            ->order_by("FIELD(s.stay_status, 'active', 'reserved', 'internal', 'empty', 'ended')", '', FALSE)
            ->order_by('p.code', 'ASC')
            ->order_by('r.room_code', 'ASC')
            ->get()
            ->result_array();
    }

    public function save_tenant_stay(array $payload)
    {
        if ($payload['room_id'] <= 0) {
            return FALSE;
        }

        $stayStatus = $this->normalize_stay_status($payload['stay_status']);
        if (in_array($stayStatus, array('active', 'reserved'), TRUE) && $payload['fullname'] === '') {
            return FALSE;
        }

        $this->db->trans_start();

        $tenantId = null;
        if ($payload['fullname'] !== '') {
            $tenantData = array(
                'fullname' => $payload['fullname'],
                'birth_place_date' => $payload['birth_place_date'] !== '' ? $payload['birth_place_date'] : null,
                'religion' => $payload['religion'] !== '' ? $payload['religion'] : null,
                'identity_number' => $payload['identity_number'] !== '' ? $payload['identity_number'] : null,
                'phone' => $payload['phone'] !== '' ? $payload['phone'] : null,
                'marital_status' => $payload['marital_status'] !== '' ? $payload['marital_status'] : null,
                'occupation' => $payload['occupation'] !== '' ? $payload['occupation'] : null,
                'address' => $payload['address'] !== '' ? $payload['address'] : null,
                'source_label' => 'Master input',
            );

            if ($payload['tenant_id'] > 0) {
                $tenantId = $payload['tenant_id'];
                $this->db->where('id', $tenantId)->update('gh_tenants', $tenantData);
            } else {
                $this->db->insert('gh_tenants', $tenantData);
                $tenantId = (int) $this->db->insert_id();
            }

            $this->db->where('tenant_id', $tenantId)->delete('gh_tenant_emergency_contacts');
            if ($payload['emergency_name'] !== '' || $payload['emergency_phone'] !== '') {
                $this->db->insert('gh_tenant_emergency_contacts', array(
                    'tenant_id' => $tenantId,
                    'contact_name' => $payload['emergency_name'] !== '' ? $payload['emergency_name'] : null,
                    'relationship' => $payload['emergency_relationship'] !== '' ? $payload['emergency_relationship'] : null,
                    'phone' => $payload['emergency_phone'] !== '' ? $payload['emergency_phone'] : null,
                ));
            }
        }

        $stayData = array(
            'tenant_id' => $tenantId,
            'room_id' => $payload['room_id'],
            'stay_status' => $stayStatus,
            'check_in_date' => $payload['check_in_date'],
            'check_out_date' => $payload['check_out_date'],
            'monthly_price' => $payload['monthly_price'],
            'deposit' => $payload['deposit'],
            'source_period' => $payload['source_period'] !== '' ? $payload['source_period'] : null,
            'source_unit_name' => $payload['source_unit_name'] !== '' ? $payload['source_unit_name'] : null,
            'notes' => $payload['notes'] !== '' ? $payload['notes'] : null,
        );

        if ($payload['stay_id'] > 0) {
            $this->db->where('id', $payload['stay_id'])->update('gh_tenant_stays', $stayData);
        } else {
            $this->db->insert('gh_tenant_stays', $stayData);
        }

        $this->sync_room_from_stay($payload['room_id'], $stayStatus);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function end_tenant_stay($id)
    {
        $stay = $this->db->where('id', $id)->get('gh_tenant_stays')->row_array();
        if ( ! $stay) {
            return FALSE;
        }

        $this->db->trans_start();
        $this->db->where('id', $id)->update('gh_tenant_stays', array(
            'stay_status' => 'ended',
            'check_out_date' => date('Y-m-d'),
        ));
        $this->sync_room_from_stay((int) $stay['room_id'], 'ended');
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    private function normalize_room_status($status)
    {
        $allowed = array('available', 'occupied', 'reserved', 'maintenance', 'mess', 'inactive');
        return in_array($status, $allowed, TRUE) ? $status : 'available';
    }

    private function normalize_stay_status($status)
    {
        $allowed = array('active', 'reserved', 'ended', 'internal', 'empty');
        return in_array($status, $allowed, TRUE) ? $status : 'active';
    }

    private function sync_room_from_stay($roomId, $stayStatus)
    {
        $map = array(
            'active' => array('status' => 'occupied', 'is_public' => 0),
            'reserved' => array('status' => 'reserved', 'is_public' => 0),
            'internal' => array('status' => 'mess', 'is_public' => 0),
            'empty' => array('status' => 'available', 'is_public' => 1),
            'ended' => array('status' => 'available', 'is_public' => 1),
        );

        if ( ! isset($map[$stayStatus])) {
            return FALSE;
        }

        return $this->db
            ->where('id', $roomId)
            ->update('gh_rooms', $map[$stayStatus]);
    }
}
