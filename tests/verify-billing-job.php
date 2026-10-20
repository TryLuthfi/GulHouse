<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
date_default_timezone_set('Asia/Jakarta');
$root = dirname(__DIR__);
$schema = getenv('GULHOUSE_DATABASE');
if (! is_string($schema) || ! preg_match('/^gulhouse_test_[A-Za-z0-9_]+$/D', $schema)) {
    fwrite(STDERR, "A synthetic test schema is required.\n"); exit(2);
}
$settings = array();
foreach (array($root.'/master/.env', $root.'/.env', $root.'/landing/.env') as $path) {
    if (is_file($path)) { $settings = parse_ini_file($path); break; }
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli(getenv('GH_DB_HOSTNAME') ?: ($settings['HOSTNAME'] ?? '127.0.0.1'),
    getenv('GH_DB_USERNAME') ?: ($settings['USERNAME'] ?? 'root'),
    getenv('GH_DB_PASSWORD') !== FALSE ? getenv('GH_DB_PASSWORD') : ($settings['PASSWORD'] ?? ''),
    $schema, (int) (getenv('GH_DB_DB_PORT') ?: ($settings['DB_PORT'] ?? 3306)));
$db->set_charset('utf8mb4');
$proofFixtureFiles = array();
$jobTestExit = 0;
$checks = 0;
function check_job($condition, $label) {
    global $checks;
    if (! $condition) { throw new RuntimeException('FAIL ' . $label); }
    $checks++; echo 'PASS ' . $label . PHP_EOL;
}
function run_job($arguments, $overrides = array()) {
    global $root;
    $environment = array_merge(getenv(), $overrides);
    $pipes = array();
    $process = proc_open(array_merge(array(PHP_BINARY, $root.'/scripts/run-gulhouse-billing.php'), $arguments),
        array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')), $pipes, $root, $environment);
    if (! is_resource($process)) { throw new RuntimeException('Unable to start local CLI billing process.'); }
    fclose($pipes[0]); $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $error = stream_get_contents($pipes[2]); fclose($pipes[2]); $code = proc_close($process);
    return array($code, json_decode(trim($output), TRUE), $error);
}
function bill_counts() {
    global $db;
    return array((int)$db->query('SELECT COUNT(*) n FROM gh_billing_periods')->fetch_assoc()['n'],
        (int)$db->query('SELECT COUNT(*) n FROM gh_room_bills')->fetch_assoc()['n']);
}
try {
    $db->multi_query(file_get_contents($root.'/database/patch_gulhouse_payment_proofs_20261015.sql'));
    do { if ($result=$db->store_result()) $result->free(); } while ($db->more_results() && $db->next_result());
    $proofDirectory=$root.'/master/application/private_payment_proofs';
    if (! is_dir($proofDirectory)) mkdir($proofDirectory,0775,TRUE);
    $proofName='proof_'.substr(hash('sha256',$schema),0,8).bin2hex(random_bytes(12)).'.pdf';
    $proofFile=$proofDirectory.'/'.$proofName;
    $journal=$proofDirectory.'/cleanup-'.hash('sha256',$schema).'.jsonl';
    if (is_file($journal)) throw new RuntimeException('Use a fresh billing schema; existing proof journal must be preserved.');
    $proofStream=fopen($proofFile,'xb'); fwrite($proofStream,"%PDF-1.4\nCLI SYNTHETIC orphan cleanup fixture\n"); fclose($proofStream); $proofFixtureFiles[]=$proofFile;
    $journalStream=fopen($journal,'xb'); fwrite($journalStream,json_encode(array('database'=>$schema,'stored_filename'=>$proofName,'state'=>'pending','id'=>0,'payment_id'=>0))."\n"); fclose($journalStream); $proofFixtureFiles[]=$journal;
    $journalHash=hash_file('sha256',$journal);
    list($code)=run_job(array('--period=2088-02','--apply'),array('GULHOUSE_DATABASE'=>'gulhouse_db','GULHOUSE_BILLING_ALLOW_REAL'=>'0'));
    check_job($code===2,'real database apply denied without explicit opt-in before framework/database access');
    list($code)=run_job(array('--period=2088-13'));
    check_job($code===2,'invalid month denied');
    $before=bill_counts();
    list($code,$preview)=run_job(array('--period=2088-02'));
    check_job($code===0 && $preview && $preview['mode']==='preview','CLI preview succeeds');
    check_job(bill_counts()===$before,'preview changes no periods or invoices');
    check_job(isset($preview['proof_cleanup']) && $preview['proof_cleanup']['dry_run']===TRUE,'preview cleanup is explicitly read-only');
    check_job(is_file($proofFile) && hash_file('sha256',$journal)===$journalHash && $preview['proof_cleanup']['pending']===1,'preview retains exact queued orphan and leaves journal unchanged');
    list($code,$current)=run_job(array());
    check_job($code===0 && $current['period']===date('Y-m') && $current['timezone']==='Asia/Jakarta','default job uses current Jakarta month');
    $room=$db->query("SELECT id FROM gh_rooms WHERE status='available' AND monthly_price>0 ORDER BY id LIMIT 1")->fetch_assoc();
    check_job((bool)$room,'fixture has a rentable room'); $roomId=(int)$room['id'];
    $db->query("INSERT INTO gh_tenants(fullname,phone,identity_number,source_label) VALUES('CLI Billing Synthetic Demo','089999000001','0000000000009999','CLI SYNTHETIC')");
    $tenantId=(int)$db->insert_id;
    $db->query("INSERT INTO gh_tenant_stays(tenant_id,room_id,stay_status,check_in_date,monthly_price,deposit,source_period) VALUES($tenantId,$roomId,'active','2088-02-01',1200000,0,'CLI SYNTHETIC')");
    $stayId=(int)$db->insert_id;
    $number='CLI-JOB-'.bin2hex(random_bytes(5));
    $db->query("INSERT INTO gh_contracts(contract_no,tenant_id,stay_id,room_id,start_date,end_date,monthly_price,deposit_amount,status,signed_at) VALUES('$number',$tenantId,$stayId,$roomId,'2088-02-01','2088-03-31',1200000,0,'active','2088-02-01')");
    $before=bill_counts();
    list($code,$first)=run_job(array('--period=2088-02','--apply'),array('GULHOUSE_BILLING_DUE_DAY'=>'31'));
    check_job($code===0 && $first['ok'] && $first['generated_or_updated_rows']>0 && $first['new_bill_rows_since_start']>0,'monthly CLI reports generated work and new period rows separately');
    check_job(isset($first['proof_cleanup']) && $first['proof_cleanup']['dry_run']===FALSE,'cleanup apply runs only after successful billing commit');
    clearstatcache(TRUE, $proofFile); // The separate CLI process removed the file after our preview cached its existence.
    check_job(! is_file($proofFile) && $first['proof_cleanup']['remaining_pending']===0 && $first['proof_cleanup']['cleanup_pending']===FALSE,'successful billing reconciles only its own queued orphan with zero remaining');
    $firstCounts=bill_counts();
    $bill=$db->query("SELECT rb.*,bp.period_key FROM gh_room_bills rb JOIN gh_billing_periods bp ON bp.id=rb.period_id WHERE rb.stay_id=$stayId AND bp.period_key='2088-02'")->fetch_assoc();
    check_job($bill && $bill['period_key']==='2088-02' && (int)$bill['base_price']===1200000,'correct month uses contract rent snapshot');
    check_job($bill['due_date']==='2088-02-29','due day clamps to February leap-year end');
    check_job((int)$db->query("SELECT COUNT(*) n FROM gh_room_bills rb JOIN gh_billing_periods bp ON bp.id=rb.period_id WHERE bp.period_key='2088-02' AND rb.base_price>0 AND rb.bill_status='unpaid'")->fetch_assoc()['n']===1,'vacant catalogue rooms create no rent receivables');
    list($code,$again)=run_job(array('--period=2088-02','--apply'),array('GULHOUSE_BILLING_DUE_DAY'=>'31'));
    check_job($code===0 && $again['generated_or_updated_rows']===0 && $again['new_bill_rows_since_start']===0 && bill_counts()===$firstCounts,'second monthly run is idempotent');
    list($code,$next)=run_job(array('--period=2088-03','--apply'));
    check_job($code===0 && $next['period']==='2088-03','following month generates independently');
    check_job((int)$db->query("SELECT COUNT(*) n FROM gh_room_bills rb JOIN gh_billing_periods bp ON bp.id=rb.period_id WHERE rb.stay_id=$stayId AND bp.period_key IN ('2088-02','2088-03')")->fetch_assoc()['n']===2,'one stay invoice per month across repeated runs');
    echo 'Billing CLI regression passed: '.$checks.' assertions. Synthetic fixture retained only.'.PHP_EOL;
} catch (Throwable $error) { fwrite(STDERR,$error->getMessage().PHP_EOL); $jobTestExit = 1; }
finally { foreach($proofFixtureFiles as $ownedFile) { clearstatcache(TRUE, $ownedFile); if(is_file($ownedFile)) unlink($ownedFile); } }
exit($jobTestExit);
