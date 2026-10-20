<?php
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';
$rt=json_decode(file_get_contents($root.'/.e2e/mobile/bookingretain26/runtime.json'),TRUE);
if(!preg_match('/^gulhouse_test_mobile_bookingretain26_[0-9_]+$/D',$rt['schema']??'')||($rt['fixture']['contains_real_occupants']??TRUE)!==FALSE)throw new RuntimeException('Owned synthetic schema required.');
list($retention,$db)=booking_retention_cli($rt['schema']);require APPPATH.'models/Master_model.php';
class ConcurrentSettlementProbe extends Master_model {public $db;}
$model=new ConcurrentSettlementProbe();$model->db=$db;
if(($argv[1]??'')==='--worker'){
    $ok=$model->save_payment(array('payment_id'=>0,'room_bill_id'=>(int)$argv[2],'amount'=>(int)$argv[3],'payment_type'=>$argv[4],'payment_date'=>'2031-01-02','method'=>'Transfer','request_key'=>$argv[5],'notes'=>'Synthetic race'));
    echo json_encode(array('ok'=>$ok,'error'=>$model->get_payment_error()));exit;
}
$checks=0;function racecheck($ok,$label){global $checks;if(!$ok)throw new RuntimeException('FAIL '.$label);$checks++;echo 'PASS '.$label.PHP_EOL;}
function raceinsert($table,$row){global $db;$db->insert($table,$row);return (int)$db->insert_id();}
$suffix=bin2hex(random_bytes(6));$prop=$db->get('gh_properties',1)->row_array();$type=$db->get('gh_room_types',1)->row_array();
$room=raceinsert('gh_rooms',array('property_id'=>$prop['id'],'room_type_id'=>$type['id'],'room_code'=>'RACE'.$suffix,'room_label'=>'RACE-'.$suffix,'monthly_price'=>1000000,'deposit'=>500000,'status'=>'reserved','is_public'=>0));$tenant=raceinsert('gh_tenants',array('fullname'=>'Synthetic race '.$suffix,'phone'=>'089612345678'));$stay=raceinsert('gh_tenant_stays',array('tenant_id'=>$tenant,'room_id'=>$room,'stay_status'=>'reserved','check_in_date'=>'2031-01-01','check_out_date'=>'2031-02-01','monthly_price'=>1000000,'deposit'=>500000));
$bills=array();foreach(array('2031-01','2031-02') as $key){$p=$db->where('period_key',$key)->get('gh_billing_periods')->row_array();if(!$p)throw new RuntimeException('Run focused settlement fixture test first.');$bills[]=raceinsert('gh_room_bills',array('period_id'=>$p['id'],'room_id'=>$room,'tenant_id'=>$tenant,'stay_id'=>$stay,'base_price'=>1000000,'deposit_amount'=>500000,'bill_status'=>'unpaid','due_date'=>'2031-01-01'));}
function run_settlement_race($stay,$requests){
    global $db;$workers=array();$db->trans_begin();$db->query('SELECT id FROM gh_tenant_stays WHERE id=? FOR UPDATE',array($stay));
    try{
        foreach($requests as $request){$pipes=array();$proc=proc_open(array(PHP_BINARY,__FILE__,'--worker',(string)$request[0],(string)$request[1],$request[2],'race_'.bin2hex(random_bytes(16))),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes,dirname(__DIR__));if(!is_resource($proc))throw new RuntimeException('Unable to start independent race worker.');fclose($pipes[0]);$workers[]=array($proc,$pipes);}
        // Both independent database connections queue behind the same stay lock.
        usleep(250000);$db->trans_commit();$results=array();
        foreach($workers as $worker){$output=stream_get_contents($worker[1][1]);$error=stream_get_contents($worker[1][2]);fclose($worker[1][1]);fclose($worker[1][2]);$exit=proc_close($worker[0]);$result=json_decode($output,TRUE);if($exit!==0||!is_array($result)||$error!=='')throw new RuntimeException('Race worker failed; no result claimed.');$results[]=$result;}
        return $results;
    }catch(Throwable $e){$db->trans_rollback();foreach($workers as $worker){@proc_terminate($worker[0]);}throw $e;}
}
$results=run_settlement_race($stay,array(array($bills[0],1000000,'rent'),array($bills[0],1000000,'rent')));racecheck(count(array_filter($results,function($r){return $r['ok'];}))===1,'two simultaneous independent rent submissions yield exactly one success');
$rent=$db->select('COUNT(*) AS n,SUM(amount) AS total',FALSE)->where('room_bill_id',$bills[0])->where('payment_type','rent')->get('gh_payments')->row_array();racecheck((int)$rent['n']===1&&(int)$rent['total']===1000000,'rent race never creates excess receipts');
$results=run_settlement_race($stay,array(array($bills[0],500000,'deposit'),array($bills[1],500000,'deposit')));racecheck(count(array_filter($results,function($r){return $r['ok'];}))===1,'different monthly invoices serialize the same stay deposit and yield one success');
$deposit=$db->select('COUNT(*) AS n,SUM(amount) AS total',FALSE)->where('stay_id',$stay)->get('gh_deposit_transactions')->row_array();racecheck((int)$deposit['n']===1&&(int)$deposit['total']===500000,'cross-invoice deposit race records only one actual received ledger row');
echo json_encode(array('passed'=>$checks,'independent_worker_processes'=>4,'environment'=>'owned_synthetic','actual_data_writes'=>0)).PHP_EOL;
