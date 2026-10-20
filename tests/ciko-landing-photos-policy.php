<?php
define('BASEPATH',__DIR__);require dirname(__DIR__).'/master/application/hooks/Security_hook.php';
function &get_instance(){global $ci;return$ci;}
function show_error($message,$code,$title=''){throw new RuntimeException($title,$code);}
function redirect($url,$method='',$code=0){throw new RuntimeException($url,$code);}
class PolicyRouter {function fetch_class(){return'manage';}function fetch_method(){return'save_landing_photos';}}
class PolicySession {public $role;function __construct($r){$this->role=$r;}function userdata($k){return$k==='gh_admin_logged_in'?true:1;}function set_userdata($k,$v){}}
class PolicyDB {public $role;function __construct($r){$this->role=$r;}function where($k,$v){return$this;}function get($k){return$this;}function row_array(){return array('role'=>$this->role);}}
class PolicyInput {public $method;function __construct($m){$this->method=$m;}function method($x){return$this->method;}}
$checks=array();foreach(array(array('owner','POST',0),array('super_admin','POST',0),array('admin','POST',0),array('manager','POST',0),array('viewer','POST',403),array('finance','POST',403),array('maintenance','POST',403),array('admin','GET',405))as$case){
 $ci=(object)array('router'=>new PolicyRouter(),'session'=>new PolicySession($case[0]),'db'=>new PolicyDB($case[0]),'input'=>new PolicyInput($case[1]));$code=0;
 try{(new Security_hook())->enforce();}catch(RuntimeException$e){$code=$e->getCode();}
 $checks[]=array('name'=>$case[0].' '.$case[1],'status'=>$code===$case[2]?'PASS':'FAIL','code'=>$code);
}
$result=array('checks'=>$checks,'failed'=>count(array_filter($checks,function($x){return$x['status']==='FAIL';})));file_put_contents(dirname(__DIR__).'/.e2e/landing-photos-20261021/ciko/policy-results.json',json_encode($result,JSON_PRETTY_PRINT));echo json_encode($result,JSON_PRETTY_PRINT);exit($result['failed']?1:0);
