<?php
// Explicit local-only additive migration; backup before DDL/data backfill.
require __DIR__.'/booking-retention-bootstrap.php';
list($unused,$db)=booking_retention_cli('gulhouse_db');
if($db->database!=='gulhouse_db' || !in_array($db->hostname,array('localhost','127.0.0.1'),true))throw new RuntimeException('Local guard');
$dir=$root.'/.e2e/property-city-20261021';
if(!is_dir($dir))mkdir($dir,0700,true);
$rows=$db->get('gh_properties')->result_array();
if(in_array('--apply',$argv,true)) {
 $backup=$dir.'/properties-before-'.date('Ymd-His').'.json';
 if(file_put_contents($backup,json_encode($rows,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))===false)throw new RuntimeException('Backup failed');
 if(json_decode(file_get_contents($backup),true)!==$rows)throw new RuntimeException('Backup readback failed');
 $sql=file_get_contents($root.'/database/patch_gulhouse_property_city_20261021.sql');
 $sql=preg_replace('/^--.*$/m','',$sql);
 foreach(explode(';',$sql) as $statement)if(trim($statement)!==''&&!$db->query(trim($statement)))throw new RuntimeException('Migration failed');
}
echo json_encode($db->select('id,code,city')->get('gh_properties')->result_array(),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
