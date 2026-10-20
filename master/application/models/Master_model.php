<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_model extends CI_Model
{
    private $payment_error = '';
    public function get_payment_error(){return $this->payment_error;}
    public function get_payment_quotes(array $billIds)
    {
        $billIds=array_values(array_unique(array_filter(array_map('intval',$billIds))));
        if(!$billIds)return array();
        $bills=$this->db->where_in('id',$billIds)->get('gh_room_bills')->result_array();$stayIds=array();
        foreach($bills as $b)if(!empty($b['stay_id']))$stayIds[]=(int)$b['stay_id'];
        $stays=array();$ledgers=array();$deposits=array();$rent=array();
        foreach($this->db->where_in('room_bill_id',$billIds)->where('payment_type','rent')->get('gh_payments')->result_array() as $p)$rent[$p['room_bill_id']]=($rent[$p['room_bill_id']]??0)+(int)$p['amount'];
        if($stayIds){
            foreach($this->db->where_in('id',$stayIds)->get('gh_tenant_stays')->result_array() as $s)$stays[$s['id']]=$s;
            foreach($this->db->where_in('stay_id',$stayIds)->get('gh_deposit_transactions')->result_array() as $l)$ledgers[$l['stay_id']][]=$l;
            foreach($this->db->select('pay.*,b.stay_id')->from('gh_payments pay')->join('gh_room_bills b','b.id=pay.room_bill_id')->where_in('b.stay_id',$stayIds)->where('pay.payment_type','deposit')->get()->result_array() as $p)$deposits[$p['stay_id']][]=$p;
        }
        $out=array();foreach($bills as $b)$out[$b['id']]=$this->settlement_quote($b,$stays[$b['stay_id']]??NULL,$rent[$b['id']]??0,$ledgers[$b['stay_id']]??array(),$deposits[$b['stay_id']]??array());return $out;
    }
    private function settlement_quote($bill,$stay,$rentPaid,array $ledgers,array $deposits)
    {
        $balance=0;$linked=array();$valid=TRUE;
        foreach($ledgers as $l){$balance+=in_array($l['transaction_type'],array('received','adjustment'),TRUE)?(int)$l['amount']:-(int)$l['amount'];if(!empty($l['source_payment_id']))$linked[(int)$l['source_payment_id']]=TRUE;if((int)$l['tenant_id']!==(int)($stay['tenant_id']??0))$valid=FALSE;}
        // Imported deposit payments without a ledger row still count as already received.
        foreach($deposits as $p)if(!isset($linked[(int)$p['id']]))$balance+=(int)$p['amount'];
        $rentAllowed=isset($bill['base_price'])&&is_numeric($bill['base_price'])&&(int)$bill['base_price']>=0&&!in_array($bill['bill_status'],array('empty','reserved','internal'),TRUE);
        $depositAllowed=$stay&&isset($stay['deposit'])&&is_numeric($stay['deposit'])&&(int)$stay['deposit']>=0&&$valid&&(int)$stay['tenant_id']===(int)$bill['tenant_id']&&(int)$stay['room_id']===(int)$bill['room_id']&&in_array($stay['stay_status'],array('reserved','active'),TRUE);
        $rent=max(0,(int)$bill['base_price']-(int)$rentPaid);$deposit=$depositAllowed?max(0,(int)$stay['deposit']-$balance):0;
        return array('rent_remaining'=>$rentAllowed?$rent:0,'deposit_remaining'=>$deposit,'rent_available'=>$rentAllowed,'deposit_available'=>(bool)$depositAllowed,'rent_reason'=>$rentAllowed?($rent>0?'':'Sewa sudah lunas.'):'Tagihan ini tidak menerima pembayaran sewa.','deposit_reason'=>$depositAllowed?($deposit>0?'':'Deposit masa tinggal sudah terpenuhi.'):'Deposit belum dapat dibayar: masa tinggal aktif/reservasi dan identitas tagihan harus terhubung secara pasti.','can_pay_rent'=>$rentAllowed&&$rent>0,'can_pay_deposit'=>$depositAllowed&&$deposit>0,'deposit_balance'=>$balance,'deposit_required'=>(int)($stay['deposit']??0),'method'=>'Transfer');
    }
    public function get_properties(array $filters = array())
    {
        $this->db->from('gh_properties');
        $this->apply_property_filters($filters);

        return $this->db
            ->order_by('code', 'ASC')
            ->get()
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
            'city' => trim((string) ($payload['city'] ?? '')) ?: null,
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

    public function get_room_types(array $filters = array())
    {
        $this->db
            ->select('rt.*')
            ->select('COUNT(r.id) AS room_count', FALSE)
            ->select('COUNT(DISTINCT p.id) AS property_count', FALSE)
            ->select('GROUP_CONCAT(DISTINCT p.code ORDER BY p.code SEPARATOR ", ") AS property_codes', FALSE)
            ->select('GROUP_CONCAT(DISTINCT p.property_type ORDER BY p.property_type SEPARATOR ", ") AS property_types', FALSE)
            ->from('gh_room_types rt')
            ->join('gh_rooms r', 'r.room_type_id = rt.id', 'left')
            ->join('gh_properties p', 'p.id = r.property_id', 'left');

        $this->apply_room_type_filters($filters);

        return $this->db
            ->group_by('rt.id')
            ->order_by('rt.name', 'ASC')
            ->get()
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

    public function get_rooms(array $filters = array())
    {
        $this->db
            ->select('r.*, p.code AS property_code, rt.name AS type_name')
            ->from('gh_rooms r')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->join('gh_room_types rt', 'rt.id = r.room_type_id', 'left');

        $this->apply_room_filters($filters);

        return $this->db
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
        if ($payload['property_id'] <= 0 || $payload['room_type_id'] <= 0 || $payload['room_code'] === '' || ($payload['deposit'] !== null && $payload['deposit'] < 0) || ($payload['monthly_price'] !== null && $payload['monthly_price'] < 0)) {
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

    public function get_tenant_stays(array $filters = array())
    {
        $this->db
            ->select('s.*, t.fullname, t.birth_place_date, t.religion, t.identity_number, t.phone, t.marital_status, t.occupation, t.address, ec.contact_name, ec.relationship, ec.phone AS emergency_phone, r.room_code, r.room_name, r.room_label, r.status AS room_status, r.is_public, p.code AS property_code, rt.name AS type_name')
            ->from('gh_tenant_stays s')
            ->join('gh_tenants t', 't.id = s.tenant_id', 'left')
            ->join('gh_tenant_emergency_contacts ec', 'ec.tenant_id = t.id', 'left')
            ->join('gh_rooms r', 'r.id = s.room_id', 'left')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->join('gh_room_types rt', 'rt.id = r.room_type_id', 'left');

        $this->apply_tenant_filters($filters);

        return $this->db
            ->order_by("FIELD(s.stay_status, 'active', 'reserved', 'internal', 'empty', 'ended')", '', FALSE)
            ->order_by('p.code', 'ASC')
            ->order_by('r.room_code', 'ASC')
            ->get()
            ->result_array();
    }

    public function save_tenant_stay(array $payload)
    {
        if ($payload['room_id'] <= 0 || ! $this->valid_flow_date($payload['check_in_date']) || (! empty($payload['check_out_date']) && (! $this->valid_flow_date($payload['check_out_date']) || $payload['check_out_date'] < $payload['check_in_date'])) || ($payload['monthly_price'] !== null && $payload['monthly_price'] < 0) || ($payload['deposit'] !== null && $payload['deposit'] < 0)) {
            return FALSE;
        }

        $stayStatus = $this->normalize_stay_status($payload['stay_status']);
        if (in_array($stayStatus, array('active', 'reserved'), TRUE) && $payload['fullname'] === '') {
            return FALSE;
        }
        if ($payload['identity_number'] !== '' && $this->db->where('identity_number', $payload['identity_number'])->where('id !=', (int) $payload['tenant_id'])->count_all_results('gh_tenants') > 0) return FALSE;
        if ($payload['stay_id'] > 0) {
            $existingStay = $this->db->where('id', $payload['stay_id'])->get('gh_tenant_stays')->row_array();
            if (! $existingStay || (int) $existingStay['tenant_id'] !== (int) $payload['tenant_id']) return FALSE;
            if ($this->db->table_exists('gh_resident_cases') && $this->db->where('stay_id',(int)$payload['stay_id'])->count_all_results('gh_resident_cases')) {
                // Linked physical/financial history belongs to this exact stay and tenant.
                foreach (array('room_id','stay_status','check_in_date','check_out_date','monthly_price','deposit') as $fixed) if ((string)$payload[$fixed] !== (string)$existingStay[$fixed]) return FALSE;
            }
        }

        $this->db->trans_start();

        $room = $this->db->query('SELECT * FROM gh_rooms WHERE id = ? FOR UPDATE', array($payload['room_id']))->row_array();
        $this->load->model('Operations_model');
        if (! $room || (in_array($stayStatus, array('active', 'reserved', 'internal'), TRUE) && $this->Operations_model->room_overlap($payload['room_id'], $payload['check_in_date'], $payload['check_out_date'] ?: '9999-12-31', $payload['stay_id']))) {
            $this->db->trans_complete();
            return FALSE;
        }
        if ($payload['stay_id'] > 0 && $this->db->where('stay_id', $payload['stay_id'])->where_in('status', array('active','expiring'))->count_all_results('gh_contracts') > 0) {
            $existingStay = $this->db->where('id', $payload['stay_id'])->get('gh_tenant_stays')->row_array();
            foreach (array('room_id', 'stay_status', 'check_in_date', 'check_out_date', 'monthly_price', 'deposit') as $fixed) {
                if ((string) $payload[$fixed] !== (string) $existingStay[$fixed]) { $this->db->trans_complete(); return FALSE; }
            }
        }
        if ($payload['stay_id'] <= 0 && $stayStatus === 'active') $stayStatus = 'reserved';

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
            $newStayId = (int) $this->db->insert_id();
            if ($tenantId && in_array($stayStatus, array('active', 'reserved'), TRUE) && $payload['check_out_date'] && $payload['monthly_price'] > 0) {
                $this->Operations_model->create_draft_for_stay($newStayId);
            }
        }

        $this->sync_room_from_stay($payload['room_id'], $stayStatus);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function end_tenant_stay($id)
    {
        if ($this->db->table_exists('gh_resident_cases') && $this->db->where('stay_id',(int)$id)->count_all_results('gh_resident_cases')) return FALSE;
        $stay = $this->db->where('id', $id)->get('gh_tenant_stays')->row_array();
        if ( ! $stay) {
            return FALSE;
        }
        if ($this->db->table_exists('gh_contracts') && $this->db->where('stay_id', $id)->where_in('status', array('active', 'expiring', 'draft'))->count_all_results('gh_contracts') > 0) return FALSE;

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
            ->select('COALESCE(SUM(CASE WHEN rb.bill_status IN ("unpaid","partial") AND rb.due_date < CURDATE() THEN GREATEST(CAST(rb.base_price AS DECIMAL(20,0))-CAST(rb.paid_total AS DECIMAL(20,0)),0) ELSE 0 END), 0) AS past_due_total', FALSE)
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

    public function get_payment_bill_options()
    {
        return $this->db->select('rb.id, rb.tenant_name_snapshot, rb.bill_status, bp.period_label, r.room_label, p.code AS property_code')->from('gh_room_bills rb')
            ->join('gh_billing_periods bp', 'bp.id=rb.period_id')->join('gh_rooms r', 'r.id=rb.room_id')->join('gh_properties p', 'p.id=r.property_id', 'left')
            ->where_in('rb.bill_status', array('unpaid','partial','paid','overpaid','unknown'))->order_by('bp.period_key', 'DESC')->order_by('r.room_label', 'ASC')->get()->result_array();
    }

    public function save_payment_bill(array $payload)
    {
        $this->payment_error='Tagihan bersifat baca-saja dan diterbitkan otomatis dari kesepakatan sewa.';
        return FALSE;
    }

    public function save_payment(array $payload)
    {
        $this->payment_error='';$id=(int)($payload['payment_id']??0);
        $oldPeek=$id>0?$this->db->where('id',$id)->get('gh_payments')->row_array():NULL;
        $billId=(int)($payload['room_bill_id']??($oldPeek['room_bill_id']??0));
        $peek=$billId>0?$this->db->where('id',$billId)->get('gh_room_bills')->row_array():NULL;
        if(!$peek){$this->payment_error='Tagihan pembayaran tidak ditemukan.';return FALSE;}
        $this->db->trans_begin();
        try {
            $stay=!empty($peek['stay_id'])?$this->db->query('SELECT * FROM gh_tenant_stays WHERE id=? FOR UPDATE',array($peek['stay_id']))->row_array():NULL;
            $bill=$this->db->query('SELECT * FROM gh_room_bills WHERE id=? FOR UPDATE',array($billId))->row_array();
            if(!$bill||(int)$bill['stay_id']!==(int)$peek['stay_id'])throw new RuntimeException('Hubungan tagihan berubah. Muat ulang formulir.');
            if($id>0){
                $old=$this->db->query('SELECT * FROM gh_payments WHERE id=? FOR UPDATE',array($id))->row_array();
                if(!$old||(int)$old['room_bill_id']!==$billId)throw new RuntimeException('Transaksi historis tidak dapat dipindahkan ke tagihan lain.');
                foreach(array('amount','payment_type','method','payment_date','payment_date_text','reference_no') as $field)if(array_key_exists($field,$payload)&&(string)$payload[$field] !== (string)$old[$field])throw new RuntimeException('Nilai, tipe, metode, tanggal, dan referensi pembayaran historis tidak dapat diubah. Hanya catatan yang dapat disunting.');
                $this->db->where('id',$id)->update('gh_payments',array('notes'=>trim((string)($payload['notes']??''))?:NULL));
            } else {
                $type=$payload['payment_type']??'';$amount=$payload['amount']??NULL;$date=$payload['payment_date']??'';$key=$payload['request_key']??'';
                if(!in_array($type,array('rent','deposit'),TRUE)||!is_numeric($amount)||(int)$amount<1||(string)(int)$amount!==(string)$amount||!$this->valid_flow_date($date)||!is_string($key)||!preg_match('/^[A-Za-z0-9_-]{16,100}$/D',$key))throw new RuntimeException('Pilih Sewa/Deposit, tanggal valid, nominal otomatis, dan muat ulang formulir pembayaran.');
                if(isset($payload['method'])&&$payload['method']!==''&&$payload['method']!=='Transfer')throw new RuntimeException('Pembayaran baru hanya menggunakan metode Transfer.');
                $source='REQUEST|'.hash('sha256',$key);$notes=trim((string)($payload['notes']??''));$dateText=trim((string)($payload['payment_date_text']??''));
                $duplicate=$this->db->query('SELECT * FROM gh_payments WHERE source_key=? FOR UPDATE',array($source))->row_array();
                if($duplicate){
                    if((int)$duplicate['room_bill_id']!==$billId||$duplicate['payment_type']!==$type||(int)$duplicate['amount']!==(int)$amount||$duplicate['payment_date']!==$date||$duplicate['method']!=='Transfer'||(string)$duplicate['notes']!==$notes||(string)$duplicate['payment_date_text']!==$dateText)throw new RuntimeException('Kunci transaksi sudah digunakan untuk data pembayaran berbeda.');
                    $this->db->trans_commit();return TRUE;
                }
                $payments=$this->db->query('SELECT * FROM gh_payments WHERE room_bill_id=? FOR UPDATE',array($billId))->result_array();$rent=0;foreach($payments as $p)if($p['payment_type']==='rent')$rent+=(int)$p['amount'];
                $ledger=$stay?$this->db->query('SELECT * FROM gh_deposit_transactions WHERE stay_id=? FOR UPDATE',array($stay['id']))->result_array():array();
                $deposits=$stay?$this->db->query("SELECT pay.* FROM gh_payments pay INNER JOIN gh_room_bills b ON b.id=pay.room_bill_id WHERE b.stay_id=? AND pay.payment_type='deposit' FOR UPDATE",array($stay['id']))->result_array():array();
                $quote=$this->settlement_quote($bill,$stay,$rent,$ledger,$deposits);$remaining=$quote[$type.'_remaining'];
                if(!$quote['can_pay_'.$type]||(int)$amount!==$remaining)throw new RuntimeException('Nominal harus sama dengan sisa '.($type==='rent'?'sewa':'deposit').' terkini. Muat ulang formulir; pembayaran kurang/lebih tidak disimpan.');
                $data=array('room_bill_id'=>$billId,'payment_date'=>$date,'payment_date_text'=>$dateText?:NULL,'amount'=>$remaining,'payment_type'=>$type,'method'=>'Transfer','reference_no'=>NULL,'notes'=>$notes?:NULL,'source_key'=>$source);
                if(!$this->db->insert('gh_payments',$data))throw new RuntimeException('Pembayaran belum tersimpan. Muat ulang dan periksa transaksi.');
                $paymentId=(int)$this->db->insert_id();
                if($type==='deposit')$this->db->insert('gh_deposit_transactions',array('source_payment_id'=>$paymentId,'tenant_id'=>$bill['tenant_id'],'room_id'=>$bill['room_id'],'stay_id'=>$stay['id'],'transaction_date'=>$date,'transaction_type'=>'received','amount'=>$remaining,'method'=>'Transfer','reference_no'=>NULL,'description'=>'Deposit dari transaksi pembayaran #'.$paymentId));
                $this->refresh_bill_payment_total($billId);
            }
            if(!$this->db->trans_status())throw new RuntimeException('Pembayaran gagal disimpan; tidak ada perubahan yang diterapkan.');
            $this->db->trans_commit();return TRUE;
        } catch(Throwable $e){$this->db->trans_rollback();$this->payment_error=$e instanceof RuntimeException?$e->getMessage():'Pembayaran belum tersimpan. Periksa tagihan dan coba kembali.';return FALSE;}
    }

    public function delete_payment($id)
    {
        $this->db->trans_begin();
        $payment = $this->db->query('SELECT * FROM gh_payments WHERE id=? FOR UPDATE', array((int) $id))->row_array();
        if ( ! $payment) {
            $this->db->trans_rollback();
            return FALSE;
        }
        if ($this->db->table_exists('gh_resident_cases')) {
            $linkedBill=$this->db->where('id',$payment['room_bill_id'])->get('gh_room_bills')->row_array();
            if ($linkedBill && $this->db->where('stay_id',(int)$linkedBill['stay_id'])->where('payment_verified_at IS NOT NULL',NULL,FALSE)->count_all_results('gh_resident_cases')) { $this->db->trans_rollback(); return FALSE; }
        }

        if (! $this->deposit_payment_can_change($payment, array('payment_type' => 'rent', 'room_bill_id' => $payment['room_bill_id'], 'amount' => 0))) {
            $this->db->trans_rollback();
            return FALSE;
        }
        $proofs = array();
        if ($this->db->table_exists('gh_payment_proofs')) {
            $this->load->model('Payment_proof_model');
            $proofs = $this->Payment_proof_model->lock_and_collect((int) $id);
            if ($proofs === FALSE) { $this->db->trans_rollback(); return FALSE; }
        }
        $this->db->where('id', $id)->delete('gh_payments');
        if ($this->db->table_exists('gh_deposit_transactions') && $this->db->field_exists('source_payment_id', 'gh_deposit_transactions')) {
            $this->db->where('source_payment_id', (int) $id)->delete('gh_deposit_transactions');
        }
        $this->refresh_bill_payment_total((int) $payment['room_bill_id']);
        if (! $this->db->trans_status()) { $this->db->trans_rollback(); return FALSE; }
        $this->db->trans_commit();
        if ($proofs) {
            $cleanup = $this->Payment_proof_model->cleanup_files($proofs);
            if ($cleanup['cleanup_pending']) $this->session->set_flashdata('warning', 'Pembayaran dihapus. Pembersihan berkas bukti privat tertunda dan tercatat untuk dicoba kembali.');
        }
        return TRUE;
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
            ->where('payment_type', 'rent')
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
                'past_due_amount' => $bill && ! in_array($status, array('empty', 'internal', 'reserved'), TRUE) && $bill['due_date'] && $bill['due_date'] < date('Y-m-d') ? max(0, (int) $bill['base_price'] - $total) : 0,
                'future_due_amount' => $bill && ! in_array($status, array('empty', 'internal', 'reserved'), TRUE) && (! $bill['due_date'] || $bill['due_date'] >= date('Y-m-d')) ? max(0, (int) $bill['base_price'] - $total) : 0,
            ));
    }

    private function valid_flow_date($value)
    {
        return is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts) && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }

    private function deposit_payment_can_change(array $payment, array $replacement)
    {
        if (! $this->db->field_exists('source_payment_id', 'gh_deposit_transactions')) return TRUE;
        $ledger = $this->db->where('source_payment_id', $payment['id'])->get('gh_deposit_transactions')->row_array();
        if (! $ledger) return TRUE;
        $this->db->query('SELECT id FROM gh_tenant_stays WHERE id = ? FOR UPDATE', array($ledger['stay_id']));
        $row = $this->db->select("COALESCE(SUM(CASE WHEN transaction_type IN ('received','adjustment') THEN amount ELSE -CAST(amount AS SIGNED) END),0) AS balance", FALSE)->where('stay_id', $ledger['stay_id'])->get('gh_deposit_transactions')->row_array();
        $sameDeposit = $replacement['payment_type'] === 'deposit' && (int) $replacement['room_bill_id'] === (int) $payment['room_bill_id'];
        return (int) $row['balance'] - (int) $ledger['amount'] + ($sameDeposit ? (int) $replacement['amount'] : 0) >= 0;
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

        if (isset($filters['has_payment']) && $filters['has_payment'] === '1') {
            $this->db->where('EXISTS (SELECT 1 FROM gh_payments ghp WHERE ghp.room_bill_id = rb.id)', null, FALSE);
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

    private function apply_property_filters(array $filters)
    {
        if (! empty($filters['type']) && in_array($filters['type'], array('house', 'apartment', 'other'), TRUE)) {
            $this->db->where('property_type', $filters['type']);
        }

        if (isset($filters['status']) && $filters['status'] === 'active') {
            $this->db->where('is_active', 1);
        } elseif (isset($filters['status']) && $filters['status'] === 'inactive') {
            $this->db->where('is_active', 0);
        }

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $this->db->group_start()
                ->like('code', $q)
                ->or_like('name', $q)
                ->or_like('city', $q)
                ->or_like('address', $q)
                ->group_end();
        }
    }

    private function apply_room_type_filters(array $filters)
    {
        if (! empty($filters['property_type']) && in_array($filters['property_type'], array('house', 'apartment', 'other'), TRUE)) {
            $this->db->where('p.property_type', $filters['property_type']);
        }

        if (isset($filters['status']) && $filters['status'] === 'active') {
            $this->db->where('rt.is_active', 1);
        } elseif (isset($filters['status']) && $filters['status'] === 'inactive') {
            $this->db->where('rt.is_active', 0);
        }

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $this->db->group_start()
                ->like('rt.name', $q)
                ->or_like('rt.description', $q)
                ->or_like('p.code', $q)
                ->group_end();
        }

        if (isset($filters['usage']) && $filters['usage'] === 'used') {
            $this->db->having('room_count >', 0);
        }
    }

    private function apply_room_filters(array $filters)
    {
        if (! empty($filters['property_id'])) {
            $this->db->where('r.property_id', (int) $filters['property_id']);
        }

        if (! empty($filters['room_type_id'])) {
            $this->db->where('r.room_type_id', (int) $filters['room_type_id']);
        }

        if (! empty($filters['status']) && in_array($filters['status'], array('available', 'occupied', 'reserved', 'maintenance', 'mess', 'inactive'), TRUE)) {
            $this->db->where('r.status', $filters['status']);
        }

        if (isset($filters['public']) && $filters['public'] === 'public') {
            $this->db->where('r.is_public', 1);
        } elseif (isset($filters['public']) && $filters['public'] === 'hidden') {
            $this->db->where('r.is_public', 0);
        }

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $this->db->group_start()
                ->like('r.room_code', $q)
                ->or_like('r.room_name', $q)
                ->or_like('r.room_label', $q)
                ->or_like('p.code', $q)
                ->or_like('rt.name', $q)
                ->group_end();
        }
    }

    private function apply_tenant_filters(array $filters)
    {
        if (! empty($filters['property_id'])) {
            $this->db->where('r.property_id', (int) $filters['property_id']);
        }

        if (! empty($filters['room_type_id'])) {
            $this->db->where('r.room_type_id', (int) $filters['room_type_id']);
        }

        if (! empty($filters['room_id'])) {
            $this->db->where('s.room_id', (int) $filters['room_id']);
        }

        if (! empty($filters['stay_status']) && in_array($filters['stay_status'], array('active', 'reserved', 'ended', 'internal', 'empty'), TRUE)) {
            $this->db->where('s.stay_status', $filters['stay_status']);
        }

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $this->db->group_start()
                ->like('t.fullname', $q)
                ->or_like('t.phone', $q)
                ->or_like('t.identity_number', $q)
                ->or_like('s.source_unit_name', $q)
                ->or_like('s.notes', $q)
                ->or_like('r.room_label', $q)
                ->or_like('r.room_code', $q)
                ->or_like('p.code', $q)
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
