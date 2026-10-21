<?php
define('BASEPATH', __DIR__);
require dirname(__DIR__).'/master/application/hooks/Security_hook.php';
function show_error($message,$code,$title=''){throw new RuntimeException('HTTP'.$code);}
function redirect($path,$method,$code){throw new RuntimeException('HTTP'.$code);}
function &get_instance(){global $ci;return $ci;}
class CikoFocusRouter {function fetch_class(){return 'manage';}function fetch_method(){return 'save_photo_focus';}}
class CikoFocusSession {function userdata($key){return true;}function set_userdata($key,$value){}}
class CikoFocusInput {public $verb='POST';function method($upper){return $this->verb;}}
class CikoFocusDb {public $role='admin';function where($a,$b){return $this;}function get($t){return $this;}function row_array(){return array('role'=>$this->role);}}
$ci=(object)array('router'=>new CikoFocusRouter(),'session'=>new CikoFocusSession(),'input'=>new CikoFocusInput(),'db'=>new CikoFocusDb());
$count=0;foreach(array('admin'=>null,'owner'=>null,'super_admin'=>null,'manager'=>null,'viewer'=>'HTTP403','finance'=>'HTTP403','maintenance'=>'HTTP403') as $role=>$expected){$ci->db->role=$role;$actual=null;try{(new Security_hook())->enforce();}catch(RuntimeException $e){$actual=$e->getMessage();}if($actual!==$expected)throw new Exception('Policy '.$role.' mismatch');$count++;}
$ci->db->role='admin';$ci->input->verb='GET';try{(new Security_hook())->enforce();throw new Exception('GET incorrectly accepted');}catch(RuntimeException $e){if($e->getMessage()!=='HTTP405')throw $e;$count++;}
echo json_encode(array('pass'=>$count,'scope'=>'actual central hook using mocked auth/session and DB; no real business mutation')).PHP_EOL;
