<?php
/** Explicit local-only additive migration, complete private backup and legacy row hashes. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
umask(0077);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$root=dirname(__DIR__); $env=array();
foreach (array($root.'/master/.env',$root.'/.env',$root.'/landing/.env') as $p) if(is_file($p)) { $env=parse_ini_file($p); break; }
$schema=getenv('GULHOUSE_MIGRATION_DATABASE') ?: ($env['GULHOUSE_DATABASE']??$env['DATABASE']??'');
$host=getenv('GH_DB_HOSTNAME') ?: ($env['HOSTNAME']??'127.0.0.1');
$productionFlag=NULL;
foreach($argv as $argument)if(strpos($argument,'--production-schema=')===0){if($productionFlag!==NULL)throw new RuntimeException('Specify the production schema flag only once.');$productionFlag=substr($argument,strlen('--production-schema='));}
if($productionFlag!==NULL&&($productionFlag!=='gulhouse_co_id'||$schema!==$productionFlag))throw new RuntimeException('The explicit production schema flag must match the known production target.');
$localSchema=(bool)preg_match('/^(gulhouse_db|gulhouse_test_[a-zA-Z0-9_]+)$/D',$schema);
$productionSchema=$schema==='gulhouse_co_id'&&$productionFlag==='gulhouse_co_id';
if(!in_array($host,array('127.0.0.1','localhost'),TRUE)||(!$localSchema&&!$productionSchema))throw new RuntimeException('Only local GulHouse, isolated tests, or the explicitly named production schema is allowed.');
if(in_array('--check-target',$argv,TRUE)){echo json_encode(array('target_allowed'=>TRUE,'target_kind'=>$productionSchema?'production':'local_or_test','database_connection_attempted'=>FALSE)).PHP_EOL;exit;}

$db=new mysqli($host,getenv('GH_DB_USERNAME')?:($env['USERNAME']??'root'),getenv('GH_DB_PASSWORD')?:($env['PASSWORD']??''),$schema,(int)($env['DB_PORT']??3306)); $db->set_charset('utf8mb4');
function snapshot($db) { $result=array(); foreach($db->query('SHOW TABLES')->fetch_all(MYSQLI_NUM) as $v) { $table=$v[0]; if(!preg_match('/^gh_[a-zA-Z0-9_]+$/D',$table))continue; $ctx=hash_init('sha256'); $count=0; $rows=$db->query('SELECT * FROM `'.$table.'` ORDER BY id'); while($r=$rows->fetch_assoc()) { hash_update($ctx,json_encode($r,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n"); $count++; } $result[$table]=array('count'=>$count,'sha256'=>hash_final($ctx)); } return $result; }
$required=array('gh_lifecycle_facility_catalog','gh_resident_cases','gh_resident_files','gh_facility_inspections','gh_facility_inspection_rows','gh_checkout_requests','gh_refund_payments','gh_resident_events');
$missing=array();foreach($required as $table){$result=$db->query("SHOW TABLES LIKE '".$db->real_escape_string($table)."'");if(!$result->num_rows)$missing[]=$table;}
if($missing){echo json_encode(array('ready'=>FALSE,'missing_prerequisites'=>$missing,'apply_lifecycle_migration_first'=>TRUE)).PHP_EOL;if(in_array('--apply',$argv,TRUE))throw new RuntimeException('Lifecycle prerequisites must be explicitly migrated before mobile tokens.');exit;}
$before=snapshot($db);
if(!in_array('--apply',$argv,TRUE)) { echo json_encode(array('dry_run'=>TRUE,'legacy_tables'=>count($before),'migration'=>'patch_gulhouse_mobile_api_20261019.sql')).PHP_EOL; exit; }
$dir=$root.'/.deploy/backups'; if(!is_dir($dir))mkdir($dir,0700,TRUE); $file=$dir.'/mobile-api-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sql'; $h=fopen($file,'xb'); if(!$h)throw new RuntimeException('Backup unavailable.');
fwrite($h,"SET FOREIGN_KEY_CHECKS=0;\n");
foreach(array_keys($before) as $table) { $ddl=$db->query('SHOW CREATE TABLE `'.$table.'`')->fetch_assoc(); fwrite($h,$ddl['Create Table'].";\n"); $rs=$db->query('SELECT * FROM `'.$table.'`'); while($row=$rs->fetch_assoc()) { $cols=array_map(function($k){return '`'.$k.'`';},array_keys($row)); $vals=array_map(function($v)use($db){return $v===NULL?'NULL':"'".$db->real_escape_string($v)."'";},array_values($row)); fwrite($h,'INSERT INTO `'.$table.'` ('.implode(',',$cols).') VALUES ('.implode(',',$vals).");\n"); } }
fwrite($h,"SET FOREIGN_KEY_CHECKS=1;\n"); fclose($h); if(filesize($file)<100)throw new RuntimeException('Backup verification failed.');
$db->multi_query(file_get_contents($root.'/database/patch_gulhouse_mobile_api_20261019.sql')); do {if($r=$db->store_result())$r->free();}while($db->more_results()&&$db->next_result());
$after=snapshot($db); foreach($before as $table=>$signature)if(($after[$table]??NULL)!==$signature)throw new RuntimeException('Legacy reconciliation failed for '.$table.'. Inspect private backup.');
echo json_encode(array('applied'=>TRUE,'legacy_rows_and_hashes_preserved'=>TRUE,'backup_sha256'=>hash_file('sha256',$file),'new_tables'=>count($after)-count($before))).PHP_EOL;
