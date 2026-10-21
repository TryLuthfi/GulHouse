<?php
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';
require APPPATH.'models/Master_table_model.php';
list($unused,$admin)=booking_retention_cli('gulhouse_db');
$schema='gulhouse_test_ciko_masterpage_'.date('Ymd_His').'_'.random_int(1000,9999);
if(!preg_match('/^gulhouse_test_ciko_masterpage_[0-9_]+$/D',$schema))throw new RuntimeException('Fixture guard');
$admin->query('CREATE DATABASE `'.$schema.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');list($unused,$db)=booking_retention_cli($schema);
$checks=array();$ck=function($name,$ok)use(&$checks){$checks[]=array('name'=>$name,'status'=>$ok?'PASS':'FAIL');if(!$ok)throw new RuntimeException('FAIL '.$name);};
$ins=function($table,$row)use($db){if(!$db->insert($table,$row))throw new RuntimeException('Fixture insert failed '.$table);};
try {
 foreach(array('gh_properties','gh_room_types','gh_rooms','gh_tenants','gh_tenant_stays','gh_tenant_emergency_contacts') as $t)$db->query('CREATE TABLE `'.$t.'` LIKE gulhouse_db.`'.$t.'`');
 for($i=1;$i<=130;$i++)$ins('gh_room_types',array('id'=>$i,'name'=>'Type '.str_pad($i,3,'0',STR_PAD_LEFT),'description'=>$i%2?'Group odd':'Group even','is_active'=>$i%4?1:0));
 $statuses=array('available','occupied','reserved','maintenance','mess','inactive');$stays=array('active','reserved','internal','empty','ended');$types=array('house','apartment','other');
 for($i=1;$i<=123;$i++){
  $code='P'.str_pad($i,3,'0',STR_PAD_LEFT);$name=$i===5?'Percent % Place':($i===6?'Underscore _ Place':'Same Name');
  $ins('gh_properties',array('id'=>$i,'code'=>$code,'name'=>$name,'property_type'=>$types[($i-1)%3],'city'=>'City '.($i%5),'address'=>'Manual '.($i%7),'is_active'=>$i%4?1:0));
  $ins('gh_rooms',array('id'=>$i,'property_id'=>$i,'room_type_id'=>$i,'room_code'=>'R'.str_pad($i,3,'0',STR_PAD_LEFT),'room_name'=>'Same Room','room_label'=>'Unit '.str_pad($i,3,'0',STR_PAD_LEFT),'status'=>$statuses[($i-1)%6],'monthly_price'=>1000000+$i,'deposit'=>500000,'is_public'=>$i%2,'unit_city'=>'Unit City '.($i%5),'unit_address'=>'Unit Address '.($i%7)));
  $ins('gh_tenants',array('id'=>$i,'fullname'=>'Same Person','phone'=>'08'.str_pad($i,10,'0',STR_PAD_LEFT),'identity_number'=>str_pad($i,16,'0',STR_PAD_LEFT)));
  $ins('gh_tenant_stays',array('id'=>$i,'tenant_id'=>$i,'room_id'=>$i,'stay_status'=>$stays[($i-1)%5],'check_in_date'=>$i%2?'2026-10-01':null,'monthly_price'=>1000000+$i,'deposit'=>500000,'notes'=>'Fixture note '.($i%7)));
  $ins('gh_tenant_emergency_contacts',array('id'=>$i,'tenant_id'=>$i,'contact_name'=>'Latest '.$i,'phone'=>'0810000000'));
 }
 // Multiple contacts must not multiply a single stay in count/page/summary.
 $ins('gh_tenant_emergency_contacts',array('id'=>1001,'tenant_id'=>1,'contact_name'=>'Other contact','phone'=>'0820000000'));
 $model=new Master_table_model();$model->db=$db;
 $ck('Synthetic rows exceed largest requested page size',(int)$db->count_all('gh_rooms')===123);
 $totals=array('properties'=>123,'room-types'=>130,'rooms'=>123,'tenants'=>123);
 foreach($totals as $resource=>$expected) {
  $page=$model->page($resource);$ck($resource.' defaults to ten SQL-bounded rows',count($page['rows'])===10&&$page['pagination']['per_page']===10);
  $ck($resource.' summary includes every matching row',$page['summary']['total']===$expected&&$page['pagination']['total']===$expected);
  $ck($resource.' page query has SQL LIMIT and OFFSET',preg_match('/LIMIT 10 OFFSET 0\s*$/D',$db->last_query())===1);
  foreach(array(10,25,50,100) as $size){
   $ids=array();$pages=(int)ceil($expected/$size);
   for($p=1;$p<=$pages;$p++){
    $result=$model->page($resource,array(),array('page'=>$p,'per_page'=>$size));
    $ck($resource.' size'.$size.' page'.$p.' respects bound',count($result['rows'])===min($size,$expected-($p-1)*$size));
    $ck($resource.' size'.$size.' page'.$p.' stable full summary',$result['summary']===$page['summary']);
    $ids=array_merge($ids,array_column($result['rows'],'id'));
   }
   $ck($resource.' size'.$size.' no duplicates/gaps',count($ids)===$expected&&count(array_unique($ids))===$expected);
  }
  $beyond=$model->page($resource,array(),array('page'=>'99999999999999999999','per_page'=>25));
  $ck($resource.' huge page clamps to last page',$beyond['pagination']['page']===(int)ceil($expected/25)&&count($beyond['rows'])===$expected%25);
  $bad=$model->page($resource,array(),array('page'=>array('1'),'per_page'=>array('100'),'sort'=>array('bad'),'dir'=>array('desc')));
  $ck($resource.' array options fall back safely',$bad['pagination']['page']===1&&$bad['pagination']['per_page']===10&&$bad['pagination']['dir']==='asc');
  $bad=$model->page($resource,array(),array('page'=>'-8','per_page'=>'0','sort'=>'id; DROP TABLE gh_rooms;--','dir'=>'DESC; DROP TABLE gh_tenants;--'));
  $ck($resource.' unsafe sort and direction ignored',$bad['pagination']['page']===1&&$bad['pagination']['per_page']===10&&$bad['pagination']['dir']==='asc'&&isset($model->sorts($resource)[$bad['pagination']['sort']]));
  foreach($model->sorts($resource) as $sort=>$label){$a=$model->page($resource,array(),array('per_page'=>10,'sort'=>$sort,'dir'=>'desc'));$b=$model->page($resource,array(),array('page'=>2,'per_page'=>10,'sort'=>$sort,'dir'=>'desc'));$c=$model->page($resource,array(),array('per_page'=>10,'sort'=>$sort,'dir'=>'desc'));$ck($resource.' stable tied sort '.$sort,array_column($a['rows'],'id')===array_column($c['rows'],'id')&&!array_intersect(array_column($a['rows'],'id'),array_column($b['rows'],'id')));}
  $empty=$model->page($resource,array('q'=>'NO_SUCH_FIXTURE_VALUE'),array('page'=>99));
  $ck($resource.' empty filter is valid page state',$empty['rows']===array()&&$empty['summary']['total']===0&&$empty['pagination']['from']===0&&$empty['pagination']['to']===0&&$empty['pagination']['page']===1);
  $special=$model->page($resource,array('q'=>"' OR 1=1 --"));$ck($resource.' search SQL fragment remains literal',$special['summary']['total']===0);
  $structured=$model->page($resource,array('q'=>array('bad'),'status'=>array('bad'),'property_id'=>array(1)));$ck($resource.' structured filters ignored safely',$structured['summary']['total']===$expected);
  $found=$model->find($resource,1);$ck($resource.' lazy find selects one identity',(int)$found['id']===1&&preg_match('/LIMIT 1\s*$/D',$db->last_query())===1);
  $ck($resource.' lazy missing/malformed identity returns no data',!$model->find($resource,999999)&&!$model->find($resource,array(1))&&!$model->find($resource,'1 OR 1=1'));
 }
 $r=$model->page('properties');$ck('Properties global summary counts correct',$r['summary']===array('total'=>123,'active'=>93,'house'=>41,'apartment'=>41));
 $r=$model->page('properties',array('type'=>'house','status'=>'active'));$ck('Properties combined filters drive full summary',$r['summary']['total']===31&&$r['summary']['house']===31&&$r['summary']['active']===31);
 $r=$model->page('properties',array('q'=>'%'));$ck('Percent search is literal not wildcard',$r['summary']['total']===1&&(int)$r['rows'][0]['id']===5);
 $r=$model->page('properties',array('q'=>'_'));$ck('Underscore search is literal not wildcard',$r['summary']['total']===1&&(int)$r['rows'][0]['id']===6);
 $r=$model->page('room-types');$ck('Room type global counts aggregate all matching types',$r['summary']===array('total'=>130,'active'=>98,'used'=>123,'rooms'=>123));
 $r=$model->page('room-types',array('usage'=>'used','status'=>'inactive'));$ck('Room type having filter drives full summary',$r['summary']['total']===30&&$r['summary']['used']===30&&$r['summary']['rooms']===30&&$r['summary']['active']===0);
 $r=$model->page('room-types',array('property_type'=>'apartment'));$ck('Room type property filter stays scoped',$r['summary']['total']===41&&$r['summary']['rooms']===41);
 $r=$model->page('rooms');$ck('Room global summary correct',$r['summary']===array('total'=>123,'available'=>21,'occupied'=>21,'public'=>62));
 $r=$model->page('rooms',array('status'=>'available','public'=>'public'));$ck('Room status/public filters intersect full summary',$r['summary']['total']===21&&$r['summary']['available']===21&&$r['summary']['public']===21);
 $r=$model->page('rooms',array('property_id'=>5,'room_type_id'=>5));$ck('Room relation filters isolate exact unit',$r['summary']['total']===1&&(int)$r['rows'][0]['id']===5);
 $r=$model->page('rooms',array('q'=>'Unit City 3'));$ck('Room unit location search supported',$r['summary']['total']===25);
 $r=$model->page('tenants');$ck('Stay global counts include historical ended and avoid duplicate contacts',$r['summary']===array('total'=>123,'active'=>25,'reserved'=>25,'internal'=>25,'empty'=>24,'ended'=>24));
 $r=$model->page('tenants',array('stay_status'=>'ended'));$ck('Ended stays filter summary correct',$r['summary']['total']===24&&$r['summary']['ended']===24);
 $r=$model->page('tenants',array('property_id'=>1,'room_id'=>1));$ck('Duplicate contacts do not duplicate stay page or summary',$r['summary']['total']===1&&count($r['rows'])===1);
 $row=$r['rows'][0];$ck('Stay list contains previous table fields',array_key_exists('occupation',$row)&&array_key_exists('identity_number',$row)&&array_key_exists('contact_name',$row)&&array_key_exists('emergency_phone',$row));
 $detail=$model->find('tenants',1);$ck('Stay detail has complete original edit fields',isset($detail['tenant_id'])&&array_key_exists('birth_place_date',$detail)&&array_key_exists('identity_number',$detail)&&array_key_exists('relationship',$detail)&&array_key_exists('address',$detail));
 $ck('Emergency contact choice deterministic',$detail['contact_name']==='Latest 1');
 $r=$model->page('tenants',array('q'=>'Fixture note 2'));$ck('Tenant notes search includes all matching stays',$r['summary']['total']===18);
 $ck('SQL injection options did not alter fixture tables',(int)$db->count_all('gh_rooms')===123&&(int)$db->count_all('gh_tenants')===123);
 $badResource=false;try{$model->page('payments');}catch(InvalidArgumentException $e){$badResource=true;}$ck('Unknown resource rejected',$badResource);
 $result=array('passed'=>count($checks),'failed'=>0,'checks'=>$checks,'environment'=>'owned_synthetic','real_database_writes'=>0);
}catch(Throwable $e){$result=array('passed'=>count(array_filter($checks,function($c){return $c['status']==='PASS';})),'failed'=>1,'error'=>$e->getMessage(),'schema'=>$schema,'checks'=>$checks);}
$dir=$root.'/.e2e/master-pagination-20261021';file_put_contents($dir.'/ciko-model-results.json',json_encode($result,JSON_PRETTY_PRINT));
$keep=in_array('--keep-http-fixture',$argv,true)&&!$result['failed'];
if($keep){
 foreach($admin->query('SHOW TABLES')->result_array() as $item){$table=array_values($item)[0];if(!$db->table_exists($table))$db->query('CREATE TABLE `'.$table.'` LIKE gulhouse_db.`'.$table.'`');}
 foreach(array('gh_region_provinces','gh_region_regencies','gh_region_districts') as $table)$db->query('INSERT INTO '.$table.' SELECT * FROM gulhouse_db.'.$table);
 $password=bin2hex(random_bytes(18));$passwordHash=password_hash($password,PASSWORD_DEFAULT);
 foreach(array('super_admin','viewer','finance','maintenance') as $role)$ins('gh_admin_users',array('fullname'=>'CIKO '.$role,'username'=>'ciko_'.$role,'email'=>$role.'@example.test','password_hash'=>$passwordHash,'role'=>$role,'is_active'=>1));
 file_put_contents($dir.'/http-fixture-private.json',json_encode(array('schema'=>$schema,'password'=>$password)));chmod($dir.'/http-fixture-private.json',0600);
} elseif(!$result['failed'])$admin->query('DROP DATABASE `'.$schema.'`');
echo json_encode(array('passed'=>$result['passed'],'failed'=>$result['failed'],'error'=>$result['error']??null,'http_fixture'=>$keep?$schema:null));exit($result['failed']?1:0);
