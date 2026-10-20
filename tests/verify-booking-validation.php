<?php
// Focused model/controller validation against owned synthetic DB; DB errors fault-injected explicitly.
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';
function base_url($path=''){return 'http://127.0.0.1/'.ltrim($path,'/');}
$runtime=json_decode(file_get_contents($root.'/.e2e/mobile/bookingretain26/runtime.json'),TRUE);
if(!preg_match('/^gulhouse_test_mobile_bookingretain26_[0-9_]+$/D',$runtime['schema']??''))throw new RuntimeException('Owned isolated schema required.');
list($retention,$db)=booking_retention_cli($runtime['schema']);require $root.'/landing/application/models/Landing_model.php';
$model=new Landing_model();$model->db=$db;$checks=0;function valcheck($ok,$name){global $checks;if(!$ok)throw new RuntimeException('FAIL '.$name);$checks++;echo 'PASS '.$name.PHP_EOL;}
$rooms=array_values(array_filter($model->get_all_public_rooms(),function($r){return !empty($r['available_count']);}));if(!$rooms)throw new RuntimeException('Synthetic public room required.');
$suffix=bin2hex(random_bytes(5));$today=(new DateTimeImmutable('now',new DateTimeZone('Asia/Jakarta')))->format('Y-m-d');$valid=array('fullname'=>'Validation fixture '.$suffix,'phone'=>'0896'.random_int(10000000,99999999),'room_interest'=>$rooms[0]['name'],'move_in_plan'=>$today,'message'=>'Synthetic validation only');
$badCases=array(
    array('fullname','','fullname','required name'),array('fullname',str_repeat('A',151),'fullname','name length'),
    array('phone','','phone','required phone'),array('phone','08123','phone','short phone'),array('phone',str_repeat('1',16),'phone','long phone'),array('phone','abc'.$valid['phone'].'xyz','phone','letters in phone'),array('phone','08+123456789','phone','embedded plus'),
    array('phone','+628123456789','phone','plus prefix'),array('phone','0812 3456 789','phone','phone spaces'),array('phone','0812-3456-789','phone','phone punctuation'),
    array('room_interest','','room_interest','required room'),array('room_interest','Unknown synthetic type '.$suffix,'room_interest','unknown room'),array('move_in_plan',str_repeat('x',61),'move_in_plan','move-in length'),array('message',str_repeat('x',2001),'message','message length'),array('fullname',array('bad'),'fullname','array input')
);
foreach($badCases as $case){$p=$valid;$p[$case[0]]=$case[1];valcheck(!$model->save_booking_request($p)&&$model->get_booking_error_field()===$case[2]&&$model->get_booking_error()!==''&&strpos($model->get_booking_error(),$valid['phone'])===FALSE,'precise safe failure: '.$case[3]);}
$full=new class extends Landing_model {public function get_all_public_rooms(){return array(array('name'=>'Synthetic full','available_count'=>0));}};$full->db=$db;$p=$valid;$p['room_interest']='Synthetic full';valcheck(!$full->save_booking_request($p)&&$full->get_booking_error_field()==='room_interest'&&strpos($full->get_booking_error(),'penuh')!==FALSE,'unavailable room has specific message');
$p=$valid;valcheck($model->save_booking_request($p)&&$model->get_booking_error()===''&&$model->get_booking_error_field()==='','digits-only phone accepted and stale model errors cleared');
$saved=$db->where('fullname',$valid['fullname'])->get('gh_booking_requests')->row_array();valcheck($saved['phone']===$valid['phone']&&$saved['phone'][0]==='0','leading zero preserved as text');
foreach(array('2026-02-30','2100-02-29','2030-1-01',$today.'T10:00:00',(new DateTimeImmutable('yesterday',new DateTimeZone('Asia/Jakarta')))->format('Y-m-d')) as $badDate){$p=$valid;$p['move_in_plan']=$badDate;valcheck(!$model->save_booking_request($p)&&$model->get_booking_error_field()==='move_in_plan','invalid or past estimate date rejected');}
foreach(array(''=> 'optional empty',$today=>'WIB today',(new DateTimeImmutable('tomorrow',new DateTimeZone('Asia/Jakarta')))->format('Y-m-d')=>'future') as $date=>$label){$p=$valid;$p['fullname'].=' '.$label;$p['move_in_plan']=$date;valcheck($model->save_booking_request($p)&&$db->where('fullname',$p['fullname'])->get('gh_booking_requests')->row_array()['move_in_plan']===$date,'estimate date accepted: '.$label);}
$cityMethod=new ReflectionMethod($model,'public_property_city');$cityMethod->setAccessible(TRUE);
valcheck($cityMethod->invoke($model,'GH 1','Actual City','Kota Other')==='Actual City','actual property city takes priority');
valcheck($cityMethod->invoke($model,'GH 3',' Balikpapan ','Jl. Synthetic')==='Balikpapan','city comes from editable property master');
valcheck($cityMethod->invoke($model,'GH 2','','Kota Jakarta Barat')===''&&$cityMethod->invoke($model,'APT','','')==='','empty city does not infer location or use hardcoded fallback');
$cards=$model->get_all_public_rooms();$properties=array();foreach($db->get('gh_properties')->result_array() as $property)$properties[$property['code']]=$property;
$locations=TRUE;foreach($cards as $card)$locations=$locations&&array_key_exists('city',$card)&&array_key_exists('property_address',$card)&&$card['property_address']===trim((string)($properties[$card['code']]['address']??''));
valcheck($locations,'public room cards expose city and unchanged actual property address');
// Explicit fault injection; not a live database outage or db_debug exit test.
foreach(array(FALSE,TRUE) as $throws){
    $failing=new Landing_model();$failing->db=new class($db,$throws){private $db,$throws;function __construct($d,$t){$this->db=$d;$this->throws=$t;}function __call($n,$args){return call_user_func_array(array($this->db,$n),$args);}function insert($table,$row){if($this->throws)throw new RuntimeException('PRIVATE SQL '.$row['phone']);return FALSE;}};
    $p=$valid;$p['fullname'].=$throws?' throw':' false';valcheck(!$failing->save_booking_request($p)&&$failing->get_booking_error_field()==='form'&&strpos($failing->get_booking_error(),'PRIVATE')===FALSE&&strpos($failing->get_booking_error(),$p['phone'])===FALSE,'fault-injected database '.($throws?'exception':'false').' returns generic safe message');
}
class CI_Controller {public $input,$session,$Landing_model;}
function redirect($url){$GLOBALS['booking_redirect']=$url;}
require $root.'/landing/application/controllers/Home.php';
$home=(new ReflectionClass('Home'))->newInstanceWithoutConstructor();$home->Landing_model=$model;
$home->session=new class {public $data=array();function set_flashdata($k,$v){$this->data[$k]=$v;}function unset_userdata($keys){foreach((array)$keys as $k)unset($this->data[$k]);}};
$home->input=new class {public $data=array();function post($key,$clean=TRUE){return $this->data[$key]??NULL;}};
$home->input->data=array('fullname'=>str_repeat('n',200),'phone'=>str_repeat('9',100),'room_interest'=>str_repeat('r',180),'move_in_plan'=>str_repeat('m',80),'message'=>str_repeat('x',2500));$home->booking();
$flash=$home->session->data;$bounded=TRUE;foreach(array('fullname'=>150,'phone'=>64,'room_interest'=>150,'move_in_plan'=>60,'message'=>2000) as $key=>$length)$bounded=$bounded&&mb_strlen($flash['booking_old'][$key])===$length;
valcheck($bounded&&$flash['booking_error_field']==='fullname'&&$flash['booking_error']!==''&&isset($flash['warning']),'Home failure preserves bounded five-field flash plus precise field/message');
$home->input->data=$valid;$home->input->data['fullname'].=' Home';$home->booking();
valcheck(!isset($home->session->data['booking_error'],$home->session->data['booking_old'],$home->session->data['booking_error_field'])&&isset($home->session->data['success']),'Home success clears retained errors and old values');
echo json_encode(array('passed'=>$checks,'environment'=>'isolated_synthetic','database_error_tests'=>'fault_injection_not_live_outage','real_booking_writes'=>0)).PHP_EOL;
