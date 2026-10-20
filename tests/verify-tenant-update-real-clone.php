<?php
/** Offline local clone of authorized real data: never a browser/E2E fixture. */
if(PHP_SAPI!=='cli')exit(2);
require dirname(__DIR__).'/scripts/import-gulhouse-tenants-update.php';
$root=dirname(__DIR__);$private=$root.'/.deploy/tenant-update-20261015';
$schema='gulhouse_test_tenant_import_'.date('Ymd_His').'_'.bin2hex(random_bytes(4));
$env=parse_ini_file($root.'/landing/.env');$host=$env['HOSTNAME']??'127.0.0.1';
if(!in_array($host,array('127.0.0.1','localhost','::1'),true)||!preg_match('/^gulhouse_test_tenant_import_[a-zA-Z0-9_]+$/D',$schema))throw new RuntimeException('Strict local clone guard failed.');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli($host,$env['USERNAME']??'root',$env['PASSWORD']??'','gulhouse_db',(int)($env['DB_PORT']??3306));$db->set_charset('utf8mb4');
$processEnv=getenv();$processEnv['MYSQL_PWD']=$env['PASSWORD']??'';
function clone_process(array $command,$stdin=null,$stdout=null){global $root,$processEnv;$spec=array(0=>$stdin?array('file',$stdin,'rb'):array('pipe','r'),1=>$stdout?array('file',$stdout,'wb'):array('pipe','w'),2=>array('pipe','w'));$p=proc_open($command,$spec,$pipes,$root,$processEnv);if(!is_resource($p))throw new RuntimeException('CLI unavailable');if(!$stdin)fclose($pipes[0]);$out=$stdout?'':stream_get_contents($pipes[1]);if(!$stdout)fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);$code=proc_close($p);if($code!==0)throw new RuntimeException('Private clone CLI failed; exit '.$code);return json_decode(trim($out),true);}
$checks=0;function check_clone_import($ok,$label){global $checks;if(!$ok)throw new RuntimeException($label);$checks++;echo 'PASS '.$label.PHP_EOL;}
$tables=array('gh_payments','gh_room_bills','gh_contracts','gh_contract_documents','gh_deposit_transactions','gh_booking_requests');
$fingerprint=function()use($db,$tables){$out=array();foreach($tables as $table)$out[$table]=GulhouseTenantUpdate::fingerprint($db->query('SELECT * FROM `'.$table.'` ORDER BY id')->fetch_all(MYSQLI_ASSOC));return $out;};
$sourceProtected=$fingerprint();
$existing=(int)$db->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".$db->real_escape_string($schema)."'")->fetch_row()[0];
if($existing)throw new RuntimeException('Never overwrite existing clone.');
$dump=$private.'/offline-real-clone-'.$schema.'.sql';
$baseArgs=array('--host='.$host,'--port='.(int)($env['DB_PORT']??3306),'--user='.($env['USERNAME']??'root'),'--default-character-set=utf8mb4');
clone_process(array_merge(array('D:/XAMPP/mysql/bin/mysqldump.exe'),$baseArgs,array('--single-transaction','--routines','--triggers','--events','--hex-blob','gulhouse_db')),null,$dump);
check_clone_import(is_file($dump)&&filesize($dump)>1000,'consistent complete source backup captured privately');
$db->query('CREATE DATABASE `'.$schema.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
clone_process(array_merge(array('D:/XAMPP/mysql/bin/mysql.exe'),$baseArgs,array($schema)),$dump);
$db->select_db($schema);check_clone_import($fingerprint()===$sourceProtected,'offline clone preserves all protected source records');
$report=$private.'/clone-plan-'.$schema.'.json';$input=$private.'/normalized-source.json';
$command=array(PHP_BINARY,$root.'/scripts/import-gulhouse-tenants-update.php','--database='.$schema,'--input='.$input,'--output='.$report);
$dry=clone_process($command);check_clone_import($dry['summary']['source_rows']===64&&$dry['summary']['operations']>0,'full 64-row workbook dry-run evaluated on real-data clone');
$applied=clone_process(array_merge($command,array('--apply','--approve-plan='.$dry['plan_sha256'])));check_clone_import($applied['mode']==='applied','reviewed full plan applies to offline clone');
check_clone_import($fingerprint()===$sourceProtected,'all payment invoice contract document deposit and booking fingerprints unchanged');
$again=clone_process($command);check_clone_import($again['summary']['operations']===0,'full workbook rerun has zero operations');
$counts=array();foreach(array('gh_rooms','gh_tenants','gh_tenant_stays','gh_tenant_emergency_contacts','gh_payments','gh_room_bills','gh_contracts','gh_booking_requests') as $table)$counts[$table]=(int)$db->query('SELECT COUNT(*) FROM `'.$table.'`')->fetch_row()[0];
$newTerms=(int)$db->query("SELECT COUNT(*) FROM gh_tenant_stays WHERE source_period='Tenant update 2026-10-15' AND (monthly_price IS NOT NULL OR deposit IS NOT NULL OR check_out_date IS NOT NULL)")->fetch_row()[0];check_clone_import($newTerms===0,'all new stays retain absent money and checkout as NULL');
$collisionKey=$db->query('SELECT identity_number FROM gh_tenants WHERE id=6')->fetch_row()[0];
$collision=(int)$db->query("SELECT COUNT(*) FROM gh_tenants WHERE identity_number='".$db->real_escape_string($collisionKey)."'")->fetch_row()[0];check_clone_import($collision===1,'collision source rows never duplicate identity');
$db->select_db('gulhouse_db');check_clone_import($fingerprint()===$sourceProtected,'source real database remains untouched by clone trial');
$result=array('kind'=>'OFFLINE_AUTHORIZED_REAL_DATA_CLONE_NOT_E2E','database'=>$schema,'passed'=>$checks,'summary'=>$applied['summary'],'rerun_operations'=>$again['summary']['operations'],'protected_sha256'=>$sourceProtected,'counts'=>$counts,'backup_sha256'=>hash_file('sha256',$dump),'completed_at'=>date('c'));
file_put_contents($private.'/offline-clone-result-'.$schema.'.json',json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),LOCK_EX);
echo json_encode(array('database'=>$schema,'passed'=>$checks,'summary'=>$applied['summary'],'rerun_operations'=>$again['summary']['operations'],'counts'=>$counts)).PHP_EOL;
