<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Lifecycle extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (! $this->session->userdata('gh_admin_logged_in')) { redirect('login','location',303); return; }
        $this->load->model('Lifecycle_model');
        if (! $this->Lifecycle_model->ready()) show_error('Modul siklus penghuni belum dimigrasikan. Jalankan migrasi additive melalui CLI.',503);
    }
    private function stays() { return $this->db->select('s.*,t.fullname,r.room_label,r.room_code')->from('gh_tenant_stays s')->join('gh_tenants t','t.id=s.tenant_id')->join('gh_rooms r','r.id=s.room_id')->where('s.stay_status','reserved')->get()->result_array(); }
    private function enroll_stays() { return $this->db->select('s.*,t.fullname,r.room_label,r.room_code,p.code AS property_code')->from('gh_tenant_stays s')->join('gh_tenants t','t.id=s.tenant_id')->join('gh_rooms r','r.id=s.room_id')->join('gh_properties p','p.id=r.property_id')->where_in('s.stay_status',array('active','reserved'))->where('NOT EXISTS (SELECT 1 FROM gh_resident_cases c WHERE c.stay_id=s.id)',NULL,FALSE)->get()->result_array(); }
    public function index()
    {
        $rows=$this->Lifecycle_model->cases();
        $metrics=array('total'=>count($rows),'active'=>0,'refund_due'=>0,'monthly_expected'=>0,'monthly_completed'=>0);
        $coverage=array();
        foreach ($rows as $row) if (in_array($row['status'],array('active','notice'),TRUE)) {
            $metrics['active']++; $metrics['monthly_expected']++;
            $done=$this->db->where(array('case_id'=>$row['id'],'stage'=>'routine','period'=>date('Y-m'),'status'=>'final'))->count_all_results('gh_facility_inspections')>0;
            if ($done) $metrics['monthly_completed']++;
            $coverage[]=array('case_id'=>$row['id'],'room_label'=>$row['room_label'],'full_name'=>$row['full_name'],'period'=>date('Y-m'),'status'=>$done?'completed':'pending');
        }
        $unmanaged=$this->db->select('s.id,r.room_label,t.fullname')->from('gh_tenant_stays s')->join('gh_rooms r','r.id=s.room_id')->join('gh_tenants t','t.id=s.tenant_id')->where('s.stay_status','active')->where('NOT EXISTS (SELECT 1 FROM gh_resident_cases c WHERE c.stay_id=s.id)',NULL,FALSE)->get()->result_array();
        foreach ($unmanaged as $stay) { $metrics['active']++; $metrics['monthly_expected']++; $coverage[]=array('case_id'=>NULL,'stay_id'=>$stay['id'],'room_label'=>$stay['room_label'],'full_name'=>$stay['fullname'],'period'=>date('Y-m'),'status'=>'not_enrolled'); }
        $metrics['monthly_pending']=$metrics['monthly_expected']-$metrics['monthly_completed'];
        $metrics['refund_due']=$this->db->where('actual_date IS NOT NULL',NULL,FALSE)->where('refund_due <=',date('Y-m-d'))->where('paid_amount < refundable_amount',NULL,FALSE)->count_all_results('gh_checkout_requests');
        $q=trim((string)$this->input->get('q',TRUE)); $status=trim((string)$this->input->get('status',TRUE));
        $rows=array_values(array_filter($rows,function($c) use($q,$status){return ($status===''||$c['status']===$status) && ($q===''||stripos($c['full_name'].' '.$c['room_label'].' '.$c['room_code'].' '.$c['phone'],$q)!==FALSE);}));
        $this->load->view('lifecycle/index',array('title'=>'Siklus Penghuni | GUL HOUSE','admin_name'=>$this->session->userdata('gh_admin_name'),'cases'=>$rows,'stays'=>$this->stays(),'enroll_stays'=>$this->enroll_stays(),'metrics'=>$metrics,'coverage'=>$coverage,'filters'=>array('q'=>$q,'status'=>$status)));
    }
    public function enroll()
    {
        if ($this->input->method(TRUE)!=='POST') { show_error('Gunakan POST.',405); return; }
        try { $v=$this->Lifecycle_model->enroll_existing($this->input->post(NULL,TRUE)?:array(),'admin:'.(int)$this->session->userdata('gh_admin_id')); $this->session->set_flashdata('resident_access_code',$v['code']); $this->session->set_flashdata('success','Penghuni existing ditautkan tanpa mengubah masa tinggal. Simpan kode akses pribadi untuk disampaikan aman kepada penghuni.'); redirect('lifecycle/detail/'.$v['id'],'location',303); return; }
        catch (Throwable $e) { $this->session->set_flashdata('error',$e instanceof RuntimeException?$e->getMessage():'Penghuni belum ditautkan. Periksa duplikasi dan kelengkapan data.'); }
        redirect('lifecycle','location',303);
    }
    public function detail($id)
    {
        $data=$this->Lifecycle_model->detail((int)$id); if (! $data) { show_404(); return; }
        $data=array_merge($data,$this->Lifecycle_model->administration($data['case']));
        $role=$this->session->userdata('gh_admin_role');
        unset($data['case']['access_hash']);
        if (in_array($role,array('maintenance','finance'),TRUE)) {
            $data['case']['profile']=array(); $data['case']['profile_json']=NULL; $data['case']['ktp_file_id']=NULL; $data['case']['marriage_file_id']=NULL;
            $data['files']=array_values(array_filter($data['files'],function($f)use($role){return !in_array($f['kind'],$role==='maintenance'?array('ktp','marriage','refund_proof','rent_proof','penalty_proof'):array('ktp','marriage'),TRUE);}));
        }
        if ($role==='maintenance') { $data['case']['phone']=''; if ($data['checkout']) { $data['checkout']['refund_bank']=''; $data['checkout']['refund_account']=''; } $data['refunds']=array(); $data['payment_summary']=NULL;$data['billing_policy']=array('enabled'=>FALSE); }
        if($role==='maintenance'){unset($data['reservation_quote'],$data['room_choices']);$data['link_stays']=array();$data['room_change']=array('allowed'=>FALSE,'blockers'=>array('Peran ini tidak mengubah reservasi.'));}
        $data['title']='Siklus Penghuni | GUL HOUSE'; $data['admin_name']=$this->session->userdata('gh_admin_name'); $data['stays']=$data['link_stays'];
        $this->load->view('lifecycle/detail',$data);
    }
    public function action($id)
    {
        if ($this->input->method(TRUE)!=='POST') { show_error('Gunakan POST.',405); return; }
        $p=$this->input->post(NULL,TRUE); $action=$p['action']??'';
        try { $this->Lifecycle_model->action((int)$id,$action,is_array($p)?$p:array(),'admin:'.(int)$this->session->userdata('gh_admin_id')); if($action==='penalty_payment'){$keys=$this->session->userdata('gh_penalty_request_keys')?:array();unset($keys[(int)$id]);$this->session->set_userdata('gh_penalty_request_keys',$keys);} $this->session->set_flashdata('success','Proses berhasil disimpan.'); }
        catch (Throwable $e) { $this->session->set_flashdata('error',$e instanceof RuntimeException?$e->getMessage():'Proses belum tersimpan. Periksa data dan coba kembali.'); }
        redirect('lifecycle/detail/'.(int)$id,'location',303);
    }
    public function file($id)
    {
        $f=$this->Lifecycle_model->file((int)$id); if (! $f) { show_404(); return; }
        $role=$this->session->userdata('gh_admin_role');
        if (($role==='maintenance' && in_array($f['kind'],array('ktp','marriage','refund_proof','rent_proof','penalty_proof'),TRUE)) || ($role==='finance' && in_array($f['kind'],array('ktp','marriage'),TRUE))) { show_error('Peran ini tidak memiliki akses dokumen identitas.',403); return; }
        $path=$this->Lifecycle_model->storage().'/'.$f['storage_name']; if (! is_file($path)) { show_404(); return; }
        $this->output->set_content_type($f['mime'])->set_header('Content-Disposition: inline; filename="document-'.(int)$f['id'].'.'.pathinfo($f['storage_name'],PATHINFO_EXTENSION).'"')->set_header('Cache-Control: no-store')->set_header('X-Content-Type-Options: nosniff')->set_output(file_get_contents($path));
    }
    public function print_form($id)
    {
        $data=$this->Lifecycle_model->detail((int)$id); if (! $data) { show_404(); return; }
        unset($data['case']['profile_json'],$data['case']['profile'],$data['case']['access_hash']);
        $stage=$this->input->get('stage',TRUE); $data['stage']=in_array($stage,array('check_in','routine','check_out'),TRUE)?$stage:'routine'; $data['title']='Form Pemeriksaan Fasilitas | GUL HOUSE';
        $this->load->view('lifecycle/print',$data);
    }
}
