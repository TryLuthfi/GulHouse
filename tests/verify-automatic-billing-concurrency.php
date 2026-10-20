<?php
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';
$rt=json_decode(file_get_contents($root.'/.e2e/all-revisions-20261020/wasp-policy-runtime.json'),TRUE);
if(!preg_match('/^gulhouse_test_wasp_policy_[A-Za-z0-9_]+$/D',$rt['schema']??''))throw new RuntimeException('Dedicated WASP synthetic schema required.');
list($retention,$db)=booking_retention_cli($rt['schema']);require APPPATH.'models/Operations_model.php';
class ConcurrentAutomaticProbe extends Operations_model {public $db;}
$ops=new ConcurrentAutomaticProbe();$ops->db=$db;
if(($argv[1]??'')==='--worker'){echo json_encode($ops->automatic_billing('2031-02-21',7,TRUE));exit;}
function autobinsert($table,$row){global $db;$db->insert($table,$row);return (int)$db->insert_id();}
$checks=0;function autobcheck($ok,$label){global $checks;if(!$ok)throw new RuntimeException('FAIL '.$label);$checks++;echo 'PASS '.$label.PHP_EOL;}
$suffix=bin2hex(random_bytes(6));$prop=$db->get('gh_properties',1)->row_array();$type=$db->get('gh_room_types',1)->row_array();$period=$db->where('period_key','2031-01')->get('gh_billing_periods')->row_array();if(!$period)throw new RuntimeException('Run automatic billing fixture test first.');
$room=autobinsert('gh_rooms',array('property_id'=>$prop['id'],'room_type_id'=>$type['id'],'room_code'=>'ARACE'.$suffix,'room_label'=>'ARACE-'.$suffix,'monthly_price'=>9000000,'deposit'=>9000000,'status'=>'occupied','is_public'=>0));$tenant=autobinsert('gh_tenants',array('fullname'=>'Synthetic auto race '.$suffix,'phone'=>'089612345678'));$stay=autobinsert('gh_tenant_stays',array('tenant_id'=>$tenant,'room_id'=>$room,'stay_status'=>'active','check_in_date'=>'2031-01-31','check_out_date'=>'2031-02-28','monthly_price'=>1500000,'deposit'=>200000));$case=autobinsert('gh_resident_cases',array('access_hash'=>hash('sha256',$suffix),'full_name'=>'Synthetic auto race '.$suffix,'phone'=>'089612345678','source'=>'website','room_id'=>$room,'tenant_id'=>$tenant,'stay_id'=>$stay,'planned_start'=>'2031-01-31','status'=>'active','billing_anchor_date'=>'2031-01-31','billing_policy_version'=>Billing_policy_model::VERSION,'rolling_monthly'=>1));autobinsert('gh_room_bills',array('period_id'=>$period['id'],'room_id'=>$room,'tenant_id'=>$tenant,'stay_id'=>$stay,'base_price'=>1500000,'deposit_amount'=>200000,'due_date'=>'2031-01-31','bill_status'=>'unpaid'));
$lock='gh_issue_'.substr(hash('sha256',$db->database),0,48);$db->query('SELECT GET_LOCK(?,10)',array($lock));$workers=array();
try{
    for($i=0;$i<2;$i++){$pipes=array();$proc=proc_open(array(PHP_BINARY,__FILE__,'--worker'),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes,dirname(__DIR__));if(!is_resource($proc))throw new RuntimeException('Cannot start independent worker.');fclose($pipes[0]);$workers[]=array($proc,$pipes);}
    usleep(250000);$db->query('SELECT RELEASE_LOCK(?)',array($lock));$results=array();
    foreach($workers as $worker){$body=stream_get_contents($worker[1][1]);$err=stream_get_contents($worker[1][2]);fclose($worker[1][1]);fclose($worker[1][2]);$exit=proc_close($worker[0]);$r=json_decode($body,TRUE);if($exit!==0||$err!==''||!is_array($r))throw new RuntimeException('Independent billing worker failed.');$results[]=$r;}
}catch(Throwable $e){$db->query('SELECT RELEASE_LOCK(?)',array($lock));foreach($workers as $worker)@proc_terminate($worker[0]);throw $e;}
autobcheck(count($results)===2&&$results[0]['ok']&&$results[1]['ok'],'both independent automatic billing processes complete safely');
$rows=$db->where('stay_id',$stay)->where('due_date','2031-02-28')->get('gh_room_bills')->result_array();autobcheck(count($rows)===1,'overlapping jobs create exactly one recurring invoice');
autobcheck((int)$rows[0]['base_price']===1500000&&(int)$rows[0]['deposit_amount']===0,'concurrent invoice keeps agreement price and zero recurring deposit');
autobcheck($db->where('bill_id',$rows[0]['id'])->count_all_results('gh_lifecycle_bill_policies')===1,'overlapping jobs attach exactly one policy row');
// The fixture remains synthetic; stop its future catch-up participation after the race.
$db->where('id',$case)->update('gh_resident_cases',array('status'=>'checked_out'));$db->where('id',$stay)->update('gh_tenant_stays',array('stay_status'=>'ended'));
echo json_encode(array('passed'=>$checks,'independent_worker_processes'=>2,'environment'=>'dedicated_synthetic','actual_data_writes'=>0)).PHP_EOL;
