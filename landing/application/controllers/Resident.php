<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Resident extends CI_Controller
{
    private $lifecycle;
    public function __construct()
    {
        parent::__construct(); $this->load->model(array('Lifecycle_model','Resident_access_model')); $this->lifecycle=$this->Lifecycle_model;
        if (! $this->lifecycle->ready()) show_error('Layanan pendaftaran belum tersedia. Hubungi pengelola.',503);
        $this->output->set_header('Cache-Control: no-store')->set_header('Referrer-Policy: no-referrer');
    }
    private function require_case()
    {
        $c=$this->Resident_access_model->current();
        if (!$c) { redirect('resident/access','location',303);return NULL; }
        return $c;
    }
    public function start()
    {
        $error='';
        if ($this->input->method(TRUE)==='POST') {
            try { $payload=$this->input->post(NULL,TRUE)?:array();$payload['source']='website';$v=$this->lifecycle->intake($payload); $this->Resident_access_model->establish($this->lifecycle->get_case($v['id'])); $this->session->set_flashdata('resident_access_code',$v['code']); redirect('resident/portal','location',303); return; }
            catch (Throwable $e) { $error=$e instanceof RuntimeException?$e->getMessage():'Pendaftaran belum tersimpan. Periksa data dan coba kembali.'; }
        }
        $this->load->view('resident/start',array('title'=>'Pendaftaran Penghuni | GUL HOUSE','rooms'=>$this->lifecycle->rooms(),'error'=>$error));
    }
    public function access()
    {
        if ($this->input->method(TRUE)==='GET' && $this->Resident_access_model->current()) { redirect('resident/portal','location',303);return; }
        $error=''; if ($this->input->method(TRUE)==='POST') {
            // Per-IP bounded attempt counter, no identity lookup or enumerable personal credentials.
            $dir=rtrim($this->config->item('sess_save_path'),'/\\'); $path=$dir.'/gh_resident_attempt_'.hash('sha256',$this->db->database.$this->input->ip_address()).'.json'; $fp=@fopen($path,'c+');
            if (! $fp || ! flock($fp,LOCK_EX)) { if ($fp) fclose($fp); show_error('Akses sementara tidak tersedia.',503); return; }
            $v=json_decode(stream_get_contents($fp),TRUE); if (!is_array($v)||($v['until']??0)<=time()) $v=array('until'=>time()+300,'count'=>0); $v['count']++; rewind($fp); ftruncate($fp,0); fwrite($fp,json_encode($v)); fflush($fp); flock($fp,LOCK_UN); fclose($fp);
            if ($v['count']>10) { $this->output->set_header('Retry-After: 300'); show_error('Terlalu banyak percobaan. Tunggu lima menit.',429); return; }
            $code=$this->input->post('access_code',TRUE);$phone=$this->input->post('phone',TRUE);
            $c=$this->Resident_access_model->credentials($phone,is_string($code)?trim($code):'');
            if ($c) { $this->Resident_access_model->establish($c);redirect('resident/portal','location',303); return; }
            $error='Nomor HP atau kode akses tidak cocok.';
        }
        $this->load->view('resident/access',array('title'=>'Akses Penghuni | GUL HOUSE','error'=>$error));
    }
    public function portal()
    {
        $c=$this->require_case(); if (! $c) return; $data=$this->lifecycle->detail($c['id']);
        unset($data['case']['access_hash']);
        $data['resident_billing']=array();
        if(!empty($c['tenant_id'])&&!empty($c['stay_id'])) {
            $this->load->model('Landing_model');
            $context=$this->Landing_model->get_payment_context((int)$c['tenant_id'],(int)$c['stay_id']);
            if($context) {
                $current=$this->Landing_model->get_current_payment_bill($context);
                $data['resident_billing']=array('context'=>$context,'current_bill'=>$current,
                    'bill_history'=>$this->Landing_model->get_payment_bill_history($context,24),
                    'payments'=>!empty($current['id'])?$this->Landing_model->get_bill_payments((int)$current['id']):array(),
                    'portal'=>$this->Landing_model->get_resident_portal($context));
            }
        }
        // Resident sees finalized records only; drafts and private OCR analysis remain internal.
        $data['inspections']=array_values(array_filter($data['inspections'],function($i){return $i['status']==='final';}));
        foreach ($data['inspections'] as &$i) { unset($i['ocr_json'],$i['ocr']); } unset($i);
        $finalIds=array_column($data['inspections'],'id');
        $data['files']=array_values(array_filter($data['files'],function($f) use($finalIds){return !$f['inspection_id'] || in_array($f['inspection_id'],$finalIds); }));
        $data['title']='Portal Penghuni | GUL HOUSE'; $this->load->view('resident/portal',$data);
    }
    public function action()
    {
        if ($this->input->method(TRUE)!=='POST') { show_error('Gunakan POST.',405); return; }
        $c=$this->require_case(); if (! $c) return; $p=$this->input->post(NULL,TRUE)?:array();
        try { $this->lifecycle->action($c['id'],$p['action']??'',$p,'resident',TRUE); $this->session->set_flashdata('success','Data berhasil disimpan.'); }
        catch (Throwable $e) { $this->session->set_flashdata('error',$e instanceof RuntimeException?$e->getMessage():'Data belum tersimpan. Periksa kembali isian.'); }
        redirect('resident/portal','location',303);
    }
    public function file($id)
    {
        $c=$this->require_case(); if (! $c) return; $f=$this->lifecycle->file((int)$id);
        if (! $f || (int)$f['case_id']!==(int)$c['id']) { show_404(); return; }
        if ($f['inspection_id']) { $i=$this->db->where('id',$f['inspection_id'])->where('status','final')->get('gh_facility_inspections')->row_array(); if (! $i) { show_404(); return; } }
        $path=$this->lifecycle->storage().'/'.$f['storage_name']; if (! is_file($path)) { show_404(); return; }
        $this->output->set_content_type($f['mime'])->set_header('Content-Disposition: inline; filename="document-'.(int)$f['id'].'.'.pathinfo($f['storage_name'],PATHINFO_EXTENSION).'"')->set_header('X-Content-Type-Options: nosniff')->set_output(file_get_contents($path));
    }
    public function logout()
    {
        if ($this->input->method(TRUE)!=='POST') { show_error('Gunakan POST.',405); return; }
        $this->Resident_access_model->clear(); $this->session->sess_regenerate(TRUE); redirect('resident/access','location',303);
    }
}
