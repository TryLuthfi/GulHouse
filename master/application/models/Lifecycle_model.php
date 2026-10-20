<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once __DIR__.'/Billing_policy_model.php';

/** Shared, explicit resident lifecycle. Constructor never changes schema or data. */
class Lifecycle_model extends CI_Model
{
    private $pendingUploads = array();
    private function policy(){ return new Billing_policy_model($this->db); }
    public function ready() { foreach(array('gh_lifecycle_facility_catalog','gh_resident_cases','gh_resident_files','gh_facility_inspections','gh_facility_inspection_rows','gh_checkout_requests','gh_refund_payments','gh_resident_events') as $table) if (!$this->db->table_exists($table)) return FALSE; return TRUE; }
    private function fail($message) { throw new RuntimeException($message); }
    public function date_valid($s) { $d = DateTime::createFromFormat('!Y-m-d', (string) $s); return $d && $d->format('Y-m-d') === $s; }
    private function money($p, $key) { $s = isset($p[$key]) ? (string) $p[$key] : '0'; if (! preg_match('/^\d{1,12}$/D', $s)) $this->fail('Nominal harus angka rupiah nonnegatif.'); return (int) $s; }
    public function rooms() { return $this->db->select('r.id,r.room_label,r.room_code,r.status,r.monthly_price,r.deposit,p.code AS property_code')->from('gh_rooms r')->join('gh_properties p','p.id=r.property_id')->where('r.status','available')->get()->result_array(); }
    public function cases() { return $this->db->select('c.*,r.room_label,r.room_code')->from('gh_resident_cases c')->join('gh_rooms r','r.id=c.room_id')->order_by('c.id','DESC')->get()->result_array(); }
    public function get_case($id, $lock = FALSE) { return $lock ? $this->db->query('SELECT * FROM gh_resident_cases WHERE id=? FOR UPDATE', array((int) $id))->row_array() : $this->db->where('id',(int) $id)->get('gh_resident_cases')->row_array(); }
    public function by_code($code) { if (! preg_match('/^[a-f0-9]{64}$/D', $code)) return NULL; return $this->db->where('access_hash',hash('sha256',$code))->get('gh_resident_cases')->row_array(); }
    public function storage() { return dirname(__DIR__).'/private_lifecycle/'.substr(hash('sha256',$this->db->database),0,16); }
    public function file($id) { return $this->db->where('id',(int) $id)->get('gh_resident_files')->row_array(); }
    public function upload($caseId, $field, $kind, $inspectionId = NULL, $required = FALSE)
    {
        if (empty($_FILES[$field]) || (int) $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) { if ($required) $this->fail('Lampiran '.$field.' wajib diunggah.'); return NULL; }
        $f = $_FILES[$field];
        if ((int) $f['error'] !== UPLOAD_ERR_OK || $f['size'] < 1 || $f['size'] > 10*1024*1024 || ! is_uploaded_file($f['tmp_name'])) $this->fail('Unggah gagal. Batas berkas 10 MB.');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']); $types = array('application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png');
        if (! isset($types[$mime])) $this->fail('Gunakan PDF, JPG, atau PNG asli.');
        if ($mime === 'application/pdf' && file_get_contents($f['tmp_name'],FALSE,NULL,0,5) !== '%PDF-') $this->fail('PDF tidak valid.');
        if ($mime !== 'application/pdf' && ! @getimagesize($f['tmp_name'])) $this->fail('Gambar tidak valid.');
        $hash = hash_file('sha256',$f['tmp_name']);
        $old = $this->db->where(array('case_id'=>$caseId,'kind'=>$kind,'sha256'=>$hash))->get('gh_resident_files')->row_array();
        if ($old) { if ($kind === 'scan' && (int) $old['inspection_id'] !== (int) $inspectionId) $this->fail('Scan ini sudah terdaftar pada pemeriksaan lain.'); return (int) $old['id']; }
        $dir = $this->storage(); if (! is_dir($dir) && ! mkdir($dir,0700,TRUE)) $this->fail('Penyimpanan tidak tersedia.');
        $name = bin2hex(random_bytes(24)).'.'.$types[$mime]; if (! move_uploaded_file($f['tmp_name'],$dir.'/'.$name)) $this->fail('Berkas gagal disimpan.');
        $this->pendingUploads[] = $dir.'/'.$name;
        $this->db->insert('gh_resident_files',array('case_id'=>$caseId,'inspection_id'=>$inspectionId,'kind'=>$kind,'original_name'=>substr(basename($f['name']),0,180),'storage_name'=>$name,'mime'=>$mime,'sha256'=>$hash));
        return (int) $this->db->insert_id();
    }
    private function event($id,$type,$desc,$actor) { $this->db->insert('gh_resident_events',array('case_id'=>$id,'event_type'=>$type,'description'=>$desc,'actor'=>$actor)); }
    public function intake($p)
    {
        foreach (array('full_name','phone','planned_start','room_id','consent') as $k) if (empty($p[$k])) $this->fail('Nama, nomor HP, kamar, tanggal dan persetujuan wajib diisi.');
        if (strlen($p['full_name'])>160 || ! preg_match('/^[+0-9 ()-]{8,30}$/D',$p['phone']) || ! $this->date_valid($p['planned_start'])) $this->fail('Identitas awal atau tanggal tidak valid.');
        if (! $this->db->where('id',(int) $p['room_id'])->where('status','available')->count_all_results('gh_rooms')) $this->fail('Kamar tidak tersedia. Hubungi pengelola.');
        $code = bin2hex(random_bytes(32)); $this->pendingUploads=array(); $this->db->trans_begin();
        try {
            $this->db->insert('gh_resident_cases',array('access_hash'=>hash('sha256',$code),'full_name'=>trim($p['full_name']),'phone'=>trim($p['phone']),'room_id'=>(int) $p['room_id'],'planned_start'=>$p['planned_start'],'source'=>'website'));
            $id = (int) $this->db->insert_id(); $ktp = $this->upload($id,'ktp_file','ktp',NULL,TRUE); $marriage = $this->upload($id,'marriage_file','marriage');
            $this->db->where('id',$id)->update('gh_resident_cases',array('ktp_file_id'=>$ktp,'marriage_file_id'=>$marriage));
            $this->event($id,'intake','Identitas awal dan persetujuan penggunaan data diterima.','resident');
            if (! $this->db->trans_status()) $this->fail('Gagal menyimpan pendaftaran.');
            $this->db->trans_commit(); $this->pendingUploads=array(); return array('id'=>$id,'code'=>$code);
        } catch (Throwable $e) { $this->db->trans_rollback(); $this->cleanup_uploads(); throw $e; }
    }
    public function paid($c)
    {
        if (empty($c['stay_id'])) return FALSE;
        $s=$this->db->where('id',$c['stay_id'])->get('gh_tenant_stays')->row_array();
        $bill=$this->db->query('SELECT b.* FROM gh_room_bills b JOIN gh_billing_periods p ON p.id=b.period_id WHERE b.stay_id=? AND b.base_price>0 ORDER BY p.period_key ASC,b.id ASC LIMIT 1',array($c['stay_id']))->row_array();
        if (!$s || !$bill || (int)$s['monthly_price']<1) return FALSE;
        $v=$this->db->query("SELECT COALESCE(SUM(amount),0) total FROM gh_payments WHERE room_bill_id=? AND payment_type='rent'",array($bill['id']))->row_array();
        $draft=$this->db->select('monthly_price')->where('stay_id',$c['stay_id'])->where('status','draft')->order_by('id','ASC')->get('gh_contracts')->row_array();
        return (int)$v['total'] >= max((int)$s['monthly_price'],(int)$bill['base_price'],(int)($draft['monthly_price']??0));
    }
    public function enroll_existing($p,$actor)
    {
        $this->pendingUploads=array(); $this->db->trans_begin();
        try {
            $s=$this->db->query('SELECT * FROM gh_tenant_stays WHERE id=? FOR UPDATE',array((int)($p['stay_id']??0)))->row_array();
            if (!$s || !in_array($s['stay_status'],array('active','reserved'),TRUE) || !$s['tenant_id'] || !$this->date_valid($s['check_in_date'])) $this->fail('Pilih masa tinggal aktif/reservasi dengan tanggal masuk valid.');
            if ($this->db->where('stay_id',$s['id'])->count_all_results('gh_resident_cases')) $this->fail('Masa tinggal sudah mempunyai rekam siklus.');
            $t=$this->db->where('id',$s['tenant_id'])->get('gh_tenants')->row_array(); if (!$t) $this->fail('Data penghuni tidak ditemukan.');
            $profile=array('identity_number'=>$t['identity_number'],'address'=>$t['address'],'occupation'=>$t['occupation'],'birth_place_date'=>$t['birth_place_date'],'religion'=>$t['religion'],'marital_status'=>$t['marital_status']);
            $emergency=$this->db->where('tenant_id',$t['id'])->order_by('id','ASC')->get('gh_tenant_emergency_contacts')->row_array(); if ($emergency) $profile+=array('emergency_name'=>$emergency['contact_name'],'emergency_phone'=>$emergency['phone'],'emergency_relation'=>$emergency['relationship']);
            $code=bin2hex(random_bytes(32));
            $this->db->insert('gh_resident_cases',array('access_hash'=>hash('sha256',$code),'full_name'=>$t['fullname'],'phone'=>$t['phone']??'','room_id'=>$s['room_id'],'planned_start'=>$s['check_in_date'],'source'=>'existing','tenant_id'=>$t['id'],'stay_id'=>$s['id'],'status'=>$s['stay_status']==='active'?'active':'reserved','profile_json'=>json_encode($profile)));
            $id=(int)$this->db->insert_id(); $ktp=$this->upload($id,'ktp_file','ktp',NULL,TRUE);$marriage=$this->upload($id,'marriage_file','marriage');
            $this->db->where('id',$id)->update('gh_resident_cases',array('ktp_file_id'=>$ktp,'marriage_file_id'=>$marriage));
            if ($s['stay_status']==='reserved') {
                $this->load->model('Operations_model');
                $draft=$this->db->where('stay_id',$s['id'])->where('status','draft')->get('gh_contracts')->row_array();
                if (!$draft && !$this->Operations_model->create_draft_for_stay($s['id'])) $this->fail('Lengkapi tanggal akhir dan tarif reservasi sebelum onboarding.');
                $this->initial_bill($s); $newCase=$this->get_case($id); $this->attach_ktp($newCase,$s['id']);
            }
            $this->event($id,'enroll_existing','Penghuni existing ditautkan; data masa tinggal dan pembayaran sebelumnya dipertahankan.',$actor);
            if (!$this->db->trans_status()) $this->fail('Pendaftaran existing belum tersimpan.'); $this->db->trans_commit();$this->pendingUploads=array();return array('id'=>$id,'code'=>$code);
        } catch(Throwable $e) { $this->db->trans_rollback();$this->cleanup_uploads();throw $e; }
    }
    public function balance($c)
    {
        if (empty($c['stay_id'])) return 0;
        $v=$this->db->query("SELECT COALESCE(SUM(CASE WHEN transaction_type IN ('received','adjustment') THEN amount ELSE -CAST(amount AS SIGNED) END),0) total FROM gh_deposit_transactions WHERE stay_id=? AND tenant_id=?",array($c['stay_id'],$c['tenant_id']))->row_array(); return (int) $v['total'];
    }
    public function detail($id)
    {
        $c=$this->get_case($id); if (! $c) return NULL;
        $c['profile']=json_decode($c['profile_json']??'{}',TRUE)?:array(); $room=$this->db->where('id',$c['room_id'])->get('gh_rooms')->row_array(); $c['room_label']=$room['room_label']??''; $c['room_code']=$room['room_code']??'';
        $property=$room?$this->db->where('id',$room['property_id'])->get('gh_properties')->row_array():NULL; $c['property_code']=$property['code']??''; $c['display_room']=trim($c['property_code'].' · '.$c['room_label']);
        $checks=$this->db->where('case_id',$id)->order_by('check_date','DESC')->order_by('id','DESC')->get('gh_facility_inspections')->result_array();
        foreach ($checks as &$i) { $i['rows']=$this->db->where('inspection_id',$i['id'])->get('gh_facility_inspection_rows')->result_array(); $i['files']=$this->db->where('inspection_id',$i['id'])->get('gh_resident_files')->result_array(); $i['ocr']=json_decode($i['ocr_json']??'{}',TRUE)?:array(); } unset($i);
        return array('case'=>$c,'inspections'=>$checks,'files'=>$this->db->where('case_id',$id)->get('gh_resident_files')->result_array(),'checkout'=>$this->db->where('case_id',$id)->get('gh_checkout_requests')->row_array(),'refunds'=>$this->db->where('case_id',$id)->get('gh_refund_payments')->result_array(),'history'=>$this->db->where('case_id',$id)->order_by('id','DESC')->get('gh_resident_events')->result_array(),'inventory_items'=>$this->db->where('is_active',1)->get('gh_lifecycle_facility_catalog')->result_array(),'payment_verified'=>$this->paid($c)&&!empty($c['payment_verified_at']),'deposit_balance'=>$this->balance($c),'maintenance'=>$this->case_maintenance($c),'payment_summary'=>$this->payment_summary($c),'billing_policy'=>$this->policy()->summary($c));
    }
    public function payment_summary($c)
    {
        if (empty($c['stay_id'])) return NULL;
        $stay=$this->db->where('id',$c['stay_id'])->get('gh_tenant_stays')->row_array();
        if (!$stay || (int)$stay['tenant_id']!==(int)$c['tenant_id'] || (int)$stay['room_id']!==(int)$c['room_id']) return NULL;
        $bill=$this->db->query('SELECT b.*,p.period_key FROM gh_room_bills b JOIN gh_billing_periods p ON p.id=b.period_id WHERE b.stay_id=? AND b.base_price>0 ORDER BY p.period_key ASC,b.id ASC LIMIT 1',array($c['stay_id']))->row_array();
        $paid=$bill?$this->db->query("SELECT COALESCE(SUM(amount),0) amount FROM gh_payments WHERE room_bill_id=? AND payment_type='rent'",array($bill['id']))->row_array():array('amount'=>0);
        $balance=$this->balance($c);
        return array('stay_start'=>$stay['check_in_date'],'stay_end'=>$stay['check_out_date'],'monthly_price'=>(int)$stay['monthly_price'],'deposit_required'=>(int)$stay['deposit'],'deposit_balance'=>$balance,'invoice_id'=>$bill?(int)$bill['id']:NULL,'invoice_period_id'=>$bill?(int)$bill['period_id']:NULL,'invoice_period'=>$bill['period_key']??NULL,'invoice_amount'=>$bill?(int)$bill['base_price']:0,'invoice_due'=>$bill['due_date']??NULL,'rent_paid'=>(int)$paid['amount'],'rent_outstanding'=>$bill?max((int)$bill['base_price']-(int)$paid['amount'],0):0,'rent_credit'=>$bill?max((int)$paid['amount']-(int)$bill['base_price'],0):0,'deposit_credit'=>max($balance-(int)$stay['deposit'],0),'deposit_outstanding'=>$stay['stay_status']==='ended'?0:max((int)$stay['deposit']-$balance,0),'verified_at'=>$c['payment_verified_at'],'stay_status'=>$stay['stay_status']);
    }
    private function payment_proof($c,$p)
    {
        $summary=$this->payment_summary($c);
        if (!$summary || !in_array($summary['stay_status'],array('reserved','active'),TRUE) || $c['status']==='checked_out') $this->fail('Bukti pembayaran hanya untuk reservasi atau masa tinggal aktif.');
        if (isset($p['invoice_id']) && (int)$p['invoice_id']>0 && !$this->db->where('id',(int)$p['invoice_id'])->where('stay_id',$c['stay_id'])->count_all_results('gh_room_bills')) $this->fail('Tagihan bukan milik masa tinggal Anda.');
        $this->upload($c['id'],'payment_file','rent_proof',NULL,TRUE);
        // No payment, paid_total, deposit ledger or verified status is changed by uploading a claim.
    }
    private function case_maintenance($c)
    {
        if (empty($c['stay_id'])) return array();
        $s=$this->db->where('id',$c['stay_id'])->get('gh_tenant_stays')->row_array(); if (!$s) return array();
        $this->db->where('tenant_id',$c['tenant_id'])->where('room_id',$c['room_id'])->where('reported_at >=',$s['check_in_date']);
        if ($s['stay_status']==='ended' && $s['check_out_date']) $this->db->where('reported_at <=',$s['check_out_date']);
        return $this->db->order_by('reported_at','DESC')->get('gh_maintenance_tickets')->result_array();
    }
    public function action($id,$action,$p,$actor,$resident=FALSE)
    {
        if ($resident && ! in_array($action,array('full_profile','notice','payment_proof'),TRUE)) $this->fail('Aksi tidak diizinkan.');
        $this->pendingUploads=array(); $this->db->trans_begin();
        try {
            $c=$this->get_case($id,TRUE); if (! $c) $this->fail('Pendaftaran tidak ditemukan.');
            $eventDescription='Proses '.str_replace('_',' ',$action).' disimpan.';
            if ($action === 'reserve') {
                $this->reserve($c,$p);
            } elseif ($action==='change_room') {
                $eventDescription=$this->change_room($c,$p);
            } elseif (in_array($action,array('activate','check_in','checkin'),TRUE)) {
                $this->activate($c,$p);
            } elseif ($action === 'link_stay') {
                if ($c['stay_id']) $this->fail('Masa tinggal sudah ditautkan.');
                $s=$this->db->query('SELECT * FROM gh_tenant_stays WHERE id=? FOR UPDATE',array((int)($p['stay_id']??0)))->row_array();
                if (! $s || ! $s['tenant_id'] || !$this->link_identity_matches($c,$s) || $s['stay_status'] !== 'reserved') $this->fail('Penautan memerlukan identitas penghuni yang sudah terbukti dan kamar yang sama. Pendaftaran baru menggunakan Buat reservasi & tagihan awal.');
                $t=$this->db->where('id',$s['tenant_id'])->get('gh_tenants')->row_array();
                if (preg_replace('/\D/','',$t['phone']??'') !== preg_replace('/\D/','',$c['phone'])) $this->fail('Nomor HP reservasi tidak cocok.');
                $this->db->where('id',$id)->update('gh_resident_cases',array('stay_id'=>$s['id'],'tenant_id'=>$s['tenant_id'],'status'=>'reserved'));
                $this->initial_bill($s);
                $this->attach_ktp($c,$s['id']);
            } elseif ($action === 'verify_payment') {
                if (!in_array($c['status'],array('reserved','paid','active','notice'),TRUE) || (!empty($c['payment_verified_at']) && in_array($c['status'],array('active','notice'),TRUE))) $this->fail('Pembayaran awal sudah diverifikasi atau masa tinggal telah ditutup.');
                if (! $this->paid($c)) $this->fail('Pembayaran sewa riil belum lunas. Catat/verifikasi pembayaran pada modul Pembayaran terlebih dahulu.');
                $this->db->where('id',$id)->update('gh_resident_cases',array('payment_verified_at'=>date('Y-m-d H:i:s'),'status'=>in_array($c['status'],array('active','notice'),TRUE)?$c['status']:'paid'));
            } elseif ($action === 'payment_proof') { $this->payment_proof($c,$p);
            } elseif ($action==='penalty_payment') {
                $this->policy()->record_fee($c,$p,$actor,$this);
                $eventDescription='Pembayaran denda riil dicatat pada ledger denda terpisah; bukan pelunasan sewa atau deposit.';
            } elseif ($action==='overdue_checkout_review') {
                $review=$this->policy()->review_overdue($c,$p,$actor);
                $existing=$this->db->query('SELECT * FROM gh_checkout_requests WHERE case_id=? FOR UPDATE',array($c['id']))->row_array();
                if($existing){$update=$p;$update['revision_reason']='Peninjauan tunggakan H+7: '.($p['reason']??'');$this->notice_update($c,$update);}else $this->notice($c,$p);
                $this->db->where('case_id',$c['id'])->update('gh_checkout_requests',array('overdue_review_id'=>$review));
                $eventDescription='Tunggakan H+7 ditinjau admin. Pengajuan keluar dibuat; piutang tetap tercatat. Kamar belum dilepas dan checkout fisik belum selesai.';
            } elseif ($action === 'full_profile') { $this->full_profile($c,$p);
            } elseif ($action === 'inspection_save') { $this->save_inspection($c,$p);
            } elseif ($action === 'inspection_finalize') { $this->finalize_inspection($c,$p);
            } elseif ($action === 'inspection_extract') { $this->extract_inspection($c,$p);
            } elseif ($action === 'notice') { $this->notice($c,$p);
            } elseif ($action === 'notice_update') { $eventDescription=$this->notice_update($c,$p);
            } elseif ($action === 'checkout') { $this->checkout_case($c,$p,FALSE,$actor);
            } elseif ($action === 'checkout_overdue') { $this->checkout_case($c,$p,TRUE,$actor);$eventDescription='Checkout fisik setelah peninjauan tunggakan; piutang sewa/denda dipertahankan, akrual denda dibekukan pada tanggal keluar. Tidak ada potongan/refund otomatis.';
            } elseif ($action === 'refund') { $this->refund($c,$p);
            } elseif ($action === 'resolve_dispute') { $this->resolve_dispute($c,$p);
            } elseif ($action === 'maintenance') { $this->maintenance($c,$p);
            } else { $this->fail('Aksi tidak dikenal.'); }
            $this->event($id,$action,$eventDescription,$actor);
            if (! $this->db->trans_status()) $this->fail('Data gagal disimpan; periksa duplikasi atau hubungan data.');
            $this->db->trans_commit(); $this->pendingUploads=array(); return TRUE;
        } catch (Throwable $e) { $this->db->trans_rollback(); $this->cleanup_uploads(); throw $e; }
    }
    private function cleanup_uploads() { foreach ($this->pendingUploads as $path) if (is_file($path)) @unlink($path); $this->pendingUploads=array(); }
    private function reserve($c,$p)
    {
        if ($c['stay_id']) $this->fail('Reservasi sudah tersedia.');
        if(!$this->policy()->ready())$this->fail('Migrasi kebijakan sewa belum tersedia. Jalankan migrasi additive sebelum membuat reservasi baru.');
        if(!in_array($c['status'],array('intake','prospect'),TRUE))$this->fail('Pendaftaran tidak berada pada tahap calon penghuni.');
        $start=array_key_exists('start_date',$p)?$p['start_date']:$c['planned_start'];
        if (!is_string($start)) $this->fail('Tanggal mulai wajib berupa tanggal valid dengan format YYYY-MM-DD.');
        $start=trim($start);
        if (!$this->date_valid($start)) $this->fail('Tanggal mulai wajib berupa tanggal valid dengan format YYYY-MM-DD.');
        $end=$this->default_end($start);
        if (!$this->date_valid($end)) $this->fail('Tanggal mulai melewati batas periode sewa yang didukung.');
        $room=$this->db->query('SELECT * FROM gh_rooms WHERE id=? FOR UPDATE',array($c['room_id']))->row_array();
        $price=(int)($room['monthly_price']??0);$deposit=(int)($room['deposit']??0);
        if($price<1 || $deposit<0)$this->fail('Tarif/deposit master kamar belum valid. Lengkapi master kamar.');
        $this->load->model('Operations_model');
        if (!$room || in_array($room['status'],array('maintenance','inactive','mess'),TRUE) || $this->Operations_model->room_overlap($c['room_id'],$start,$end,0)) $this->fail('Kamar sudah terisi/bentrok.');
        $this->db->insert('gh_tenants',array('fullname'=>$c['full_name'],'phone'=>$c['phone'],'source_label'=>'Resident intake'));
        $tenantId=(int)$this->db->insert_id();
        $this->db->insert('gh_tenant_stays',array('tenant_id'=>$tenantId,'room_id'=>$c['room_id'],'stay_status'=>'reserved','check_in_date'=>$start,'check_out_date'=>$end,'monthly_price'=>$price,'deposit'=>$deposit,'source_period'=>'Booking','notes'=>'Resident lifecycle #'.$c['id']));
        $stayId=(int)$this->db->insert_id();
        if (!$this->Operations_model->create_draft_for_stay($stayId)) $this->fail('Kontrak draft belum berhasil dibuat.');
        $this->db->where('id',$c['id'])->update('gh_resident_cases',array('stay_id'=>$stayId,'tenant_id'=>$tenantId,'status'=>'reserved','planned_start'=>$start));
        $s=$this->db->where('id',$stayId)->get('gh_tenant_stays')->row_array(); $this->initial_bill($s); $this->attach_ktp($c,$stayId);
        $bill=$this->db->where('stay_id',$stayId)->get('gh_room_bills')->row_array();$this->policy()->enroll_reservation($this->get_case($c['id']),$bill);
        $this->db->where('id',$c['room_id'])->update('gh_rooms',array('status'=>'reserved'));
    }
    public function default_end($start)
    {
        return $this->calendar_boundary($start,1);
    }
    public function calendar_boundary($originalStart,$monthOffset=1)
    {
        if(!$this->date_valid($originalStart) || !is_int($monthOffset) || $monthOffset<0 || $monthOffset>1200)return '';
        $start=DateTimeImmutable::createFromFormat('!Y-m-d',$originalStart,new DateTimeZone('Asia/Jakarta'));
        $month=$start->modify('first day of this month')->modify('+'.$monthOffset.' months');
        return $month->setDate((int)$month->format('Y'),(int)$month->format('m'),min((int)$start->format('d'),(int)$month->format('t')))->format('Y-m-d');
    }
    private function link_identity_matches($c,$s)
    {
        if(empty($c['tenant_id']) || (int)$c['tenant_id']!==(int)$s['tenant_id'] || (int)$c['room_id']!==(int)$s['room_id'])return FALSE;
        return !$this->db->where('stay_id',$s['id'])->where('id !=',$c['id'])->count_all_results('gh_resident_cases');
    }
    public function link_stays($c)
    {
        if(empty($c['tenant_id']) || !empty($c['stay_id']))return array();
        $rows=$this->db->select('s.*,t.fullname,r.room_label,r.room_code')->from('gh_tenant_stays s')->join('gh_tenants t','t.id=s.tenant_id')->join('gh_rooms r','r.id=s.room_id')->where('s.tenant_id',$c['tenant_id'])->where('s.room_id',$c['room_id'])->where('s.stay_status','reserved')->get()->result_array();
        return array_values(array_filter($rows,function($s)use($c){return $this->link_identity_matches($c,$s);}));
    }
    private function room_change_blockers($c,$stay=NULL)
    {
        $errors=array();
        if(!in_array($c['status'],array('intake','prospect','reserved','paid'),TRUE) || ($stay && $stay['stay_status']!=='reserved'))$errors[]='Ubah kamar hanya tersedia sebelum check-in.';
        if($this->db->where('case_id',$c['id'])->count_all_results('gh_facility_inspections'))$errors[]='Sudah ada pemeriksaan fasilitas untuk kamar asal. Dokumen/pemeriksaan harus ditinjau sebelum pindah kamar.';
        if($stay) {
            $contracts=$this->db->where('stay_id',$stay['id'])->get('gh_contracts')->result_array();
            if(!$contracts)$errors[]='Kontrak draft belum tersedia.';
            foreach($contracts as $contract) {
                if($contract['status']!=='draft' || !empty($contract['signed_at']))$errors[]='Kontrak telah aktif, ditutup, atau ditandatangani; perubahan kamar diblokir.';
                $docs=$this->db->where('contract_id',$contract['id'])->where_in('document_type',array('contract','handover','inventory','statement'))->get('gh_contract_documents')->result_array();
                foreach($docs as $doc)if(in_array($doc['status'],array('received','verified'),TRUE)||!empty($doc['file_path']))$errors[]='Dokumen kesepakatan kamar sudah diterima/ditandatangani; lakukan peninjauan dokumen terlebih dahulu.';
            }
            $bills=$this->db->where('stay_id',$stay['id'])->get('gh_room_bills')->result_array();
            if(count($bills)!==1)$errors[]='Perubahan otomatis hanya mendukung satu tagihan awal. Tagihan multi-periode perlu rekonsiliasi admin terlebih dahulu.';
            elseif((int)$bills[0]['tenant_id']!==(int)$c['tenant_id'] || (int)$bills[0]['room_id']!==(int)$c['room_id'] || (int)$bills[0]['base_price']!==(int)$stay['monthly_price'])$errors[]='Hubungan/nilai tagihan awal berbeda dari kesepakatan reservasi. Rekonsiliasi terlebih dahulu.';
        }
        return array_values(array_unique($errors));
    }
    public function administration($c)
    {
        $room=$this->db->where('id',$c['room_id'])->get('gh_rooms')->row_array();
        $stay=!empty($c['stay_id'])?$this->db->where('id',$c['stay_id'])->get('gh_tenant_stays')->row_array():NULL;
        $start=$stay?$stay['check_in_date']:$c['planned_start'];$end=$stay?$stay['check_out_date']:$this->default_end($start);
        $summary=$this->payment_summary($c);$price=(int)($stay?$stay['monthly_price']:($room['monthly_price']??0));$deposit=(int)($stay?$stay['deposit']:($room['deposit']??0));
        $current=array('monthly_price'=>$price,'deposit'=>$deposit,'rent_paid'=>(int)($summary['rent_paid']??0),'deposit_paid'=>$this->balance($c));
        $this->load->model('Operations_model');$choices=array();
        foreach($this->db->select('r.*,p.code AS property_code')->from('gh_rooms r')->join('gh_properties p','p.id=r.property_id')->where_not_in('r.status',array('inactive','mess'))->order_by('r.id')->get()->result_array() as $r) {
            $overlap=$end!==''?$this->Operations_model->room_overlap($r['id'],$start,$end,(int)($c['stay_id']??0)):TRUE;
            $choices[]=array('id'=>(int)$r['id'],'room_label'=>$r['room_label'],'room_code'=>$r['room_code'],'property_code'=>$r['property_code'],'monthly_price'=>(int)$r['monthly_price'],'deposit'=>(int)$r['deposit'],'is_current'=>(int)$r['id']===(int)$c['room_id'],'available'=>$r['status']==='available' && (int)$r['monthly_price']>0 && !$overlap,'overlap'=>$overlap,'financial'=>array('rent_delta'=>(int)$r['monthly_price']-$price,'deposit_delta'=>(int)$r['deposit']-$deposit,'rent_outstanding'=>max((int)$r['monthly_price']-$current['rent_paid'],0),'rent_credit'=>max($current['rent_paid']-(int)$r['monthly_price'],0),'deposit_outstanding'=>max((int)$r['deposit']-$current['deposit_paid'],0),'deposit_credit'=>max($current['deposit_paid']-(int)$r['deposit'],0)));
        }
        $blockers=$this->room_change_blockers($c,$stay);
        return array('reservation_quote'=>array('start_date'=>$start,'end_date'=>$end,'period_boundary'=>$this->calendar_boundary($start,1),'period_anchor_day'=>(int)substr($start,8,2),'is_actual_checkout_date'=>FALSE,'monthly_price'=>$price,'deposit'=>$deposit,'room_id'=>(int)$c['room_id']),'link_stays'=>$this->link_stays($c),'room_choices'=>$choices,'room_change'=>array('allowed'=>!$blockers,'blockers'=>$blockers,'original_room_id'=>(int)$c['room_id'],'current'=>$current));
    }
    private function change_room($c,$p)
    {
        $reason=is_string($p['reason']??NULL)?trim($p['reason']):'';$target=(int)($p['room_id']??0);
        if(mb_strlen($reason)<10 || mb_strlen($reason)>1000 || (string)($p['resident_confirmed']??'')!=='1')$this->fail('Isi alasan 10–1.000 karakter dan konfirmasi persetujuan penghuni.');
        if(!$target || $target===(int)$c['room_id'])$this->fail('Pilih kamar tujuan yang berbeda.');
        $stay=!empty($c['stay_id'])?$this->db->query('SELECT * FROM gh_tenant_stays WHERE id=? FOR UPDATE',array((int)$c['stay_id']))->row_array():NULL;
        if(!empty($c['stay_id']) && (!$stay || (int)$stay['tenant_id']!==(int)$c['tenant_id'] || (int)$stay['room_id']!==(int)$c['room_id']))$this->fail('Hubungan reservasi tidak sesuai.');
        $ids=array((int)$c['room_id'],$target);sort($ids,SORT_NUMERIC);
        $rooms=$this->db->query('SELECT * FROM gh_rooms WHERE id IN ('.implode(',',$ids).') ORDER BY id FOR UPDATE')->result_array();$map=array();foreach($rooms as $r)$map[(int)$r['id']]=$r;
        $old=$map[(int)$c['room_id']]??NULL;$new=$map[$target]??NULL;
        if(!$old || !$new || $new['status']!=='available' || (int)$new['monthly_price']<1 || (int)$new['deposit']<0)$this->fail('Kamar tujuan tidak tersedia atau tarif master belum valid.');
        if($stay) {
            $this->db->query('SELECT id FROM gh_contracts WHERE stay_id=? FOR UPDATE',array($stay['id']));
            $this->db->query('SELECT id FROM gh_contract_documents WHERE contract_id IN (SELECT id FROM gh_contracts WHERE stay_id=?) FOR UPDATE',array($stay['id']));
            $this->db->query('SELECT id FROM gh_room_bills WHERE stay_id=? FOR UPDATE',array($stay['id']));
            $this->db->query('SELECT id FROM gh_facility_inspections WHERE case_id=? FOR UPDATE',array($c['id']));
        }
        $blockers=$this->room_change_blockers($c,$stay);if($blockers)$this->fail(implode(' ',$blockers));
        $start=$stay?$stay['check_in_date']:$c['planned_start'];$end=$stay?$stay['check_out_date']:$this->default_end($start);
        $this->load->model('Operations_model');if(!$this->date_valid($end) || $this->Operations_model->room_overlap($target,$start,$end,(int)($c['stay_id']??0)))$this->fail('Kamar tujuan bentrok dengan masa tinggal lain.');
        $rentPaid=0;$depositPaid=$this->balance($c);
        if($stay) {
            $bill=$this->db->where('stay_id',$stay['id'])->get('gh_room_bills')->row_array();
            $payments=$this->db->query('SELECT * FROM gh_payments WHERE room_bill_id=? FOR UPDATE',array($bill['id']))->result_array();$paymentIds=array();
            foreach($payments as $payment){$paymentIds[]=(int)$payment['id'];if($payment['payment_type']==='rent')$rentPaid+=(int)$payment['amount'];}
            $ledger=$this->db->query('SELECT * FROM gh_deposit_transactions WHERE stay_id=? FOR UPDATE',array($stay['id']))->result_array();
            $depositPaid=0;
            foreach($ledger as $entry) {
                if((int)$entry['tenant_id']!==(int)$c['tenant_id'] || (!empty($entry['source_payment_id'])&&!in_array((int)$entry['source_payment_id'],$paymentIds,TRUE)))$this->fail('Ledger deposit mempunyai relasi pembayaran/penghuni lain. Rekonsiliasi terlebih dahulu.');
                $depositPaid+=in_array($entry['transaction_type'],array('received','adjustment'),TRUE)?(int)$entry['amount']:-(int)$entry['amount'];
            }
            if($depositPaid<0)$this->fail('Saldo deposit tidak valid. Rekonsiliasi terlebih dahulu.');
            $price=(int)$new['monthly_price'];$deposit=(int)$new['deposit'];$remaining=max($price-$rentPaid,0);
            $status=$rentPaid<=0?'unpaid':($rentPaid<$price?'partial':($rentPaid>$price?'overpaid':'paid'));
            $this->db->where('id',$stay['id'])->update('gh_tenant_stays',array('room_id'=>$target,'monthly_price'=>$price,'deposit'=>$deposit));
            $this->db->where('stay_id',$stay['id'])->where('status','draft')->update('gh_contracts',array('room_id'=>$target,'monthly_price'=>$price,'deposit_amount'=>$deposit));
            $this->db->where('id',$bill['id'])->update('gh_room_bills',array('room_id'=>$target,'base_price'=>$price,'deposit_amount'=>$deposit,'paid_total'=>$rentPaid,'bill_status'=>$status,'past_due_amount'=>$bill['due_date']&&$bill['due_date']<date('Y-m-d')?$remaining:0,'future_due_amount'=>!$bill['due_date']||$bill['due_date']>=date('Y-m-d')?$remaining:0));
            $verified=$c['payment_verified_at'];if($rentPaid<$price || $depositPaid<$deposit)$verified=NULL;
            $this->db->where('id',$c['id'])->update('gh_resident_cases',array('room_id'=>$target,'payment_verified_at'=>$verified,'status'=>$verified?'paid':'reserved'));
            $this->db->where('id',$target)->update('gh_rooms',array('status'=>'reserved'));
            $others=$this->db->where('room_id',$old['id'])->where('id !=',$stay['id'])->where_in('stay_status',array('active','reserved','internal'))->count_all_results('gh_tenant_stays');
            if(!$others && in_array($old['status'],array('reserved','occupied'),TRUE))$this->db->where('id',$old['id'])->update('gh_rooms',array('status'=>'available'));
        } else $this->db->where('id',$c['id'])->update('gh_resident_cases',array('room_id'=>$target));
        return 'Kamar '.$old['room_label'].' (#'.$old['id'].') → '.$new['room_label'].' (#'.$new['id'].'). Alasan: '.$reason.'. Persetujuan penghuni dikonfirmasi. Tarif baru '.(int)$new['monthly_price'].', deposit '.(int)$new['deposit'].'. Sewa riil '.$rentPaid.', saldo deposit '.$depositPaid.'. Sisa sewa '.max((int)$new['monthly_price']-$rentPaid,0).', kredit sewa '.max($rentPaid-(int)$new['monthly_price'],0).', sisa deposit '.max((int)$new['deposit']-$depositPaid,0).', kredit deposit '.max($depositPaid-(int)$new['deposit'],0).'. Transaksi lama dipertahankan; tidak ada transfer/refund otomatis.';
    }
    private function initial_bill($s)
    {
        if ($this->db->where('stay_id',$s['id'])->count_all_results('gh_room_bills')) return;
        $key=substr($s['check_in_date'],0,7); $period=$this->db->where('period_key',$key)->get('gh_billing_periods')->row_array();
        if (!$period) { $this->db->insert('gh_billing_periods',array('period_key'=>$key,'period_label'=>date('F Y',strtotime($s['check_in_date'])),'period_month'=>(int)substr($key,5,2),'period_year'=>(int)substr($key,0,4),'source_file'=>'RESIDENT_INITIAL')); $periodId=(int)$this->db->insert_id(); } else $periodId=(int)$period['id'];
        $t=$this->db->where('id',$s['tenant_id'])->get('gh_tenants')->row_array();
        $this->db->insert('gh_room_bills',array('period_id'=>$periodId,'room_id'=>$s['room_id'],'stay_id'=>$s['id'],'tenant_id'=>$s['tenant_id'],'tenant_name_snapshot'=>$t['fullname'],'tenant_phone_snapshot'=>$t['phone'],'base_price'=>$s['monthly_price'],'deposit_amount'=>$s['deposit'],'paid_total'=>0,'past_due_amount'=>0,'future_due_amount'=>$s['monthly_price'],'vacant_amount'=>0,'bill_status'=>'unpaid','due_date'=>$s['check_in_date'],'source_sheet'=>'RESIDENT_INITIAL','source_period'=>$key,'notes'=>'Sewa awal sebelum check-in; pembayaran harus diverifikasi.'));
    }
    private function attach_ktp($c,$stayId)
    {
        // Preserve private lifecycle storage; metadata links do not expose a public path.
        $contract=$this->db->where('stay_id',$stayId)->where('status','draft')->get('gh_contracts')->row_array();
        if ($contract && $c['ktp_file_id']) $this->db->where('contract_id',$contract['id'])->where('document_type','ktp')->update('gh_contract_documents',array('status'=>'received','received_at'=>date('Y-m-d'),'notes'=>'KTP privat tersedia pada Siklus Penghuni #'.$c['id']));
    }
    private function activate($c,$p)
    {
        $error=$this->activation_error($c['stay_id']); if ($error!=='') $this->fail($error);
        if (empty($p['contract_signed']) || empty($p['inspection_confirmed'])) $this->fail('Konfirmasi kontrak bertanda tangan dan pemeriksaan fisik wajib.');
        $contract=$this->db->where('stay_id',$c['stay_id'])->where('status','draft')->order_by('id','DESC')->get('gh_contracts')->row_array();
        if (!$contract || $this->balance($c)<(int)$contract['deposit_amount']) $this->fail('Kontrak draft atau pelunasan deposit belum tersedia.');
        $this->db->where('contract_id',$contract['id'])->where('document_type','contract')->update('gh_contract_documents',array('status'=>'verified','received_at'=>date('Y-m-d'),'notes'=>'Kontrak fisik bertanda tangan dikonfirmasi pada Siklus Penghuni.'));
        $this->load->model('Operations_model');
        if (!$this->Operations_model->contract_action($contract['id'],'activate',array('inspection_confirmed'=>1,'signed_at'=>date('Y-m-d')))) $this->fail($this->Operations_model->get_contract_action_error() ?: 'Aktivasi ditolak. Periksa tanggal/kamar.');
        $this->db->where('id',$c['id'])->update('gh_resident_cases',array('status'=>'active'));
    }
    private function full_profile($c,$p)
    {
        if (empty($c['payment_verified_at']) || ! $this->paid($c) || ! empty($c['profile_completed_at'])) $this->fail('Biodata lengkap hanya setelah pembayaran diverifikasi, dan dikunci setelah diserahkan.');
        foreach (array('identity_number','address','birth_date','gender','occupation','emergency_name','emergency_phone','emergency_relation','emergency_address','consent') as $k) if (empty($p[$k])) $this->fail('Lengkapi identitas, alamat, pekerjaan, kontak darurat beserta alamat dan persetujuan.');
        if (! preg_match('/^\d{16}$/D',$p['identity_number']) || ! $this->date_valid($p['birth_date']) || $p['birth_date']>date('Y-m-d') || !in_array($p['gender'],array('male','female'),TRUE) || ! preg_match('/^[+0-9 ()-]{8,30}$/D',$p['emergency_phone'])) $this->fail('NIK harus 16 digit; tanggal lahir, jenis kelamin atau telepon darurat tidak valid.');
        if (!empty($p['email']) && !filter_var($p['email'],FILTER_VALIDATE_EMAIL)) $this->fail('Alamat email tidak valid.');
        if ($this->db->where('identity_number',$p['identity_number'])->where('id !=',(int)$c['tenant_id'])->count_all_results('gh_tenants')) $this->fail('NIK sudah tercatat pada penghuni lain. Hubungi pengelola untuk pemeriksaan identitas.');
        $data=json_decode($c['profile_json']??'{}',TRUE)?:array();
        foreach (array('identity_number','address','birth_date','birth_place','gender','religion','marital_status','occupation','email','company','vehicle_type','vehicle_plate','emergency_name','emergency_phone','emergency_relation','emergency_address') as $k) {
            if (isset($p[$k]) && trim((string)$p[$k])!=='') $data[$k]=substr(trim($p[$k]),0,1000);
            elseif (!isset($data[$k])) $data[$k]='';
        }
        $this->db->where('id',$c['id'])->update('gh_resident_cases',array('profile_json'=>json_encode($data),'profile_completed_at'=>date('Y-m-d H:i:s')));
        $tenantUpdate=array('identity_number'=>$data['identity_number'],'address'=>$data['address'],'occupation'=>$data['occupation']);
        foreach(array('religion','marital_status') as $field) if (!empty($data[$field])) $tenantUpdate[$field]=$data[$field];
        if (!empty($data['birth_place'])) $tenantUpdate['birth_place_date']=$data['birth_place'].', '.$data['birth_date'];
        $this->db->where('id',$c['tenant_id'])->update('gh_tenants',$tenantUpdate);
        $this->db->where('tenant_id',$c['tenant_id'])->delete('gh_tenant_emergency_contacts');
        $this->db->insert('gh_tenant_emergency_contacts',array('tenant_id'=>$c['tenant_id'],'contact_name'=>$data['emergency_name'],'phone'=>$data['emergency_phone'],'relationship'=>$data['emergency_relation']));
    }
    private function inspection($id,$inspectionId)
    {
        $i=$this->db->where('case_id',$id)->where('id',(int)$inspectionId)->get('gh_facility_inspections')->row_array(); if (! $i) $this->fail('Pemeriksaan tidak ditemukan.'); return $i;
    }
    private function save_inspection($c,$p)
    {
        if (! $c['stay_id']) $this->fail('Tautkan masa tinggal terlebih dahulu.');
        $stay=$this->db->where('id',$c['stay_id'])->get('gh_tenant_stays')->row_array();
        if (!$stay || (int)$stay['tenant_id']!==(int)$c['tenant_id'] || (int)$stay['room_id']!==(int)$c['room_id']) $this->fail('Hubungan kamar/penghuni tidak sesuai.');
        $stage=$p['stage']??''; $date=$p['check_date']??''; $period=$p['period']??'';
        if (! in_array($stage,array('check_in','routine','check_out'),TRUE) || ! $this->date_valid($date) || $date>date('Y-m-d') || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D',$period) || substr($date,0,7)!==$period || empty($p['officer_name'])) $this->fail('Jenis, tanggal aktual, periode, dan petugas wajib valid.');
        $legacyHandover=$c['source']==='existing' && $stay['stay_status']==='active';
        if ($date<$stay['check_in_date'] || ($stage==='check_in' && $stay['stay_status']!=='reserved' && !$legacyHandover && empty($p['revision_of'])) || ($stage!=='check_in' && $stay['stay_status']!=='active' && empty($p['revision_of']))) $this->fail('Jenis/tanggal pemeriksaan harus sesuai masa tinggal.');
        $iid=(int)($p['inspection_id']??0);
        if ($iid) {
            $i=$this->inspection($c['id'],$iid); if ($i['status']!=='draft') $this->fail('Dokumen final terkunci. Buat revisi baru.');
            if ($stage!==$i['stage'] || $date!==$i['check_date'] || $period!==$i['period']) $this->fail('Jenis, tanggal dan periode draft harus tetap; buat pemeriksaan baru untuk koreksi.');
            $this->db->where('inspection_id',$iid)->delete('gh_facility_inspection_rows');
            $this->db->where('id',$iid)->update('gh_facility_inspections',array('officer_name'=>substr($p['officer_name'],0,120),'notes'=>substr($p['notes']??'',0,5000)));
        } else {
            $revision=(int)($p['revision_of']??0);
            if ($revision) { $old=$this->inspection($c['id'],$revision); if ($old['status']!=='final' || $old['stage']!==$stage || empty($p['notes'])) $this->fail('Revisi wajib merujuk dokumen final sejenis dan mencantumkan alasan.'); }
            $this->db->insert('gh_facility_inspections',array('case_id'=>$c['id'],'tenant_id'=>$c['tenant_id'],'stay_id'=>$c['stay_id'],'room_id'=>$c['room_id'],'stage'=>$stage,'check_date'=>$date,'period'=>$period,'officer_name'=>substr($p['officer_name'],0,120),'notes'=>substr($p['notes']??'',0,5000),'revision_of'=>$revision?:NULL)); $iid=(int)$this->db->insert_id();
        }
        $items=$this->db->where('is_active',1)->get('gh_lifecycle_facility_catalog')->result_array(); if (! $items) $this->fail('Master fasilitas belum tersedia.');
        foreach ($items as $item) {
            $v=$p['items'][$item['id']]??array(); $cond=$v['condition_status']??'uninspected';
            if (! in_array($cond,array('uninspected','good','needs_repair','damaged','missing','not_available'),TRUE)) $this->fail('Kondisi fasilitas tidak valid.');
            $qty=$v['actual_qty']??''; if ($qty!=='' && (!ctype_digit((string)$qty) || (int)$qty>10000)) $this->fail('Jumlah aktual tidak valid.');
            $this->db->insert('gh_facility_inspection_rows',array('inspection_id'=>$iid,'item_id'=>$item['id'],'item_name'=>$item['item_name'],'standard_qty'=>$item['standard_qty'],'actual_qty'=>$qty===''?NULL:(int)$qty,'condition_status'=>$cond,'notes'=>substr($v['notes']??'',0,2000)));
        }
        $this->upload($c['id'],'scan_file','scan',$iid,!isset($i)); $this->upload($c['id'],'photo_file','photo',$iid);
    }
    private function finalize_inspection($c,$p)
    {
        $i=$this->inspection($c['id'],$p['inspection_id']??0); if ($i['status']!=='draft') $this->fail('Pemeriksaan final tidak dapat diubah. Buat revisi baru.');
        $requiredCount=$this->db->where('is_active',1)->count_all_results('gh_lifecycle_facility_catalog');
        $actualCount=$this->db->where('inspection_id',$i['id'])->count_all_results('gh_facility_inspection_rows');
        if ($requiredCount<1 || $actualCount!==$requiredCount) $this->fail('Daftar fasilitas belum lengkap. Koreksi draf sesuai katalog formulir terbaru.');
        if (empty($p['resident_signed']) || empty($p['manager_signed']) || ($i['stage']==='routine' && empty($p['supervisor_signed']))) $this->fail('Konfirmasi tanda tangan fisik wajib lengkap.');
        if (! $this->db->where('inspection_id',$i['id'])->where('kind','scan')->count_all_results('gh_resident_files')) $this->fail('Scan bertanda tangan wajib tersedia.');
        if ($this->db->where('inspection_id',$i['id'])->group_start()->where('condition_status','uninspected')->or_where('actual_qty',NULL)->group_end()->count_all_results('gh_facility_inspection_rows')) $this->fail('Masih ada fasilitas belum diperiksa atau jumlah aktual kosong.');
        foreach ($this->db->where('inspection_id',$i['id'])->get('gh_facility_inspection_rows')->result_array() as $row) {
            $qty=(int)$row['actual_qty']; $standard=(int)$row['standard_qty']; $condition=$row['condition_status'];
            if (($condition==='good' && $qty<$standard) || ($condition==='missing' && $qty>=$standard) || ($condition==='not_available' && $qty!==0) || (in_array($condition,array('damaged','needs_repair'),TRUE) && $qty<1)) $this->fail('Jumlah aktual dan kondisi '.$row['item_name'].' tidak konsisten. Koreksi berdasarkan scan.');
        }
        $this->db->where('id',$i['id'])->update('gh_facility_inspections',array('status'=>'final','resident_signed'=>1,'manager_signed'=>1,'supervisor_signed'=>!empty($p['supervisor_signed'])?1:0,'finalized_at'=>date('Y-m-d H:i:s'),'finalized_by'=>(int)$this->session->userdata('gh_admin_id')));
    }
    private function extract_inspection($c,$p)
    {
        $i=$this->inspection($c['id'],$p['inspection_id']??0); if ($i['status']!=='draft') $this->fail('OCR hanya tersedia untuk draft.');
        $f=$this->db->where('inspection_id',$i['id'])->where('kind','scan')->order_by('id','DESC')->get('gh_resident_files')->row_array(); if (! $f) $this->fail('Unggah scan terlebih dahulu.');
        $root=dirname(dirname(dirname(__DIR__)));
        $python=getenv('GULHOUSE_OCR_PYTHON') ?: (PHP_OS_FAMILY === 'Windows'
            ? 'C:/Users/TKM/.cache/codex-runtimes/codex-primary-runtime/dependencies/python/python.exe'
            : $root.'/.tools/ocr-venv/bin/python');
        $script=$root.'/scripts/lifecycle-ocr.py'; if (! is_file($python) || ! is_file($script)) $this->fail('OCR lokal belum tersedia; periksa scan dan isi hasil manual.');
        foreach (array('proc_open','proc_get_status','proc_terminate','proc_close') as $function) {
            if (! function_exists($function)) $this->fail('Runtime OCR belum diaktifkan; periksa scan dan isi hasil manual.');
        }
        $proc=proc_open(array($python,$script,'--input',$this->storage().'/'.$f['storage_name']),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes,$root);
        if (! is_resource($proc)) $this->fail('OCR gagal dimulai; verifikasi manual tetap tersedia.');
        fclose($pipes[0]); stream_set_blocking($pipes[1],FALSE); stream_set_blocking($pipes[2],FALSE);
        $out=''; $start=microtime(TRUE); $exit=NULL; @set_time_limit(90);
        while (TRUE) {
            $out.=stream_get_contents($pipes[1]); stream_get_contents($pipes[2]);
            $state=proc_get_status($proc);
            if (strlen($out)>8*1024*1024 || microtime(TRUE)-$start>65) { proc_terminate($proc); fclose($pipes[1]); fclose($pipes[2]); proc_close($proc); $this->fail('OCR melampaui batas waktu/ukuran. Gunakan verifikasi manual atau scan lebih ringkas.'); }
            if (!$state['running']) { $exit=$state['exitcode']; $out.=stream_get_contents($pipes[1]); break; }
            usleep(50000);
        }
        fclose($pipes[1]); fclose($pipes[2]); $close=proc_close($proc); if ($exit===NULL || $exit<0) $exit=$close; $result=json_decode($out,TRUE);
        if ($exit!==0 || ! is_array($result) || empty($result['ok'])) $this->fail('OCR lokal gagal membaca scan. Gunakan verifikasi manual.');
        // OCR suggestions never become authoritative facility conditions automatically.
        $this->db->where('id',$i['id'])->update('gh_facility_inspections',array('ocr_status'=>'review_required','ocr_json'=>json_encode($result)));
    }
    private function notice($c,$p)
    {
        $s=$this->db->where('id',$c['stay_id'])->get('gh_tenant_stays')->row_array(); if (! $s || $s['stay_status']!=='active') $this->fail('Pemberitahuan hanya untuk penghuni aktif.');
        $exit=$p['planned_exit']??''; if (! $this->date_valid($exit) || $exit<date('Y-m-d')) $this->fail('Tanggal keluar tidak valid.');
        if ($this->db->where('case_id',$c['id'])->count_all_results('gh_checkout_requests')) $this->fail('Pemberitahuan sudah tersimpan. Hubungi pengelola untuk koreksi.');
        $this->db->insert('gh_checkout_requests',array('case_id'=>$c['id'],'notice_date'=>date('Y-m-d'),'planned_exit'=>$exit,'late_notice'=>(strtotime($exit)-strtotime(date('Y-m-d')))/86400<15?1:0,'reason'=>substr($p['reason']??'',0,2000),'refund_bank'=>substr($p['refund_bank']??'',0,100),'refund_account'=>substr($p['refund_account']??'',0,100)));
        $this->db->where('id',$c['id'])->update('gh_resident_cases',array('status'=>'notice'));
    }
    private function checkout_locked($id) { $q=$this->db->query('SELECT * FROM gh_checkout_requests WHERE case_id=? FOR UPDATE',array($id))->row_array(); if (! $q) $this->fail('Pemberitahuan keluar belum tersedia.'); return $q; }
    private function notice_update($c,$p)
    {
        $q=$this->checkout_locked($c['id']); $exit=trim((string)($p['planned_exit']??'')); $revision=trim((string)($p['revision_reason']??''));
        $stay=$this->db->where('id',$c['stay_id'])->get('gh_tenant_stays')->row_array();
        if ($q['actual_date'] || !$stay || $stay['stay_status']!=='active' || $c['status']==='checked_out') $this->fail('Rencana keluar tidak dapat diubah setelah checkout.');
        if (!$this->date_valid($exit) || $exit<date('Y-m-d') || $exit<$stay['check_in_date'] || $revision==='') $this->fail('Tanggal rencana baru dan alasan revisi wajib valid.');
        $this->db->where('id',$q['id'])->update('gh_checkout_requests',array('planned_exit'=>$exit,'late_notice'=>(strtotime($exit)-strtotime($q['notice_date']))/86400<15?1:0,'reason'=>substr(trim((string)($p['reason']??$q['reason'])),0,2000),'refund_bank'=>substr(trim((string)($p['refund_bank']??$q['refund_bank'])),0,100),'refund_account'=>substr(trim((string)($p['refund_account']??$q['refund_account'])),0,100)));
        // Keep the original notice_date; revising a plan must not reset the compliance clock.
        return 'Rencana keluar diperbarui dari '.$q['planned_exit'].' menjadi '.$exit.'. Alasan revisi: '.substr($revision,0,2000);
    }
    private function ledger($c,$type,$amount,$date,$description,$ref=NULL) { $this->db->insert('gh_deposit_transactions',array('tenant_id'=>$c['tenant_id'],'stay_id'=>$c['stay_id'],'room_id'=>$c['room_id'],'transaction_date'=>$date,'transaction_type'=>$type,'amount'=>$amount,'method'=>'Transfer','reference_no'=>$ref,'description'=>$description)); return (int)$this->db->insert_id(); }
    private function checkout_case($c,$p,$forced=FALSE,$actor='')
    {
        $q=$this->checkout_locked($c['id']); $date=$p['actual_date']??'';
        if ($q['actual_date'] || ! $this->date_valid($date) || $date>date('Y-m-d') || empty($p['keys_returned'])) $this->fail('Tanggal aktual, penyerahan kunci atau status checkout tidak valid.');
        if (! $this->db->where(array('case_id'=>$c['id'],'stage'=>'check_out','status'=>'final','check_date'=>$date))->count_all_results('gh_facility_inspections')) $this->fail('Finalisasi pemeriksaan checkout pada tanggal keluar terlebih dahulu.');
        $s=$this->db->query('SELECT * FROM gh_tenant_stays WHERE id=? FOR UPDATE',array($c['stay_id']))->row_array(); if (! $s || $s['stay_status']!=='active' || $date<$s['check_in_date']) $this->fail('Masa tinggal tidak aktif atau tanggal mendahului check-in.');
        $review=$forced?$this->policy()->validate_forced_exit($c,$q,$p,$actor,$date):NULL;
        $debt=$this->db->query('SELECT COALESCE(SUM(GREATEST(CAST(b.base_price AS SIGNED)-CAST(b.paid_total AS SIGNED),0)),0) debt FROM gh_room_bills b JOIN gh_billing_periods p ON p.id=b.period_id WHERE b.stay_id=? AND p.period_key<=?',array($c['stay_id'],substr($date,0,7)))->row_array(); if (!$forced && (int)$debt['debt']>0) $this->fail('Seluruh tagihan wajib dilunasi sebelum checkout normal.');
        $fees=$this->policy()->summary($c,$date);if(!$forced && $fees['penalty_outstanding']>0)$this->fail('Lunasi denda pada ledger denda sebelum checkout normal.');
        $future=$this->db->query('SELECT b.* FROM gh_room_bills b JOIN gh_billing_periods p ON p.id=b.period_id WHERE b.stay_id=? AND p.period_key>?',array($c['stay_id'],substr($date,0,7)))->result_array();
        foreach ($future as $b) { if ($b['paid_total']>0 || $this->db->where('room_bill_id',$b['id'])->count_all_results('gh_payments')) $this->fail('Selesaikan pembayaran tagihan mendatang sebelum checkout.'); $this->db->where('id',$b['id'])->update('gh_room_bills',array('base_price'=>0,'bill_status'=>'empty','past_due_amount'=>0,'future_due_amount'=>0,'notes'=>'Dibatalkan checkout '.$date)); }
        $balance=$this->balance($c); $deduct=$this->money($p,'deduction_amount'); $dispute=$this->money($p,'disputed_amount');
        if ($balance<0 || $deduct+$dispute>$balance || ($deduct>0 && empty($p['deduction_reason']))) $this->fail('Potongan/sengketa melebihi saldo atau alasan kosong.');
        if ($deduct) $this->ledger($c,'deduction',$deduct,$date,'Potongan disepakati: '.$p['deduction_reason']); $refundable=$balance-$deduct-$dispute;
        $this->db->where('id',$q['id'])->update('gh_checkout_requests',array('actual_date'=>$date,'late_notice'=>(strtotime($date)-strtotime($q['notice_date']))/86400<15?1:0,'refund_due'=>date('Y-m-d',strtotime($date.' +2 days')),'deposit_snapshot'=>$balance,'deduction_amount'=>$deduct,'deduction_reason'=>substr($p['deduction_reason']??'',0,2000),'disputed_amount'=>$dispute,'refundable_amount'=>$refundable,'refund_bank'=>substr($p['refund_bank']??$q['refund_bank'],0,100),'refund_account'=>substr($p['refund_account']??$q['refund_account'],0,100),'status'=>$refundable>0?'pending_refund':($dispute>0?'disputed':'paid')));
        $this->db->where('id',$c['stay_id'])->update('gh_tenant_stays',array('stay_status'=>'ended','check_out_date'=>$date)); $this->db->where('stay_id',$c['stay_id'])->where_in('status',array('active','expiring'))->update('gh_contracts',array('status'=>'ended','end_date'=>$date));
        $this->db->where('id',$c['id'])->update('gh_resident_cases',array('status'=>'checked_out'));
        if($review)$this->db->where('id',$review['id'])->update('gh_lifecycle_overdue_reviews',array('actual_exit_date'=>$date));
        $other=$this->db->where('room_id',$c['room_id'])->where('id !=',$c['stay_id'])->where_in('stay_status',array('active','reserved','internal'))->count_all_results('gh_tenant_stays'); if (! $other) $this->db->where('id',$c['room_id'])->update('gh_rooms',array('status'=>'maintenance','is_public'=>0));
    }
    private function refund($c,$p)
    {
        $q=$this->checkout_locked($c['id']); $amount=$this->money($p,'amount'); $date=$p['transfer_date']??''; $ref=trim($p['reference_no']??'');
        if (! $q['actual_date'] || ! $this->date_valid($date) || $date<$q['actual_date'] || $date>date('Y-m-d') || $amount<1 || $amount>(int)$q['refundable_amount']-(int)$q['paid_amount'] || $amount>$this->balance($c) || ! $ref || strlen($ref)>120 || ! $q['refund_bank'] || ! $q['refund_account']) $this->fail('Nominal, rekening, referensi atau tanggal transfer tidak valid.');
        if ($this->db->where('reference_no',$ref)->count_all_results('gh_refund_payments')) $this->fail('Referensi transfer sudah dicatat.');
        $proof=$this->upload($c['id'],'proof_file','refund_proof',NULL,TRUE); $ledger=$this->ledger($c,'refund',$amount,$date,'Refund aktual H+2',$ref);
        $this->db->insert('gh_refund_payments',array('case_id'=>$c['id'],'amount'=>$amount,'transfer_date'=>$date,'reference_no'=>$ref,'proof_file_id'=>$proof,'ledger_id'=>$ledger));
        $paid=(int)$q['paid_amount']+$amount; $this->db->where('id',$q['id'])->update('gh_checkout_requests',array('paid_amount'=>$paid,'status'=>$paid>=(int)$q['refundable_amount']?($q['disputed_amount']>0?'disputed':'paid'):'pending_refund'));
    }
    private function resolve_dispute($c,$p)
    {
        $q=$this->checkout_locked($c['id']); $release=$this->money($p,'released_amount');
        if (empty($q['actual_date']) || $q['disputed_amount']<1 || $release>(int)$q['disputed_amount'] || empty($p['resolution_note'])) $this->fail('Penyelesaian sengketa tidak valid.');
        $deduct=(int)$q['disputed_amount']-$release; if ($deduct) $this->ledger($c,'deduction',$deduct,date('Y-m-d'),'Penyelesaian sengketa: '.$p['resolution_note']);
        $this->db->where('id',$q['id'])->update('gh_checkout_requests',array('disputed_amount'=>0,'deduction_amount'=>(int)$q['deduction_amount']+$deduct,'refundable_amount'=>(int)$q['refundable_amount']+$release,'resolution_note'=>substr($p['resolution_note'],0,2000),'status'=>(int)$q['paid_amount']>=(int)$q['refundable_amount']+$release?'paid':'pending_refund'));
    }
    private function maintenance($c,$p)
    {
        $i=$this->inspection($c['id'],$p['inspection_id']??0); $row=$this->db->where('inspection_id',$i['id'])->where('item_id',(int)($p['item_id']??0))->get('gh_facility_inspection_rows')->row_array();
        if ($i['status']!=='final' || ! $row || $row['maintenance_ticket_id'] || ! in_array($row['condition_status'],array('damaged','needs_repair','missing'),TRUE) || empty($p['issue_title'])) $this->fail('Pilih temuan kerusakan terverifikasi yang belum mempunyai tiket.');
        $this->db->insert('gh_maintenance_tickets',array('ticket_no'=>'GH-LC-'.bin2hex(random_bytes(6)),'room_id'=>$c['room_id'],'tenant_id'=>$c['tenant_id'],'issue_title'=>substr($p['issue_title'],0,180),'issue_detail'=>$row['item_name'].': '.$row['notes'],'reported_at'=>date('Y-m-d')));
        $ticket=(int)$this->db->insert_id(); $this->db->where('id',$row['id'])->update('gh_facility_inspection_rows',array('maintenance_ticket_id'=>$ticket));
    }
    public function activation_error($stayId)
    {
        if (! $this->ready()) return ''; $c=$this->db->where('stay_id',(int)$stayId)->get('gh_resident_cases')->row_array(); if (! $c) return '';
        if (! $c['ktp_file_id'] || ! $c['profile_completed_at'] || ! $c['payment_verified_at'] || ! $this->paid($c)) return 'Lengkapi KTP, biodata lengkap dan verifikasi pelunasan sewa pada Siklus Penghuni.';
        $ktp=$this->file($c['ktp_file_id']); if (!$ktp || !is_file($this->storage().'/'.$ktp['storage_name'])) return 'Lampiran KTP privat tidak tersedia.';
        $draft=$this->db->where('stay_id',$stayId)->where('status','draft')->order_by('id','ASC')->get('gh_contracts')->row_array();
        if ($draft && $this->balance($c)<(int)$draft['deposit_amount']) return 'Deposit harus lunas sebelum check-in penghuni.';
        if (! $this->db->where(array('case_id'=>$c['id'],'stage'=>'check_in','status'=>'final'))->count_all_results('gh_facility_inspections')) return 'Finalisasi tanda terima fasilitas bertanda tangan pada Siklus Penghuni.';
        return '';
    }
}
