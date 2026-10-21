<?php
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';require APPPATH.'libraries/Apartment_units.php';
list($unused,$admin)=booking_retention_cli('gulhouse_db');$dir=$root.'/.e2e/apartment-unit-location-20261021';$before=json_decode(file_get_contents($dir.'/ciko-before.json'),true);
$schema='gulhouse_test_ciko_aptunit_'.date('Ymd_His').'_'.random_int(1000,9999);if(!preg_match('/^gulhouse_test_ciko_aptunit_[0-9_]+$/D',$schema))throw new RuntimeException('Schema guard');
$admin->query('CREATE DATABASE `'.$schema.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');list($unused,$db)=booking_retention_cli($schema);$checks=array();
$ck=function($n,$ok)use(&$checks){$checks[]=array('name'=>$n,'status'=>$ok?'PASS':'FAIL');if(!$ok)throw new RuntimeException('FAIL '.$n);};
$hash=function($t)use($db){$rows=$db->get($t)->result_array();$e=array_map('json_encode',$rows);sort($e,SORT_STRING);return hash('sha256',json_encode($e));};
$defs=Gulhouse_apartment_buildings::definitions();$owned=array();$props=array();foreach($before['properties'] as $p){if($p['code']==='APT')$props[]=$p;foreach($defs as $type=>$def)if($p['code']===$def['code']){$owned[$type]=(int)$p['id'];$props[]=$p;}}
$sourceRooms=array_values(array_filter($before['rooms'],function($r)use($owned){return in_array((int)$r['property_id'],array_values($owned),true);}));$types=$admin->get('gh_room_types')->result_array();
try {
 foreach(array('gh_properties','gh_rooms','gh_room_types','gh_expenses') as $t)$db->query('CREATE TABLE `'.$t.'` LIKE gulhouse_db.`'.$t.'`');
 $db->query('ALTER TABLE gh_rooms ADD COLUMN IF NOT EXISTS unit_city VARCHAR(120) NULL, ADD COLUMN IF NOT EXISTS unit_address TEXT NULL');
 foreach(array('gh_tenant_stays','gh_contracts','gh_room_bills','gh_payments','gh_ciko_history') as $t)$db->query('CREATE TABLE `'.$t.'` (id INT PRIMARY KEY,room_id INT NOT NULL,tenant_id INT NOT NULL,amount INT NOT NULL,note VARCHAR(100)) ENGINE=InnoDB');
 $db->query('CREATE TABLE gh_ciko_property_refs (id INT PRIMARY KEY,property_id INT NOT NULL) ENGINE=InnoDB');
 $tables=array('gh_properties','gh_rooms','gh_room_types','gh_expenses','gh_tenant_stays','gh_contracts','gh_room_bills','gh_payments','gh_ciko_history','gh_ciko_property_refs');
 $reset=function()use($db,$tables,$props,$sourceRooms,$types,$owned){foreach($tables as $t)$db->query('DELETE FROM `'.$t.'`');foreach($props as $p){if(in_array((int)$p['id'],array_values($owned),true)){$p['city']='CIKO City '.$p['id'];$p['address']='Synthetic address '.$p['id'];}$db->insert('gh_properties',$p);}foreach($types as $type)$db->insert('gh_room_types',$type);foreach($sourceRooms as $r){$r['unit_city']=null;$r['unit_address']=null;$db->insert('gh_rooms',$r);}foreach(array('gh_tenant_stays','gh_contracts','gh_room_bills','gh_payments','gh_ciko_history') as $t)$db->insert($t,array('id'=>991,'room_id'=>128,'tenant_id'=>992,'amount'=>2500000,'note'=>'Synthetic unit-location preservation'));};
 $reset();$prior=$db->order_by('id')->get('gh_rooms')->result_array();$related=array();foreach(array('gh_tenant_stays','gh_contracts','gh_room_bills','gh_payments','gh_ciko_history') as $t)$related[$t]=$hash($t);
 $r=Gulhouse_apartment_units::merge($db,$owned);$ck('Five existing IDs returned as merged',count($r['moved_room_ids'])===5&&count(array_diff(array(128,129,130,131,132),$r['moved_room_ids']))===0);$ck('Exactly four owned child properties removed',count($r['removed_owned_properties'])===4);
 foreach($prior as $room){$new=$db->where('id',$room['id'])->get('gh_rooms')->row_array();$originalPid=(int)$room['property_id'];$expected=$room;$expected['property_id']='4';$expected['unit_city']='CIKO City '.$originalPid;$expected['unit_address']='Synthetic address '.$originalPid;$ck('Unit '.$room['id'].' location copied; all other fields preserved',$new===$expected);}
 foreach($related as $t=>$h)$ck('Related table remains identical: '.$t,$hash($t)===$h);
 $ck('Single shared APT active with five rooms',$db->where('property_type','apartment')->count_all_results('gh_properties')===1&&$db->where('id',4)->get('gh_properties')->row_array()['is_active']==='1'&&$db->where('property_id',4)->count_all_results('gh_rooms')===5);
 $db->trans_commit();$rh=$hash('gh_rooms');$ph=$hash('gh_properties');$again=Gulhouse_apartment_units::merge($db,$owned);$db->trans_commit();$ck('Rerun idempotent and existing unit locations retained',$rh===$hash('gh_rooms')&&$ph===$hash('gh_properties')&&count($again['moved_room_ids'])===0&&count($again['removed_owned_properties'])===0);
 foreach(array('location_conflict','direct_relation','extra_unit','identity_mismatch','missing_unit','wrong_type','invalid_manifest') as $case){$reset();$manifest=$owned;
  if($case==='location_conflict')$db->where('id',130)->update('gh_rooms',array('unit_city'=>'Different existing unit city'));
  if($case==='direct_relation')$db->insert('gh_ciko_property_refs',array('id'=>1,'property_id'=>$owned['Mejestic']));
  if($case==='extra_unit'){$extra=$sourceRooms[0];$extra['id']=999;$extra['room_code']='CIKO-EXTRA';$db->insert('gh_rooms',$extra);}
  if($case==='identity_mismatch')$db->where('id',$owned['Mejestic'])->update('gh_properties',array('code'=>'UNOWNED CODE'));
  if($case==='missing_unit')$db->where('id',131)->delete('gh_rooms');
  if($case==='wrong_type')$db->where('id',130)->update('gh_rooms',array('room_type_id'=>4));
  if($case==='invalid_manifest')$manifest['The Nest']=(string)$manifest['The Nest'];
  $beforeRoom=$hash('gh_rooms');$beforeProp=$hash('gh_properties');$blocked=false;try{Gulhouse_apartment_units::merge($db,$manifest);$db->trans_rollback();}catch(Throwable $e){$blocked=true;}$ck('Reject '.$case.' with complete rollback',$blocked&&$beforeRoom===$hash('gh_rooms')&&$beforeProp===$hash('gh_properties'));
 }
 $result=array('passed'=>count($checks),'failed'=>0,'checks'=>$checks,'environment'=>'owned_synthetic','real_business_writes'=>0);
}catch(Throwable $e){$result=array('passed'=>count(array_filter($checks,function($x){return $x['status']==='PASS';})),'failed'=>1,'error'=>$e->getMessage(),'schema'=>$schema,'checks'=>$checks);}
file_put_contents($dir.'/ciko-fixture-results.json',json_encode($result,JSON_PRETTY_PRINT));if(!$result['failed'])$admin->query('DROP DATABASE `'.$schema.'`');echo json_encode($result,JSON_PRETTY_PRINT);exit($result['failed']?1:0);
