<?php
// Creates its own synthetic schema. Never applies the importer to real data.
if(PHP_SAPI!=='cli')exit(2);
require dirname(__DIR__).'/scripts/import-gulhouse-tenants-update.php';
$root=dirname(__DIR__);$schema='gulhouse_test_tenant_update_'.date('Ymd_His').'_'.bin2hex(random_bytes(4));$checks=0;
function check_tenant_import($ok,$label){global $checks;if(!$ok)throw new RuntimeException($label);$checks++;echo 'PASS '.$label.PHP_EOL;}
function run_tenant_import_process(array $command,array $environment=array()){
 global $root;$p=proc_open(array_merge(array(PHP_BINARY),$command),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes,$root,array_merge(getenv(),$environment));
 if(!is_resource($p))throw new RuntimeException('Test process unavailable');fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);return array(proc_close($p),json_decode(trim($out),true),$err);
}
list($code,$setup)=run_tenant_import_process(array('scripts/setup-gulhouse-test.php'),array('GULHOUSE_TEST_DATABASE'=>$schema));
check_tenant_import($code===0&&($setup['database']??'')===$schema,'fresh isolated schema created');
$env=parse_ini_file($root.'/landing/.env');mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli($env['HOSTNAME']??'127.0.0.1',$env['USERNAME']??'root',$env['PASSWORD']??'',$schema,(int)($env['DB_PORT']??3306));$db->set_charset('utf8mb4');
$rooms=$db->query("SELECT r.id,r.room_code,p.code property_code FROM gh_rooms r JOIN gh_properties p ON p.id=r.property_id WHERE r.status='available' ORDER BY r.id LIMIT 2")->fetch_all(MYSQLI_ASSOC);$room=(int)$rooms[0]['id'];
$db->query("INSERT INTO gh_tenants(fullname,identity_number,phone,source_label) VALUES('Tenant Import Old Demo','1234567890123456','081234567890','SYNTHETIC')");$tenant=(int)$db->insert_id;
$db->query("INSERT INTO gh_tenant_stays(tenant_id,room_id,stay_status,check_in_date,monthly_price,deposit,source_period,notes) VALUES($tenant,$room,'active','2026-08-01',1230000,500000,'August 2026','Original historical note')");$stay=(int)$db->insert_id;
$db->query("UPDATE gh_rooms SET status='occupied',is_public=0 WHERE id=$room");
$db->query("INSERT INTO gh_billing_periods(period_key,period_label,period_month,period_year,source_file) VALUES('2026-09','SYNTHETIC SEPTEMBER',9,2026,'SYNTHETIC')");$period=(int)$db->insert_id;
$db->query("INSERT INTO gh_room_bills(period_id,room_id,stay_id,tenant_id,base_price,paid_total,bill_status,source_sheet) VALUES($period,$room,$stay,$tenant,1230000,1230000,'paid','SYNTHETIC')");$bill=(int)$db->insert_id;
$db->query("INSERT INTO gh_payments(room_bill_id,payment_date,amount,payment_type,source_key) VALUES($bill,'2026-09-01',1230000,'rent','SYNTHETIC-IMPORT-HISTORY')");
$history=function()use($db,$bill){return GulhouseTenantUpdate::fingerprint(array($db->query('SELECT * FROM gh_room_bills WHERE id='.$bill)->fetch_assoc(),$db->query('SELECT * FROM gh_payments WHERE room_bill_id='.$bill)->fetch_all(MYSQLI_ASSOC)));};$before=$history();
$dir=$root.'/.deploy/tenant-update-20261015';$inputFile=$dir.'/synthetic-'.$schema.'.json';$report=$dir.'/synthetic-plan-'.$schema.'.json';
$input=array('rows'=>array(array('property_code'=>$rooms[0]['property_code'],'room_code'=>$rooms[0]['room_code'],'stay_status'=>'empty'),array('property_code'=>$rooms[1]['property_code'],'room_code'=>$rooms[1]['room_code'],'fullname'=>'Tenant Import New Demo','identity_number'=>'9876543210987654','phone'=>'081299887766','stay_status'=>'active','check_in_date'=>'2026-10-15','verified_current_occupant'=>true)));
file_put_contents($inputFile,json_encode($input));$command=array('scripts/import-gulhouse-tenants-update.php','--database='.$schema,'--input='.$inputFile,'--output='.$report);
list($code)=run_tenant_import_process(array('scripts/import-gulhouse-tenants-update.php','--input='.$inputFile),array('GULHOUSE_DATABASE'=>$schema));check_tenant_import($code!==0,'environment alone cannot select a database');
$syntheticSource=$dir.'/synthetic-source-'.$schema.'.txt';file_put_contents($syntheticSource,'verified extraction bytes');$input['source_files']=array(array('path'=>$syntheticSource,'sha256'=>hash_file('sha256',$syntheticSource)));file_put_contents($inputFile,json_encode($input));
file_put_contents($syntheticSource,'changed extraction bytes');list($code)=run_tenant_import_process($command);check_tenant_import($code!==0,'source checksum mismatch refuses even dry-run');file_put_contents($syntheticSource,'verified extraction bytes');
list($code,$preview)=run_tenant_import_process($command);check_tenant_import($code===0&&$preview['mode']==='dry_run','dry-run produces reviewed plan');
check_tenant_import($db->query('SELECT stay_status FROM gh_tenant_stays WHERE id='.$stay)->fetch_row()[0]==='active'&&$history()===$before,'dry-run changes no stay or financial history');
list($code)=run_tenant_import_process(array_merge($command,array('--apply','--approve-plan=invalid')));check_tenant_import($code!==0&&$history()===$before,'unapproved plan cannot apply');
$db->query("UPDATE gh_tenants SET occupation='Simulated concurrent edit' WHERE id=$tenant");list($code)=run_tenant_import_process(array_merge($command,array('--apply','--approve-plan='.$preview['plan_sha256'])));check_tenant_import($code!==0&&$db->query('SELECT stay_status FROM gh_tenant_stays WHERE id='.$stay)->fetch_row()[0]==='active','database drift invalidates previously reviewed plan');
list($code,$preview)=run_tenant_import_process($command);check_tenant_import($code===0,'fresh review captures changed synthetic state');
list($code,$applied)=run_tenant_import_process(array_merge($command,array('--apply','--approve-plan='.$preview['plan_sha256'])));check_tenant_import($code===0&&$applied['mode']==='applied','approved synthetic import applies transaction');
$old=$db->query('SELECT * FROM gh_tenant_stays WHERE id='.$stay)->fetch_assoc();check_tenant_import($old['stay_status']==='ended'&&$old['check_out_date']===null&&strpos($old['notes'],'Original historical note')!==false&&strpos($old['notes'],'actual_exit_unknown')!==false,'vacancy retains notes and unknown actual checkout');
$new=$db->query("SELECT s.* FROM gh_tenant_stays s JOIN gh_tenants t ON t.id=s.tenant_id WHERE t.identity_number='9876543210987654'")->fetch_assoc();check_tenant_import($new&&$new['monthly_price']===null&&$new['deposit']===null&&$new['check_out_date']===null,'new source stay leaves absent terms nullable');
check_tenant_import($history()===$before,'historical invoice and payment bytes remain identical');
$backup=$dir.'/'.$applied['backup'];$manifest=json_decode(file_get_contents($backup.'.manifest.json'),true);check_tenant_import(is_file($backup)&&hash_equals($manifest['backup_sha256'],hash_file('sha256',$backup))&&$manifest['database']===$schema,'full backup and manifest verified before apply');
list($code,$repeated)=run_tenant_import_process($command);check_tenant_import($code===0&&$repeated['summary']['operations']===0,'rerun is idempotent without duplicate tenant or stay');
list($code,$job)=run_tenant_import_process(array('scripts/run-gulhouse-billing.php','--period=2075-01','--apply'),array('GULHOUSE_DATABASE'=>$schema,'GULHOUSE_BILLING_ALLOW_REAL'=>'0'));
check_tenant_import($code===0&&$job['ok'],'future generator runs on imported synthetic observations');
$chargeable=(int)$db->query("SELECT COUNT(*) FROM gh_room_bills rb JOIN gh_billing_periods bp ON bp.id=rb.period_id WHERE bp.period_key='2075-01' AND rb.stay_id=$stay AND rb.bill_status IN ('unpaid','partial','paid','overpaid')")->fetch_row()[0];
check_tenant_import($chargeable===0&&$history()===$before,'ended unknown-exit legacy stay creates no future obligation and history stays unchanged');
echo json_encode(array('passed'=>$checks,'database'=>$schema,'complete'=>true)).PHP_EOL;
