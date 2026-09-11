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
            ->select('r.id, r.property_id, r.room_type_id, r.room_code, r.room_name, r.room_label, r.monthly_price, r.deposit, r.status, p.code AS property_code, rt.name AS type_name')
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

    public function get_rooms_for_payment_filters()
    {
        return $this->db
            ->select('r.id, r.property_id, r.room_type_id, r.room_code, r.room_label, p.code AS property_code, rt.name AS type_name')
            ->from('gh_rooms r')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->join('gh_room_types rt', 'rt.id = r.room_type_id', 'left')
            ->where('p.is_active', 1)
            ->where('r.status !=', 'inactive')
            ->order_by('p.code', 'ASC')
            ->order_by('rt.name', 'ASC')
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

    public function get_billing_periods()
    {
        if ( ! $this->db->table_exists('gh_billing_periods')) {
            return array();
        }

        return $this->db
            ->order_by('period_key', 'DESC')
            ->get('gh_billing_periods')
            ->result_array();
    }

    public function get_payment_bills(array $filters)
    {
        if ( ! $this->db->table_exists('gh_room_bills')) {
            return array();
        }

        $this->db
            ->select('rb.*, bp.period_key, bp.period_label, r.room_label, r.room_code, p.code AS property_code, rt.name AS type_name')
            ->select('pay.id AS payment_id, pay.payment_date, pay.payment_date_text, pay.amount AS payment_amount, pay.payment_type, pay.method, pay.reference_no, pay.notes AS payment_notes')
            ->from('gh_room_bills rb')
            ->join('gh_billing_periods bp', 'bp.id = rb.period_id')
            ->join('gh_rooms r', 'r.id = rb.room_id')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->join('gh_room_types rt', 'rt.id = r.room_type_id', 'left')
            ->join('gh_payments pay', 'pay.room_bill_id = rb.id', 'left');

        $this->apply_payment_filters($filters);

        return $this->db
            ->order_by('bp.period_key', 'DESC')
            ->order_by('p.code', 'ASC')
            ->order_by('r.room_code', 'ASC')
            ->order_by('pay.payment_date', 'ASC')
            ->limit(260)
            ->get()
            ->result_array();
    }

    public function get_payment_summary(array $filters)
    {
        $summary = array(
            'bills' => 0,
            'paid_total' => 0,
            'payment_count' => 0,
            'past_due_total' => 0,
            'empty_rooms' => 0,
        );

        if ( ! $this->db->table_exists('gh_room_bills')) {
            return $summary;
        }

        $this->db
            ->select('COUNT(DISTINCT rb.id) AS bills', FALSE)
            ->select('COALESCE(SUM(rb.paid_total), 0) AS paid_total', FALSE)
            ->select('COALESCE(SUM(rb.past_due_amount), 0) AS past_due_total', FALSE)
            ->select('SUM(CASE WHEN rb.bill_status = "empty" THEN 1 ELSE 0 END) AS empty_rooms', FALSE)
            ->from('gh_room_bills rb')
            ->join('gh_billing_periods bp', 'bp.id = rb.period_id')
            ->join('gh_rooms r', 'r.id = rb.room_id')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->join('gh_room_types rt', 'rt.id = r.room_type_id', 'left');

        $this->apply_payment_filters($filters);
        $row = $this->db->get()->row_array();

        if ($row) {
            $summary['bills'] = (int) $row['bills'];
            $summary['paid_total'] = (int) $row['paid_total'];
            $summary['past_due_total'] = (int) $row['past_due_total'];
            $summary['empty_rooms'] = (int) $row['empty_rooms'];
        }

        $this->db
            ->select('COUNT(pay.id) AS payment_count', FALSE)
            ->from('gh_payments pay')
            ->join('gh_room_bills rb', 'rb.id = pay.room_bill_id')
            ->join('gh_billing_periods bp', 'bp.id = rb.period_id')
            ->join('gh_rooms r', 'r.id = rb.room_id')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->join('gh_room_types rt', 'rt.id = r.room_type_id', 'left');
        $this->apply_payment_filters($filters);
        $countRow = $this->db->get()->row_array();
        $summary['payment_count'] = $countRow ? (int) $countRow['payment_count'] : 0;

        return $summary;
    }

    public function get_payment_import_issues()
    {
        if ( ! $this->db->table_exists('gh_payment_import_issues')) {
            return array();
        }

        return $this->db
            ->select('issue_type, COUNT(id) AS total', FALSE)
            ->group_by('issue_type')
            ->order_by('total', 'DESC')
            ->get('gh_payment_import_issues')
            ->result_array();
    }

    public function save_payment_bill(array $payload)
    {
        if ($payload['bill_id'] <= 0) {
            return FALSE;
        }

        $status = $this->normalize_bill_status($payload['bill_status']);
        $data = array(
            'base_price' => $payload['base_price'],
            'deposit_amount' => $payload['deposit_amount'],
            'due_date' => $payload['due_date'],
            'paid_total' => $payload['paid_total'] === null ? 0 : $payload['paid_total'],
            'past_due_amount' => $payload['past_due_amount'] === null ? 0 : $payload['past_due_amount'],
            'future_due_amount' => $payload['future_due_amount'] === null ? 0 : $payload['future_due_amount'],
            'bill_status' => $status,
            'notes' => $payload['notes'] !== '' ? $payload['notes'] : null,
        );

        return $this->db->where('id', $payload['bill_id'])->update('gh_room_bills', $data);
    }

    public function save_payment(array $payload)
    {
        if ($payload['room_bill_id'] <= 0 || $payload['amount'] === null || $payload['amount'] <= 0) {
            return FALSE;
        }

        $this->db->trans_start();
        $data = array(
            'room_bill_id' => $payload['room_bill_id'],
            'payment_date' => $payload['payment_date'],
            'payment_date_text' => $payload['payment_date_text'] !== '' ? $payload['payment_date_text'] : null,
            'amount' => $payload['amount'],
            'payment_type' => in_array($payload['payment_type'], array('rent', 'deposit', 'other'), TRUE) ? $payload['payment_type'] : 'rent',
            'method' => $payload['method'] !== '' ? $payload['method'] : null,
            'reference_no' => $payload['reference_no'] !== '' ? $payload['reference_no'] : null,
            'notes' => $payload['notes'] !== '' ? $payload['notes'] : null,
        );

        if ($payload['payment_id'] > 0) {
            $this->db->where('id', $payload['payment_id'])->update('gh_payments', $data);
        } else {
            $data['source_key'] = 'MASTER|' . date('YmdHis') . '|' . random_int(1000, 9999);
            $this->db->insert('gh_payments', $data);
        }

        $this->refresh_bill_payment_total($payload['room_bill_id']);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function delete_payment($id)
    {
        $payment = $this->db->where('id', $id)->get('gh_payments')->row_array();
        if ( ! $payment) {
            return FALSE;
        }

        $this->db->trans_start();
        $this->db->where('id', $id)->delete('gh_payments');
        $this->refresh_bill_payment_total((int) $payment['room_bill_id']);
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

    private function normalize_bill_status($status)
    {
        $allowed = array('empty', 'unpaid', 'partial', 'paid', 'overpaid', 'internal', 'reserved', 'unknown');
        return in_array($status, $allowed, TRUE) ? $status : 'unknown';
    }

    private function refresh_bill_payment_total($billId)
    {
        $row = $this->db
            ->select('COALESCE(SUM(amount), 0) AS total', FALSE)
            ->where('room_bill_id', $billId)
            ->get('gh_payments')
            ->row_array();
        $bill = $this->db->where('id', $billId)->get('gh_room_bills')->row_array();
        $total = $row ? (int) $row['total'] : 0;
        $status = $bill ? $bill['bill_status'] : 'unknown';

        if ($bill && ! in_array($status, array('empty', 'internal', 'reserved'), TRUE)) {
            $base = (int) $bill['base_price'];
            if ($total <= 0) {
                $status = 'unpaid';
            } elseif ($base > 0 && $total < $base) {
                $status = 'partial';
            } elseif ($base > 0 && $total > $base) {
                $status = 'overpaid';
            } else {
                $status = 'paid';
            }
        }

        $this->db
            ->where('id', $billId)
            ->update('gh_room_bills', array(
                'paid_total' => $total,
                'bill_status' => $status,
            ));
    }

    private function apply_payment_filters(array $filters)
    {
        if (! empty($filters['period_id'])) {
            $this->db->where('rb.period_id', (int) $filters['period_id']);
        }

        if (! empty($filters['property_id'])) {
            $this->db->where('r.property_id', (int) $filters['property_id']);
        }

        if (! empty($filters['room_type_id'])) {
            $this->db->where('r.room_type_id', (int) $filters['room_type_id']);
        }

        if (! empty($filters['room_id'])) {
            $this->db->where('rb.room_id', (int) $filters['room_id']);
        }

        if (! empty($filters['status'])) {
            $this->db->where('rb.bill_status', $filters['status']);
        }

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $this->db->group_start()
                ->like('r.room_label', $q)
                ->or_like('rb.tenant_name_snapshot', $q)
                ->or_like('rb.tenant_phone_snapshot', $q)
                ->group_end();
        }
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
