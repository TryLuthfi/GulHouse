<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Mobile extends CI_Controller
{
    public function __construct()
    {
        parent::__construct(); ini_set('display_errors','0'); $this->db->db_debug=FALSE; $this->load->model('Mobile_service');
        $this->output->set_header('Cache-Control: no-store')->set_header('X-Content-Type-Options: nosniff')->set_header('Referrer-Policy: no-referrer');
    }
    public function index(...$parts)
    {
        $origin=(string)$this->input->get_request_header('Origin'); $host=parse_url($origin,PHP_URL_HOST);
        $configured=array_filter(array_map('trim',explode(',',getenv('GULHOUSE_MOBILE_CORS_ORIGINS')?:'')));
        $cors=in_array($host,array('localhost','127.0.0.1','[::1]'),TRUE)||in_array($origin,$configured,TRUE);
        if ($origin!==''&&$cors) $this->output->set_header('Access-Control-Allow-Origin: '.$origin)->set_header('Vary: Origin')->set_header('Access-Control-Allow-Methods: GET, POST, OPTIONS')->set_header('Access-Control-Allow-Headers: Authorization, Content-Type')->set_header('Access-Control-Max-Age: 600');
        if ($this->input->method(TRUE)==='OPTIONS') { $this->output->set_status_header($cors?204:403)->set_output(''); return; }
        try {
            if (!$this->db->table_exists('gh_mobile_tokens') || !$this->Lifecycle_model->ready()) throw new Mobile_api_error('API atau prasyarat siklus penghuni belum dimigrasikan.',503,'NOT_READY');
            $body=$this->input->post(NULL,FALSE)?:array();
            if (stripos((string)$this->input->get_request_header('Content-Type'),'application/json')===0) { $body=json_decode($this->input->raw_input_stream,TRUE); if (!is_array($body)) throw new Mobile_api_error('JSON tidak valid.',400,'INVALID_JSON'); }
            if (isset($body['payload'])) { $nested=is_array($body['payload'])?$body['payload']:json_decode($body['payload'],TRUE); if (!is_array($nested)) throw new Mobile_api_error('Payload tidak valid.',400,'INVALID_JSON'); $body=array_replace_recursive($body,$nested); unset($body['payload']); }
            $_POST=$body; $method=$this->input->method(TRUE); $section=$parts[0]??'';
            if ($section==='auth' && in_array($parts[1]??'',array('admin','resident','payment'),TRUE)) { $this->post_only($method); $data=$this->Mobile_service->login($parts[1],$body); }
            elseif ($section==='catalog') { if($method!=='GET'&&($parts[1]??'')!=='booking') throw new Mobile_api_error('Gunakan GET.',405,'METHOD'); if(($parts[1]??'')==='booking')$this->post_only($method); $data=$this->Mobile_service->catalog($parts[1]??'rooms',$parts[2]??NULL,$body); }
            elseif ($section==='resident' && ($parts[1]??'')==='intake') { $this->post_only($method); $data=$this->Mobile_service->intake($body); }
            else {
                $principal=$this->Mobile_service->authenticate((string)$this->input->get_request_header('Authorization',TRUE));
                if ($section==='me') { if($method!=='GET')throw new Mobile_api_error('Gunakan GET.',405,'METHOD');$data=$this->Mobile_service->me($principal); }
                elseif ($section==='auth' && ($parts[1]??'')==='logout') { $this->post_only($method); $this->db->where('id',$principal['token_id'])->update('gh_mobile_tokens',array('revoked_at'=>date('Y-m-d H:i:s')));$data=array('revoked'=>TRUE); }
                elseif ($section==='admin') { $data=$this->Mobile_service->admin($principal,array_slice($parts,1),$method,$body,$this->input->get(NULL,TRUE)?:array()); }
                elseif ($section==='resident') { $data=$this->Mobile_service->resident($principal,$parts[1]??'portal',$method,$body); }
                elseif ($section==='payment'&&($parts[1]??'')==='status') { if($method!=='GET')throw new Mobile_api_error('Gunakan GET.',405,'METHOD');$data=$this->Mobile_service->payment_status($principal); }
                elseif ($section==='files') { if($method!=='GET')throw new Mobile_api_error('Gunakan GET.',405,'METHOD');$this->Mobile_service->stream_file($principal,$parts[1]??'', $parts[2]??'');return; }
                else throw new Mobile_api_error('Endpoint tidak ditemukan.',404,'NOT_FOUND');
            }
            $this->output->set_content_type('application/json')->set_output(json_encode(array('ok'=>TRUE,'data'=>$this->Mobile_service->sanitize($data)),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE));
        } catch(Throwable $e) {
            $known=$e instanceof Mobile_api_error || $e instanceof RuntimeException;
            $status=$e instanceof Mobile_api_error?$e->status:($known?422:500); $code=$e instanceof Mobile_api_error?$e->apiCode:($known?'VALIDATION':'INTERNAL');
            $this->output->set_status_header($status)->set_content_type('application/json')->set_output(json_encode(array('ok'=>FALSE,'error'=>array('code'=>$code,'message'=>$known?$e->getMessage():'Proses belum berhasil. Coba kembali.','fields'=>new stdClass())),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE));
        }
    }
    private function post_only($method) { if ($method!=='POST') throw new Mobile_api_error('Gunakan POST.',405,'METHOD'); }
}
