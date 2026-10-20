<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$root = dirname(__DIR__);
$env = array();
foreach (array($root . '/master/.env', $root . '/.env', $root . '/landing/.env') as $path) {
    if (is_file($path)) { $env = parse_ini_file($path); break; }
}
function setting($name, $default) {
    $value = getenv($name);
    return $value !== false ? $value : $default;
}
$source = $env['GULHOUSE_DATABASE'] ?? ($env['DATABASE'] ?? 'gulhouse_db');
$target = setting('GULHOUSE_TEST_DATABASE', 'gulhouse_test_' . date('Ymd_His'));
if (!preg_match('/^gulhouse_test_[a-zA-Z0-9_]+$/D', $target) || $target === $source) {
    throw new RuntimeException('Test database must be a separate gulhouse_test_* schema.');
}
$db = new mysqli(setting('GH_DB_HOSTNAME', $env['HOSTNAME'] ?? '127.0.0.1'), setting('GH_DB_USERNAME', $env['USERNAME'] ?? 'root'), setting('GH_DB_PASSWORD', $env['PASSWORD'] ?? ''), $source, (int) setting('GH_DB_DB_PORT', $env['DB_PORT'] ?? 3306));
$db->set_charset('utf8mb4');
if (!preg_match('/^[a-zA-Z0-9_]+$/D', $source)) throw new RuntimeException('Invalid source schema.');
$exists = $db->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');
$exists->bind_param('s', $target); $exists->execute();
if ($exists->get_result()->num_rows) throw new RuntimeException('Refusing to overwrite existing test schema. Choose a fresh name.');
$db->query("CREATE DATABASE `$target` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$tables = $db->query('SHOW TABLES')->fetch_all(MYSQLI_NUM);
$db->select_db($target);
$db->query('SET FOREIGN_KEY_CHECKS=0');
foreach ($tables as $row) {
    $table = $row[0];
    if (preg_match('/^gh_[a-zA-Z0-9_]+$/D', $table)) {
        $definition = $db->query("SHOW CREATE TABLE `$source`.`$table`")->fetch_assoc();
        $db->query($definition['Create Table']);
    }
}
$db->query('SET FOREIGN_KEY_CHECKS=1');
$db->select_db($target);
foreach (array('patch_gulhouse_flow_20261015.sql', 'patch_gulhouse_roles_20261015.sql', 'patch_gulhouse_payment_proofs_20261015.sql', 'patch_gulhouse_lifecycle_20261019.sql', 'patch_gulhouse_mobile_api_20261019.sql') as $patch) {
    $path = $root . '/database/' . $patch;
    if (is_file($path)) {
        $db->multi_query(file_get_contents($path));
        do { if ($result = $db->store_result()) $result->free(); } while ($db->more_results() && $db->next_result());
    }
}
// Public catalogue and generic templates only; no tenants/payments/admins copied.
foreach (array('gh_room_database','gh_properties','gh_room_types','gh_rooms','gh_inventory_items','gh_document_templates','gh_notification_templates') as $table) {
    $db->query("INSERT INTO `$target`.`$table` SELECT * FROM `$source`.`$table`");
}
$db->query("UPDATE gh_rooms SET status='available',is_public=1 WHERE status NOT IN ('mess','inactive') AND monthly_price > 0");
$password = setting('GULHOUSE_E2E_PASS', 'LocalDemo2026!');
$username = setting('GULHOUSE_E2E_USER', 'codex_trial');
$hash = password_hash($password, PASSWORD_DEFAULT);
$insert = $db->prepare("INSERT INTO gh_admin_users(fullname,username,email,password_hash,role,is_active) VALUES('Penguji Demo',?,'demo@example.test',?,'super_admin',1)");
$insert->bind_param('ss', $username, $hash); $insert->execute();
$insert = $db->prepare("INSERT INTO gh_admin_users(fullname,username,email,password_hash,role,is_active) VALUES('Viewer Demo','demo_viewer','viewer@example.test',?,'viewer',1)");
$insert->bind_param('s', $hash); $insert->execute();
foreach (array('manager','finance','maintenance') as $role) {
    $user = 'demo_' . $role;
    $email = $role . '@example.test';
    $insert = $db->prepare('INSERT INTO gh_admin_users(fullname,username,email,password_hash,role,is_active) VALUES(?,?,?,?,?,1)');
    $insert->bind_param('sssss', $user, $user, $email, $hash, $role); $insert->execute();
}
$room = $db->query("SELECT id FROM gh_rooms WHERE status='available' AND is_public=1 AND monthly_price>0 ORDER BY id LIMIT 1")->fetch_assoc();
if (!$room) throw new RuntimeException('No rentable room fixture.');
$rooms = $db->query("SELECT id FROM gh_rooms WHERE status='available' AND is_public=1 AND monthly_price>0 ORDER BY id LIMIT 15")->fetch_all(MYSQLI_ASSOC);
if (count($rooms) < 15) throw new RuntimeException('Fifteen rentable rooms are required for isolated browser fixtures.');
if (in_array('--demo', $argv, true)) {
    $db->query("INSERT INTO gh_billing_periods(period_key,period_label,period_month,period_year,source_file) VALUES('2026-11','NOVEMBER 2026 — DEMO',11,2026,'SYNTHETIC DEMO')");
    $periodId = $db->insert_id;
    foreach ($db->query("SELECT * FROM gh_rooms WHERE status='available' AND is_public=1 AND monthly_price>0 ORDER BY id DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC) as $index=>$demoRoom) {
        $number=$index+1; $name='DEMO Penghuni '.$number; $phone='08999100000'.$number; $nik='000000000000100'.$number;
        $tenantInsert=$db->prepare("INSERT INTO gh_tenants(fullname,phone,identity_number,source_label) VALUES(?,?,?,'SYNTHETIC DEMO')");
        $tenantInsert->bind_param('sss',$name,$phone,$nik); $tenantInsert->execute(); $tenantId=$db->insert_id;
        $roomId=(int)$demoRoom['id']; $price=(int)$demoRoom['monthly_price']; $deposit=(int)$demoRoom['deposit'];
        $state=$index===2?'reserved':'active';
        $db->query("INSERT INTO gh_tenant_stays(tenant_id,room_id,stay_status,check_in_date,check_out_date,monthly_price,deposit,source_period,notes) VALUES($tenantId,$roomId,'$state','2026-11-01','2026-11-30',$price,$deposit,'November2026','SYNTHETIC DEMO')"); $stayId=$db->insert_id;
        $contractState=$index===2?'draft':'active';
        $db->query("INSERT INTO gh_contracts(contract_no,tenant_id,stay_id,room_id,start_date,end_date,monthly_price,deposit_amount,status,notes) VALUES('DEMO-CONTRACT-$number',$tenantId,$stayId,$roomId,'2026-11-01','2026-11-30',$price,$deposit,'$contractState','Data simulasi presentasi, bukan perjanjian riil')"); $contractId=$db->insert_id;
        foreach(array('ktp'=>'KTP Demo','contract'=>'Kontrak Demo','statement'=>'Pernyataan Demo','handover'=>'Serah Terima Demo','inventory'=>'Inventaris Demo') as $type=>$title) {
            $documentStatus=$index===2?'needed':'verified';
            $db->query("INSERT INTO gh_contract_documents(contract_id,tenant_id,document_type,document_title,status,notes) VALUES($contractId,$tenantId,'$type','$title','$documentStatus','SYNTHETIC DEMO: verifikasi simulasi')");
        }
        $roomStatus=$index===2?'reserved':'occupied'; $db->query("UPDATE gh_rooms SET status='$roomStatus',is_public=0 WHERE id=$roomId");
        if($index<2) {
            $db->query("INSERT INTO gh_deposit_transactions(tenant_id,stay_id,room_id,transaction_date,transaction_type,amount,method,description) VALUES($tenantId,$stayId,$roomId,'2026-11-01','received',$deposit,'DEMO','SYNTHETIC DEMO')");
            $paid=$index===0?$price:(int)floor($price/2); $billState=$index===0?'paid':'partial';
            $db->query("INSERT INTO gh_room_bills(period_id,room_id,stay_id,tenant_id,tenant_name_snapshot,tenant_phone_snapshot,base_price,deposit_amount,due_date,paid_total,future_due_amount,bill_status,source_sheet,notes) VALUES($periodId,$roomId,$stayId,$tenantId,'$name','$phone',$price,$deposit,'2026-11-01',$paid,".($price-$paid).",'$billState','DEMO','SYNTHETIC DEMO')"); $billId=$db->insert_id;
            $db->query("INSERT INTO gh_payments(room_bill_id,payment_date,amount,payment_type,method,reference_no,source_key,notes) VALUES($billId,'2026-11-01',$paid,'rent','Transfer DEMO','DEMO-RECEIPT-$number','DEMO-PAY-$number','SYNTHETIC DEMO')");
        }
    }
    $db->query("INSERT INTO gh_maintenance_tickets(ticket_no,issue_title,pic_name,status,reported_at,deadline_at,notes) VALUES('DEMO-MAINT-1','DEMO servis AC area umum','Petugas Demo','assigned','2026-11-01','2026-11-03','SYNTHETIC DEMO')");
    $db->query("INSERT INTO gh_expenses(expense_date,category,description,amount,payment_status) VALUES('2026-11-01','operational','DEMO belanja kebersihan',150000,'paid')");
}
echo json_encode(array('database'=>$target,'room_id'=>(int)$room['id'],'room_id_wide'=>(int)($rooms[1]['id'] ?? $room['id']),'room_id_mobile'=>(int)($rooms[2]['id'] ?? $room['id']),
    'room_id_tenant'=>(int)$rooms[3]['id'],'room_id_tenant_wide'=>(int)$rooms[4]['id'],'room_id_tenant_mobile'=>(int)$rooms[5]['id'],
    'room_id_journey'=>(int)$rooms[6]['id'],'room_id_journey_wide'=>(int)$rooms[7]['id'],'room_id_journey_mobile'=>(int)$rooms[8]['id'],
    'room_id_financial'=>(int)$rooms[9]['id'],'room_id_financial_wide'=>(int)$rooms[10]['id'],'room_id_financial_mobile'=>(int)$rooms[11]['id'],
    'room_id_billing'=>(int)$rooms[12]['id'],'room_id_billing_wide'=>(int)$rooms[13]['id'],'room_id_billing_mobile'=>(int)$rooms[14]['id'],
    'tables'=>count($tables),'contains_real_occupants'=>false)) . PHP_EOL;
