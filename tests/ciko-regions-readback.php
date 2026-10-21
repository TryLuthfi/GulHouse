<?php
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';list($unused,$db)=booking_retention_cli('gulhouse_db');
$dir=$root.'/.e2e/regions-20261021';$before=json_decode(file_get_contents($dir.'/ciko-before.json'),true);$checks=array();
$ck=function($n,$ok)use(&$checks){$checks[]=array('name'=>$n,'status'=>$ok?'PASS':'FAIL');};
$hash=function($rows){$e=array_map('json_encode',$rows);sort($e,SORT_STRING);return hash('sha256',json_encode($e));};
foreach($before['tables'] as $t=>$old){$rows=$db->get($t)->result_array();$keys=array_fill_keys($old['columns'],true);$projection=array_map(function($r)use($keys){return array_intersect_key($r,$keys);},$rows);$ck('All original records and field values preserved: '.$t,count($rows)===$old['rows']&&$hash($projection)===$old['sha256']);}
foreach(array('gh_region_provinces'=>'md_provinsi_indonesia','gh_region_regencies'=>'md_kokab_indonesia','gh_region_districts'=>'md_kec_indonesia') as $dest=>$src){$cols=$dest==='gh_region_provinces'?'id,name':($dest==='gh_region_regencies'?'id,province_id,name':'id,regency_id,name');$source=$db->query('SELECT '.$cols.' FROM databasetkm_com.'.$src)->result_array();$copy=$db->select($cols)->get($dest)->result_array();$ck('Reference data copied exactly: '.$dest,$hash($source)===$hash($copy));}
$ck('No dusun reference table imported',!$db->table_exists('gh_region_villages')&&!$db->table_exists('md_dusun_indonesia'));
$ck('Copied regencies have province parent',(int)$db->query('SELECT COUNT(*) n FROM gh_region_regencies r LEFT JOIN gh_region_provinces p ON p.id=r.province_id WHERE p.id IS NULL')->row()->n===0);
$ck('Copied districts have city parent',(int)$db->query('SELECT COUNT(*) n FROM gh_region_districts d LEFT JOIN gh_region_regencies r ON r.id=d.regency_id WHERE r.id IS NULL')->row()->n===0);
foreach(array('gh_properties','gh_rooms') as $t){foreach(array('region_province_id','region_regency_id','region_district_id') as $col){$ck('Nullable administrative field exists: '.$t.'.'.$col,$db->field_exists($col,$t));$ck('No guessed administrative mapping: '.$t.'.'.$col,$db->where($col.' IS NOT NULL',null,false)->count_all_results($t)===0);}}
$ck('One active APT remains',(int)$db->where('property_type','apartment')->where('is_active',1)->count_all_results('gh_properties')===1);
$ck('Five existing APT units remain',(int)$db->where('property_id',4)->count_all_results('gh_rooms')===5);
$result=array('passed'=>count(array_filter($checks,function($c){return $c['status']==='PASS';})),'failed'=>count(array_filter($checks,function($c){return $c['status']==='FAIL';})),'checks'=>$checks,'database_writes'=>0);file_put_contents($dir.'/ciko-readback-results.json',json_encode($result,JSON_PRETTY_PRINT));echo json_encode($result,JSON_PRETTY_PRINT);exit($result['failed']?1:0);
