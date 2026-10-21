<?php
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';require APPPATH.'models/Master_model.php';require $root.'/landing/application/models/Landing_model.php';
function base_url($path=''){return 'http://localhost/GulHouse/landing/'.ltrim($path,'/');}
list($unused,$admin)=booking_retention_cli('gulhouse_db');$schema='gulhouse_test_ciko_unitmodel_'.date('Ymd_His').'_'.random_int(1000,9999);if(!preg_match('/^gulhouse_test_ciko_unitmodel_[0-9_]+$/D',$schema))throw new RuntimeException('Fixture guard');
$admin->query('CREATE DATABASE `'.$schema.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');list($unused,$db)=booking_retention_cli($schema);$checks=array();$ck=function($name,$ok)use(&$checks){$checks[]=array('name'=>$name,'status'=>$ok?'PASS':'FAIL');if(!$ok)throw new RuntimeException('FAIL '.$name);};
try{
 foreach(array('gh_properties','gh_rooms','gh_room_types') as $t){$db->query('CREATE TABLE `'.$t.'` LIKE gulhouse_db.`'.$t.'`');foreach($admin->get($t)->result_array() as $row)$db->insert($t,$row);}
 $master=new Master_model();$master->db=$db;$landing=new Landing_model();$landing->db=$db;
 $payload=function($id)use($db){$r=$db->where('id',$id)->get('gh_rooms')->row_array();$keys=array('id','property_id','room_type_id','room_code','room_name','room_label','floor_no','deposit','monthly_price','status','is_public','unit_city','unit_address');return array_intersect_key($r,array_fill_keys($keys,true));};
 $a=$payload(128);$a['unit_city']='CIKO Alpha';$a['unit_address']='CIKO Alpha Street';$ck('Master saves first unit-specific city and address',$master->save_room($a));
 $b=$payload(129);$b['unit_city']='CIKO Beta';$b['unit_address']='CIKO Beta Street';$ck('Master saves second unit-specific city and address',$master->save_room($b));
 $db->where('code','APT')->update('gh_properties',array('city'=>'CIKO SHARED WRONG','address'=>'CIKO shared address not used'));
 $cards=$landing->get_all_public_rooms();$apts=array_values(array_filter($cards,function($x){return $x['property_type']==='apartment';}));$byId=array_column($apts,null,'unit_id');
 $ck('Four priced APT units retain individual cards',count($apts)===4&&count($byId)===4);
 $ck('Same-type The Nest unit128 exposes own city/address',$byId[128]['city']==='CIKO Alpha'&&$byId[128]['property_address']==='CIKO Alpha Street');
 $ck('Same-type The Nest unit129 exposes distinct own city/address',$byId[129]['city']==='CIKO Beta'&&$byId[129]['property_address']==='CIKO Beta Street');
 $ck('All APT cards retain one shared APT property',count(array_filter($apts,function($x){return $x['code']==='APT';}))===4);
 $ck('Per-unit slugs do not collide',count(array_unique(array_column($apts,'slug')))===4);
 $ck('Other blank-unit location does not inherit wrong shared property city',$byId[130]['city']===''&&$byId[130]['property_address']==='');
 $search=$master->get_rooms(array('q'=>'CIKO Alpha'));$ck('City search finds only correct unit',count($search)===1&&(int)$search[0]['id']===128);
 $search=$master->get_rooms(array('q'=>'CIKO Beta Street'));$ck('Address search finds only correct unit',count($search)===1&&(int)$search[0]['id']===129);
 $omit=$payload(128);unset($omit['unit_city'],$omit['unit_address']);$ck('Model save without new location fields remains compatible',$master->save_room($omit));$saved=$master->get_room(128);$ck('Omitted fields preserve existing unit location',$saved['unit_city']==='CIKO Alpha'&&$saved['unit_address']==='CIKO Alpha Street');
 $blank=$payload(129);$blank['unit_city']='';$blank['unit_address']='';$ck('Location can be cleared intentionally',$master->save_room($blank));$saved=$master->get_room(129);$ck('Cleared unit location stored as null',$saved['unit_city']===null&&$saved['unit_address']===null);
 $house=$db->select('r.*')->from('gh_rooms r')->join('gh_properties p','p.id=r.property_id')->where('p.property_type','house')->where('r.monthly_price >',0)->get()->row_array();$db->where('id',$house['property_id'])->update('gh_properties',array('city'=>'CIKO House City','address'=>'CIKO House Address'));$db->where('id',$house['id'])->update('gh_rooms',array('unit_city'=>'WRONG UNIT FIELD','unit_address'=>'WRONG UNIT ADDRESS'));
 $property=$db->where('id',$house['property_id'])->get('gh_properties')->row_array();$houseCards=array_values(array_filter($landing->get_all_public_rooms(),function($x)use($property){return $x['code']===$property['code'];}));$ck('House cards still follow property city and address',count($houseCards)>0&&count(array_filter($houseCards,function($x){return $x['city']==='CIKO House City'&&$x['property_address']==='CIKO House Address';}))===count($houseCards));
 $legacy=$landing->get_room_type_detail('apt-the-nest');$ck('Old generic APT slug remains resolvable',$legacy&&$legacy['code']==='APT');
 $legacy=$landing->get_room_type_detail('apt-the-nest-the-nest');$ck('Temporary split-building slug remains resolvable',$legacy&&$legacy['code']==='APT');
 $result=array('passed'=>count($checks),'failed'=>0,'checks'=>$checks,'environment'=>'owned_synthetic','real_business_writes'=>0);
}catch(Throwable $e){$result=array('passed'=>count(array_filter($checks,function($x){return $x['status']==='PASS';})),'failed'=>1,'error'=>$e->getMessage(),'schema'=>$schema,'checks'=>$checks);}
$dir=$root.'/.e2e/apartment-unit-location-20261021';file_put_contents($dir.'/ciko-model-results.json',json_encode($result,JSON_PRETTY_PRINT));if(!$result['failed'])$admin->query('DROP DATABASE `'.$schema.'`');echo json_encode($result,JSON_PRETTY_PRINT);exit($result['failed']?1:0);
