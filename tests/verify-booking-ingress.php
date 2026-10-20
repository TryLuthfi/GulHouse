<?php
// Real landing -> master model regression in the dedicated synthetic schema only.
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';
function base_url($path=''){return 'http://127.0.0.1/'.ltrim($path,'/');}
$runtime=json_decode(file_get_contents($root.'/.e2e/mobile/bookingretain26/runtime.json'),TRUE);
if(!preg_match('/^gulhouse_test_mobile_bookingretain26_[0-9_]+$/D',$runtime['schema']??''))throw new RuntimeException('Owned isolated runtime required.');
list($retention,$db)=booking_retention_cli($runtime['schema']);
require $root.'/landing/application/models/Landing_model.php';require APPPATH.'models/Operations_model.php';
$landing=new Landing_model();$landing->db=$db;$master=new Operations_model();$master->db=$db;
$checks=0;function ingress_check($ok,$name){global $checks;if(!$ok)throw new RuntimeException('FAIL '.$name);$checks++;echo 'PASS '.$name.PHP_EOL;}
$available=array_values(array_filter($landing->get_all_public_rooms(),function($r){return !empty($r['available_count']);}));
if(!$available)throw new RuntimeException('Synthetic public available room required.');
$suffix=bin2hex(random_bytes(5));$payload=array('fullname'=>'Ingress fixture '.$suffix,'phone'=>'0898'.random_int(10000000,99999999),'room_interest'=>$available[0]['name'],'move_in_plan'=>(new DateTimeImmutable('now',new DateTimeZone('Asia/Jakarta')))->format('Y-m-d'),'message'=>'Synthetic ingress regression only');
ingress_check($landing->save_booking_request($payload),'landing stores first valid request');
$row=$db->where('fullname',$payload['fullname'])->get('gh_booking_requests')->row_array();$id=(int)$row['id'];
ingress_check(count($master->get_rows('bookings',array('q'=>$suffix)))===1,'master default active list shows new landing lead');
foreach(array('new','contacted','survey') as $status){$db->where('id',$id)->update('gh_booking_requests',array('status'=>$status));ingress_check($landing->save_booking_request($payload)&&$db->where('fullname',$payload['fullname'])->count_all_results('gh_booking_requests')===1,'recent active '.$status.' request deduplicates');}
$db->where('id',$id)->update('gh_booking_requests',array('archived_at'=>date('Y-m-d H:i:s'),'archive_reason'=>'Synthetic archive to reproduce hidden duplicate'));$archived=$db->where('id',$id)->get('gh_booking_requests')->row_array();
ingress_check(count($master->get_rows('bookings',array('q'=>$suffix)))===0,'master intentionally hides archived request');
ingress_check($landing->save_booking_request($payload),'repeated request after archive reports saved');
$total=$db->where('fullname',$payload['fullname'])->count_all_results('gh_booking_requests');$active=$master->get_rows('bookings',array('q'=>$suffix));
ingress_check($total===2&&count($active)===1,'archived repeat creates separate active lead visible in master');
ingress_check($db->where('id',$id)->get('gh_booking_requests')->row_array()===$archived,'archived original is never revived or modified');
ingress_check($landing->save_booking_request($payload)&&$db->where('fullname',$payload['fullname'])->count_all_results('gh_booking_requests')===2,'subsequent active repeat still deduplicates');
$base=rtrim($runtime['url']??'','/');if(!preg_match('#^http://127\.0\.0\.1:[0-9]+$#D',$base))throw new RuntimeException('Owned loopback URL required.');
$cookie=tempnam(sys_get_temp_dir(),'gh_ingress_');
try {
    $curl=curl_init($base.'/landing/index.php');curl_setopt_array($curl,array(CURLOPT_RETURNTRANSFER=>TRUE,CURLOPT_FOLLOWLOCATION=>TRUE,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_TIMEOUT=>20));$html=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);
    ingress_check($status===200&&preg_match('/<form class="booking-form"[^>]*action="([^"]+)"[^>]*>(.*?)<\/form>/s',$html,$form)&&preg_match('/name="(csrf_[^"]+)" value="([a-f0-9]+)"/',$form[2],$csrf),'rendered landing booking form provides action and CSRF token');
    $action=html_entity_decode($form[1]);if(strpos($action,$base.'/')!==0)throw new RuntimeException('Unexpected non-owned form origin.');
    $httpPayload=$payload;$httpPayload['fullname']='HTTP ingress '.$suffix;$httpPayload['phone']='0897'.random_int(10000000,99999999);$httpPayload[$csrf[1]]=$csrf[2];
    $curl=curl_init($action);curl_setopt_array($curl,array(CURLOPT_RETURNTRANSFER=>TRUE,CURLOPT_FOLLOWLOCATION=>TRUE,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_POST=>TRUE,CURLOPT_POSTFIELDS=>http_build_query($httpPayload),CURLOPT_TIMEOUT=>20));$result=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);
    $httpRows=$db->where('fullname',$httpPayload['fullname'])->get('gh_booking_requests')->result_array();
    ingress_check($status===200&&count($httpRows)===1&&$httpRows[0]['source']==='landing','actual rendered form POST traverses route controller and database insert');
    ingress_check(strpos($result,'Permintaan booking sudah kami terima.')!==FALSE&&count($master->get_rows('bookings',array('q'=>$httpPayload['fullname'])))===1,'HTTP success redirect corresponds to visible active master booking');
}finally{@unlink($cookie);}
echo json_encode(array('passed'=>$checks,'environment'=>'isolated_synthetic','real_booking_writes'=>0)).PHP_EOL;
