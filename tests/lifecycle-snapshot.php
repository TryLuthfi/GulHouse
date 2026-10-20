<?php
// Read-only synthetic fixture snapshot, never available via the development router.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$root=dirname(__DIR__); $runtime=json_decode(file_get_contents($root.'/.e2e/lifecycle/runtime.json'),true);
$schema=$runtime['schema']??'';
if(!preg_match('/^gulhouse_test_[A-Za-z0-9_]+$/D',$schema)) throw new RuntimeException('Synthetic schema required.');
$env=array(); foreach(array($root.'/master/.env',$root.'/.env',$root.'/landing/.env') as $p) if(is_file($p)) {$env=parse_ini_file($p);break;}
$db=new mysqli($env['HOSTNAME']??'127.0.0.1',$env['USERNAME']??'root',$env['PASSWORD']??'',$schema,(int)($env['DB_PORT']??3306)); $db->set_charset('utf8mb4');
$db->query('START TRANSACTION READ ONLY');
$id=(int)($argv[1]??0);$result=array();
foreach(array('gh_resident_cases'=>'id','gh_facility_inspections'=>'case_id','gh_resident_files'=>'case_id','gh_checkout_requests'=>'case_id','gh_refund_payments'=>'case_id') as $table=>$key){
 $result[$table]=$db->query('SELECT * FROM `'.$table.'`'.($id?' WHERE `'.$key.'`='.$id:'').' ORDER BY id')->fetch_all(MYSQLI_ASSOC);
}
if($id&&$result['gh_resident_cases']){$c=$result['gh_resident_cases'][0];$stay=(int)$c['stay_id'];$result['bills']=$db->query('SELECT * FROM gh_room_bills WHERE stay_id='.$stay)->fetch_all(MYSQLI_ASSOC);$result['deposit']=$db->query('SELECT * FROM gh_deposit_transactions WHERE stay_id='.$stay)->fetch_all(MYSQLI_ASSOC);$result['stay']=$db->query('SELECT * FROM gh_tenant_stays WHERE id='.$stay)->fetch_assoc();}
$db->rollback(); echo json_encode($result);
