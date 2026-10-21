<?php
// Independent helper tests on an explicitly owned disposable synthetic schema.
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';require APPPATH.'libraries/Apartment_buildings.php';
list($unused,$admin)=booking_retention_cli('gulhouse_db');
$schema='gulhouse_test_ciko_aptsplit_'.date('Ymd_His').'_'.random_int(1000,9999);
if(!preg_match('/^gulhouse_test_ciko_aptsplit_[0-9_]+$/D',$schema))throw new RuntimeException('Fixture guard');
$admin->query('CREATE DATABASE `'.$schema.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
list($unused,$db)=booking_retention_cli($schema);$checks=array();
$check=function($name,$ok)use(&$checks){$checks[]=array('name'=>$name,'status'=>$ok?'PASS':'FAIL');if(!$ok)throw new RuntimeException('FAIL '.$name);};
$hash=function($table)use($db){$rows=$db->get($table)->result_array();$r=array_map('json_encode',$rows);sort($r,SORT_STRING);return hash('sha256',json_encode($r));};
$rooms=$admin->where_in('id',array(128,129,130,131,132))->order_by('id')->get('gh_rooms')->result_array();
$property=$admin->where('id',4)->get('gh_properties')->row_array();$types=$admin->get('gh_room_types')->result_array();
try {
 foreach(array('gh_properties','gh_rooms','gh_room_types','gh_expenses') as $table)$db->query('CREATE TABLE `'.$table.'` LIKE gulhouse_db.`'.$table.'`');
 foreach(array('gh_tenant_stays','gh_contracts','gh_room_bills','gh_payments','gh_ciko_history') as $table)$db->query('CREATE TABLE `'.$table.'` (id INT PRIMARY KEY,room_id INT NOT NULL,tenant_id INT NOT NULL,amount INT NOT NULL,note VARCHAR(100) NOT NULL) ENGINE=InnoDB');
 $reset=function()use($db,$rooms,$property,$types){foreach(array('gh_properties','gh_rooms','gh_room_types','gh_expenses','gh_tenant_stays','gh_contracts','gh_room_bills','gh_payments','gh_ciko_history') as $t)$db->query('DELETE FROM `'.$t.'`');$db->insert('gh_properties',$property);foreach($types as $t)$db->insert('gh_room_types',$t);foreach($rooms as $r){$r['property_id']=4;$db->insert('gh_rooms',$r);}foreach(array('gh_tenant_stays','gh_contracts','gh_room_bills','gh_payments','gh_ciko_history') as $t)$db->insert($t,array('id'=>991,'room_id'=>128,'tenant_id'=>992,'amount'=>2500000,'note'=>'Synthetic CIKO preservation check'));};
 $reset();$before=$db->order_by('id')->get('gh_rooms')->result_array();$related=array();foreach(array('gh_tenant_stays','gh_contracts','gh_room_bills','gh_payments','gh_ciko_history') as $t)$related[$t]=$hash($t);
 $result=Gulhouse_apartment_buildings::apply($db);
 $check('Success maps exactly five existing room IDs',array_values($result['moved_room_ids'])===array(128,129,130,131,132));
 $check('Four separate building properties created',count($result['properties'])===4);
 $after=$db->order_by('id')->get('gh_rooms')->result_array();$intact=count($before)===count($after);foreach($before as $i=>$r){$a=$after[$i];unset($r['property_id'],$a['property_id']);$intact=$intact&&$r===$a;}$check('All room fields and timestamps except property_id preserved',$intact);
 foreach($related as $t=>$h)$check('Related row IDs and values unchanged: '.$t,$hash($t)===$h);
 $check('Old APT archived without deleting property',$db->where('id',4)->get('gh_properties')->row_array()['is_active']==='0');
 $check('New buildings have independent blank location fields',$db->where('id !=',4)->where('(city IS NOT NULL OR address IS NOT NULL)',null,false)->count_all_results('gh_properties')===0);
 $db->trans_commit();$stableRooms=$hash('gh_rooms');$stableProperties=$hash('gh_properties');$second=Gulhouse_apartment_buildings::apply($db);$db->trans_commit();
 $check('Rerun is idempotent with no moved rooms',count($second['moved_room_ids'])===0&&$hash('gh_rooms')===$stableRooms&&$hash('gh_properties')===$stableProperties);
 foreach(array('extra','missing','wrongtype','expense','inactive_target') as $case){
  $reset();
  if($case==='extra'){$r=$rooms[0];$r['id']=999;$r['property_id']=4;$r['room_code']='CIKO-UNKNOWN';$db->insert('gh_rooms',$r);}
  if($case==='missing')$db->where('id',132)->delete('gh_rooms');
  if($case==='wrongtype')$db->where('id',130)->update('gh_rooms',array('room_type_id'=>4));
  if($case==='inactive_target'){$p=$property;$p['id']=998;$p['code']='APT MEJESTIC';$p['is_active']=0;$db->insert('gh_properties',$p);}
  if($case==='expense')$db->query("INSERT INTO gh_expenses (expense_date,property_id,category,description,amount,payment_status) VALUES ('2026-10-21',4,'operational','Synthetic CIKO expense',1,'unpaid')");
  $rh=$hash('gh_rooms');$ph=$hash('gh_properties');$rejected=false;try{Gulhouse_apartment_buildings::apply($db);$db->trans_rollback();}catch(Throwable $e){$rejected=true;}
  $check('Reject '.$case.' and rollback all partial updates',$rejected&&$hash('gh_rooms')===$rh&&$hash('gh_properties')===$ph);
 }
 $result=array('passed'=>count($checks),'failed'=>0,'checks'=>$checks,'environment'=>'owned_synthetic','real_business_writes'=>0);
}catch(Throwable $e){$result=array('passed'=>count(array_filter($checks,function($x){return $x['status']==='PASS';})),'failed'=>1,'checks'=>$checks,'error'=>$e->getMessage(),'schema'=>$schema);}
$dir=$root.'/.e2e/apartment-split-20261021';if(!is_dir($dir))mkdir($dir,0700,true);file_put_contents($dir.'/ciko-fixture-results.json',json_encode($result,JSON_PRETTY_PRINT));
if(!$result['failed'])$admin->query('DROP DATABASE `'.$schema.'`');echo json_encode($result,JSON_PRETTY_PRINT);exit($result['failed']?1:0);
