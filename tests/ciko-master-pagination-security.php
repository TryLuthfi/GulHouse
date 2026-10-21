<?php
define('BASEPATH',dirname(__DIR__).'/master/system/');require dirname(__DIR__).'/master/application/hooks/Security_hook.php';
function html_escape($value){return htmlspecialchars($value,ENT_QUOTES,'UTF-8');}
function &get_instance(){global $fixture;return $fixture;}
class CikoOutput {public $body;public $headers=array();function __construct($body){$this->body=$body;}function get_output(){return $this->body;}function set_output($body){$this->body=$body;return $this;}function set_header($header){$this->headers[]=$header;return $this;}}
class CikoRouter {public $class='manage';public $method='master_form';function fetch_class(){return $this->class;}function fetch_method(){return $this->method;}}
$checks=array();$ck=function($name,$ok)use(&$checks){$checks[]=array('name'=>$name,'status'=>$ok?'PASS':'FAIL');if(!$ok)throw new RuntimeException('FAIL '.$name);};
$fixture=(object)array('router'=>new CikoRouter(),'config'=>new class {function item($key){return $key==='csrf_protection';}},'security'=>new class {function get_csrf_token_name(){return 'fixture_csrf';}function get_csrf_hash(){return 'owned-fixture-token';}},'session'=>new class {function userdata($key){return 'super_admin';}});
$hook=new Security_hook();
$fixture->output=new CikoOutput('<div class="modal"><form method="post" action="/master/rooms/save"><input name="id" value="1"></form></div>');$hook->forms();
$ck('Lazy fragment POST gets CSRF token',strpos($fixture->output->body,'name="fixture_csrf" value="owned-fixture-token"')!==false);
$ck('Fragment remains fragment without global script',stripos($fixture->output->body,'<html')===false&&strpos($fixture->output->body,'GH_CSRF')===false);
$ck('Fragment sets private no-store header',in_array('Cache-Control: private, no-store',$fixture->output->headers,true));
$ck('Fragment does not remove form identity/action',strpos($fixture->output->body,'name="id" value="1"')!==false&&strpos($fixture->output->body,'action="/master/rooms/save"')!==false);
$hook->forms();$ck('Existing CSRF token not duplicated',substr_count($fixture->output->body,'name="fixture_csrf"')===1);
$fixture->output=new CikoOutput('<div class="modal"><form method="get" action="/master/rooms"><input name="q"></form><form method="POST" action="/master/rooms/save"></form></div>');$hook->forms();$ck('Only POST form gets token',substr_count($fixture->output->body,'name="fixture_csrf"')===1);
$fixture->router->method='rooms';$untouched='<div><form method="post"></form></div>';$fixture->output=new CikoOutput($untouched);$hook->forms();$ck('Other non-HTML fragments remain unchanged',$fixture->output->body===$untouched);
$fixture->output=new CikoOutput('<html><body><form method="post"></form></body></html>');$hook->forms();$ck('Full page CSRF injection remains compatible',substr_count($fixture->output->body,'name="fixture_csrf"')===1&&strpos($fixture->output->body,'window.GH_CSRF=')!==false);
$result=array('passed'=>count($checks),'failed'=>0,'checks'=>$checks,'database_writes'=>0);file_put_contents(dirname(__DIR__).'/.e2e/master-pagination-20261021/ciko-security-results.json',json_encode($result,JSON_PRETTY_PRINT));echo json_encode(array('passed'=>count($checks),'failed'=>0));
