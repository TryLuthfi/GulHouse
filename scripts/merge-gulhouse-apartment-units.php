<?php
// Local only. A complete backup and all-table parity gate precede commit.
require __DIR__.'/booking-retention-bootstrap.php';require APPPATH.'libraries/Apartment_units.php';
$schema='gulhouse_db';foreach($argv as $arg)if(strpos($arg,'--schema=')===0)$schema=substr($arg,9);
if($schema!=='gulhouse_db'&&!preg_match('/^gulhouse_test_[a-zA-Z0-9_]+$/D',$schema))throw new RuntimeException('Hanya database lokal atau schema uji yang diizinkan.');
list($unused,$db)=booking_retention_cli($schema);
if(!in_array($db->hostname,array('localhost','127.0.0.1'),true))throw new RuntimeException('Host lokal wajib.');
$snapshot=function()use($db){$out=array();foreach($db->query('SHOW TABLES')->result_array() as $item){$table=array_values($item)[0];if(!preg_match('/^gh_[a-zA-Z0-9_]+$/D',$table))throw new RuntimeException('Tabel tidak dikenal.');$out[$table]=$db->get($table)->result_array();}return $out;};
$hash=function($rows){$encoded=array_map('json_encode',$rows);sort($encoded,SORT_STRING);return hash('sha256',json_encode($encoded));};
$pointer=$root.'/.e2e/apartment-split-20261021/active.txt';
if(!is_file($pointer))throw new RuntimeException('Receipt pemisahan sebelumnya diperlukan.');
$prior=realpath(trim(file_get_contents($pointer)));$receiptRoot=realpath($root.'/.e2e/apartment-split-20261021');
if(!$prior||strpos($prior,$receiptRoot.DIRECTORY_SEPARATOR)!==0)throw new RuntimeException('Path receipt tidak valid.');
$receipt=json_decode(file_get_contents($prior.'/result.json'),true);$owned=array_map('intval',$receipt['properties']??array());
if(count($owned)!==4)throw new RuntimeException('Manifest milik migrasi tidak lengkap.');
$before=$snapshot();
if(!in_array('--apply',$argv,true)){echo json_encode(array('schema'=>$schema,'owned_properties'=>$owned,'mode'=>'plan','database_writes'=>0),JSON_PRETTY_PRINT);exit;}
$audit=$root.'/.e2e/apartment-unit-merge-20261021/'.date('Ymd-His').'-'.bin2hex(random_bytes(3));mkdir($audit,0700,true);
$backup=json_encode($before,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
if(file_put_contents($audit.'/database-before.json',$backup)!==strlen($backup)||hash_file('sha256',$audit.'/database-before.json')!==hash('sha256',$backup))throw new RuntimeException('Backup gagal.');
$env=parse_ini_file($root.'/master/.env');$ini=$audit.'/backup-client.cnf';
$quote=function($v){return '"'.str_replace(array('\\','"',"\n","\r"),array('\\\\','\\"','\\n','\\r'),(string)$v).'"';};
file_put_contents($ini,"[client]\nhost=".$quote($env['HOSTNAME']??'127.0.0.1')."\nuser=".$quote($env['USERNAME']??'root')."\npassword=".$quote($env['PASSWORD']??'')."\nport=".(int)($env['DB_PORT']??3306)."\n");chmod($ini,0600);
try {
 $binary=PHP_OS_FAMILY==='Windows'?'D:/XAMPP/mysql/bin/mysqldump.exe':'/usr/bin/mysqldump';
 $proc=proc_open(array($binary,'--defaults-extra-file='.$ini,'--single-transaction','--routines','--triggers','--hex-blob','--result-file='.$audit.'/database-before.sql',$schema),array(0=>array('pipe','r'),1=>array('file',$audit.'/dump-output.log','w'),2=>array('file',$audit.'/dump-errors.log','w')),$pipes);
 if(!is_resource($proc))throw new RuntimeException('Backup SQL tidak dapat dijalankan.');fclose($pipes[0]);
 if(proc_close($proc)!==0||!is_file($audit.'/database-before.sql')||filesize($audit.'/database-before.sql')<1000)throw new RuntimeException('Backup SQL gagal; tidak ada pemindahan.');
}finally{if(is_file($ini))unlink($ini);}

$sql=preg_replace('/^--.*$/m','',file_get_contents($root.'/database/patch_gulhouse_unit_locations_20261021.sql'));
foreach(explode(';',$sql) as $statement)if(trim($statement)!==''&&!$db->query($statement))throw new RuntimeException('DDL lokasi unit gagal.');
try {
 $result=Gulhouse_apartment_units::merge($db,$owned);$after=$snapshot();$unchanged=array();
 foreach($before as $table=>$rows){if(in_array($table,array('gh_rooms','gh_properties'),true))continue;if($hash($rows)!==$hash($after[$table]))throw new RuntimeException('Data relasi berubah: '.$table);$unchanged[$table]=array('rows'=>count($rows),'sha256'=>$hash($rows));}
 $oldRooms=array_column($before['gh_rooms'],null,'id');$newRooms=array_column($after['gh_rooms'],null,'id');
 if(array_keys($oldRooms)!==array_keys($newRooms))throw new RuntimeException('Identitas kamar berubah.');
 foreach($oldRooms as $id=>$room){$current=$newRooms[$id];$expected=$room;if(in_array((int)$id,$result['moved_room_ids'],true))$expected['property_id']=(string)$result['apt_id'];unset($current['unit_city'],$current['unit_address']);unset($expected['unit_city'],$expected['unit_address']);if($current!==$expected)throw new RuntimeException('Field bisnis kamar berubah.');$location=$result['locations'][(int)$id]??array('unit_city'=>$room['unit_city']??null,'unit_address'=>$room['unit_address']??null);if($newRooms[$id]['unit_city']!==$location['unit_city']||$newRooms[$id]['unit_address']!==$location['unit_address'])throw new RuntimeException('Lokasi unit berubah di luar manifest.');}
 $oldProperties=array_column($before['gh_properties'],null,'id');$newProperties=array_column($after['gh_properties'],null,'id');
 foreach($oldProperties as $id=>$old){if(in_array((int)$id,$result['removed_owned_properties'],true)){if(isset($newProperties[$id]))throw new RuntimeException('Properti migrasi belum dilepas.');continue;}$expected=$old;if((int)$id===$result['apt_id'])$expected['is_active']='1';if(!isset($newProperties[$id])||$newProperties[$id]!==$expected)throw new RuntimeException('Properti lain berubah.');}
 if(!$db->trans_status())throw new RuntimeException('Transaksi tidak sehat.');$db->trans_commit();
 $report=array_merge($result,array('committed'=>true,'schema'=>$schema,'unchanged_tables'=>$unchanged,'room_ids_preserved'=>true,'sql_backup_sha256'=>hash_file('sha256',$audit.'/database-before.sql'),'audit'=>$audit));file_put_contents($audit.'/result.json',json_encode($report,JSON_PRETTY_PRINT));file_put_contents($root.'/.e2e/apartment-unit-merge-20261021/active.txt',$audit);
 echo json_encode(array('committed'=>true,'moved_room_ids'=>$result['moved_room_ids'],'removed_owned_properties'=>$result['removed_owned_properties'],'unchanged_tables'=>count($unchanged),'backup'=>$audit));
}catch(Throwable $e){$db->trans_rollback();throw $e;}
