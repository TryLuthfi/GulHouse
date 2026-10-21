<?php
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';list($unused,$db)=booking_retention_cli('gulhouse_db');
$dir=$root.'/.e2e/master-pagination-20261021';$before=json_decode(file_get_contents($dir.'/ciko-before.json'),true);$checks=array();
$ck=function($n,$ok)use(&$checks){$checks[]=array('name'=>$n,'status'=>$ok?'PASS':'FAIL');};
$actual=$db->query('SHOW TABLES')->result_array();$ck('Database table count unchanged',count($actual)===count($before['tables']));
foreach($before['tables'] as $table=>$old){$rows=$db->get($table)->result_array();$enc=array_map('json_encode',$rows);sort($enc,SORT_STRING);$ck('Every original row and field unchanged: '.$table,count($rows)===$old['rows']&&hash('sha256',json_encode($enc))===$old['sha256']);}
$result=array('passed'=>count(array_filter($checks,function($x){return $x['status']==='PASS';})),'failed'=>count(array_filter($checks,function($x){return $x['status']==='FAIL';})),'checks'=>$checks,'database_writes'=>0);file_put_contents($dir.'/ciko-readback-results.json',json_encode($result,JSON_PRETTY_PRINT));echo json_encode(array('passed'=>$result['passed'],'failed'=>$result['failed'],'database_writes'=>0));exit($result['failed']?1:0);
