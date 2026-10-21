<?php
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';list($unused,$db)=booking_retention_cli('gulhouse_db');
$dir=$root.'/.e2e/master-pagination-20261021';if(!is_dir($dir))mkdir($dir,0700,true);if(is_file($dir.'/ciko-before.json'))throw new RuntimeException('Baseline already exists');
$tables=array();foreach($db->query('SHOW TABLES')->result_array() as $r){$t=array_values($r)[0];$rows=$db->get($t)->result_array();$enc=array_map('json_encode',$rows);sort($enc,SORT_STRING);$tables[$t]=array('rows'=>count($rows),'sha256'=>hash('sha256',json_encode($enc)));}
file_put_contents($dir.'/ciko-before.json',json_encode(array('tables'=>$tables,'captured_at'=>date(DATE_ATOM),'database_writes'=>0),JSON_PRETTY_PRINT));echo json_encode(array('tables'=>count($tables),'rooms'=>$tables['gh_rooms']['rows'],'database_writes'=>0));
