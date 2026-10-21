<?php
// Local-only copy of reference geography. Never updates existing business rows.
require __DIR__.'/booking-retention-bootstrap.php';
$schema='gulhouse_db';foreach($argv as $arg)if(strpos($arg,'--schema=')===0)$schema=substr($arg,9);
if($schema!=='gulhouse_db'&&!preg_match('/^gulhouse_test_[a-zA-Z0-9_]+$/D',$schema))throw new RuntimeException('Schema lokal wajib.');
list($unused,$db)=booking_retention_cli($schema);
if(!in_array($db->hostname,array('localhost','127.0.0.1'),true))throw new RuntimeException('Host lokal wajib.');
$hash=function($rows){$encoded=array_map('json_encode',$rows);sort($encoded,SORT_STRING);return hash('sha256',json_encode($encoded));};
$before=array();foreach($db->query('SHOW TABLES')->result_array() as $item){$table=array_values($item)[0];if(!preg_match('/^gh_[a-zA-Z0-9_]+$/D',$table))throw new RuntimeException('Tabel tidak dikenal.');$before[$table]=$db->get($table)->result_array();}
$mapping=array('provinces'=>array('md_provinsi_indonesia',array('id','name')),'regencies'=>array('md_kokab_indonesia',array('id','province_id','name')),'districts'=>array('md_kec_indonesia',array('id','regency_id','name')));
$source=array();foreach($mapping as $level=>$def){$source[$level]=$db->query('SELECT '.implode(',',$def[1]).' FROM databasetkm_com.'.$def[0].' ORDER BY id')->result_array();if(!$source[$level])throw new RuntimeException('Master sumber kosong.');}
$provinces=array_column($source['provinces'],null,'id');$regencies=array_column($source['regencies'],null,'id');
foreach($source['regencies'] as $r)if(!isset($provinces[$r['province_id']]))throw new RuntimeException('Induk kota sumber tidak valid.');
foreach($source['districts'] as $r)if(!isset($regencies[$r['regency_id']]))throw new RuntimeException('Induk kecamatan sumber tidak valid.');
foreach($source as $level=>$rows)if($db->table_exists('gh_region_'.$level)&&$db->count_all('gh_region_'.$level)>0&&$hash($rows)!==$hash($db->get('gh_region_'.$level)->result_array()))throw new RuntimeException('Master tujuan berbeda, butuh peninjauan sebelum pembaruan.');
if(!in_array('--apply',$argv,true)){echo json_encode(array('mode'=>'plan','source_counts'=>array_map('count',$source),'business_writes'=>0));exit;}
$audit=$root.'/.e2e/regions-20261021/'.date('Ymd-His').'-'.bin2hex(random_bytes(3));mkdir($audit,0700,true);
$backup=json_encode($before,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
if(file_put_contents($audit.'/database-before.json',$backup)!==strlen($backup)||hash_file('sha256',$audit.'/database-before.json')!==hash('sha256',$backup))throw new RuntimeException('Backup gagal.');
$env=parse_ini_file($root.'/master/.env');$ini=$audit.'/backup-client.cnf';
$quote=function($v){return '"'.str_replace(array('\\','"',"\n","\r"),array('\\\\','\\"','\\n','\\r'),(string)$v).'"';};
file_put_contents($ini,"[client]\nhost=".$quote($env['HOSTNAME']??'127.0.0.1')."\nuser=".$quote($env['USERNAME']??'root')."\npassword=".$quote($env['PASSWORD']??'')."\nport=".(int)($env['DB_PORT']??3306)."\n");chmod($ini,0600);
try {
 $binary=PHP_OS_FAMILY==='Windows'?'D:/XAMPP/mysql/bin/mysqldump.exe':'/usr/bin/mysqldump';
 $proc=proc_open(array($binary,'--defaults-extra-file='.$ini,'--single-transaction','--routines','--triggers','--hex-blob','--result-file='.$audit.'/database-before.sql',$schema),array(0=>array('pipe','r'),1=>array('file',$audit.'/dump-output.log','w'),2=>array('file',$audit.'/dump-errors.log','w')),$pipes);
 if(!is_resource($proc))throw new RuntimeException('Backup SQL tidak dapat dijalankan.');fclose($pipes[0]);
 if(proc_close($proc)!==0||!is_file($audit.'/database-before.sql')||filesize($audit.'/database-before.sql')<1000)throw new RuntimeException('Backup SQL gagal; tidak ada perubahan.');
}finally{if(is_file($ini))unlink($ini);}


$sql=preg_replace('/^--.*$/m','',file_get_contents($root.'/database/patch_gulhouse_regions_20261021.sql'));
foreach(explode(';',$sql) as $statement)if(trim($statement)!==''&&!$db->query($statement))throw new RuntimeException('DDL wilayah gagal; backup tersedia.');
$db->trans_begin();
try {
 foreach($source as $level=>$rows){$table='gh_region_'.$level;if(!$db->count_all($table))foreach(array_chunk($rows,500) as $batch)if(!$db->insert_batch($table,$batch))throw new RuntimeException('Salinan wilayah gagal.');if($hash($rows)!==$hash($db->get($table)->result_array()))throw new RuntimeException('Verifikasi master wilayah gagal.');}
 $parity=array();foreach($before as $table=>$rows){
  $after=$db->get($table)->result_array();
  if(in_array($table,array('gh_rooms','gh_properties'),true))foreach($after as &$row){foreach(array('region_province_id','region_regency_id','region_district_id') as $f)if(!array_key_exists($f,$rows[0]??array())){if($row[$f]!==null)throw new RuntimeException('Lokasi lama berubah.');unset($row[$f]);}}unset($row);
  if($hash($rows)!==$hash($after))throw new RuntimeException('Data lama berubah: '.$table);
  $parity[$table]=array('rows'=>count($rows),'sha256'=>$hash($rows));
 }
 if(!$db->trans_status())throw new RuntimeException('Transaksi gagal.');$db->trans_commit();
 $report=array('committed'=>true,'schema'=>$schema,'source_counts'=>array_map('count',$source),'preserved_tables'=>$parity,'business_writes'=>0,'backup_sql_sha256'=>hash_file('sha256',$audit.'/database-before.sql'));
 file_put_contents($audit.'/result.json',json_encode($report,JSON_PRETTY_PRINT));file_put_contents($root.'/.e2e/regions-20261021/active.txt',$audit);
 echo json_encode(array('committed'=>true,'source_counts'=>$report['source_counts'],'preserved_tables'=>count($parity),'business_writes'=>0,'backup'=>$audit));
}catch(Throwable $e){$db->trans_rollback();throw $e;}
