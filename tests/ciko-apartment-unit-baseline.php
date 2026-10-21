<?php
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';list($unused,$db)=booking_retention_cli('gulhouse_db');
$out=array('tables'=>array(),'rooms'=>$db->order_by('id')->get('gh_rooms')->result_array(),'properties'=>$db->order_by('id')->get('gh_properties')->result_array());
foreach($db->query('SHOW TABLES')->result_array() as $row){$t=array_values($row)[0];$rows=$db->get($t)->result_array();$r=array_map('json_encode',$rows);sort($r,SORT_STRING);$out['tables'][$t]=array('rows'=>count($rows),'sha256'=>hash('sha256',json_encode($r)));}
$dir=$root.'/.e2e/apartment-unit-location-20261021';if(!is_dir($dir))mkdir($dir,0700,true);$file=$dir.'/ciko-before.json';if(is_file($file))throw new RuntimeException('Baseline already saved');file_put_contents($file,json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo json_encode(array('tables'=>count($out['tables']),'rooms'=>count($out['rooms']),'properties'=>count($out['properties']),'real_business_writes'=>0));
