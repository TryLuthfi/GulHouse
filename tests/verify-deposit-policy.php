<?php
require __DIR__ . '/http-helpers.php';
try {
    $stamp = bin2hex(random_bytes(4));
    $roomId = (int) row("SELECT id FROM gh_rooms WHERE status='available' AND monthly_price>0 ORDER BY id DESC LIMIT 1")['id'];
    $db->query("INSERT INTO gh_tenants(fullname,phone,source_label) VALUES('DEMO Deposit Policy $stamp','089960000001','SYNTHETIC TEST')");
    $tenantId = (int) $db->insert_id;
    $db->query("INSERT INTO gh_tenant_stays(tenant_id,room_id,stay_status,check_in_date,check_out_date,monthly_price,deposit) VALUES($tenantId,$roomId,'active','2030-01-01','2030-02-28',20000,100000)");
    $stayId = (int) $db->insert_id;
    $db->query("INSERT INTO gh_deposit_transactions(tenant_id,stay_id,room_id,transaction_date,transaction_type,amount,description) VALUES($tenantId,$stayId,$roomId,'2030-01-01','received',100000,'SYNTHETIC TEST')");
    $period = row("SELECT id FROM gh_billing_periods WHERE period_key='2030-02'");
    if (! $period) {
        $db->query("INSERT INTO gh_billing_periods(period_key,period_label,period_month,period_year,source_file) VALUES('2030-02','FEBRUARY 2030',2,2030,'SYNTHETIC TEST')");
        $periodId = (int) $db->insert_id;
    } else $periodId = (int) $period['id'];
    $db->query("INSERT INTO gh_room_bills(period_id,room_id,stay_id,tenant_id,base_price,paid_total,bill_status,due_date) VALUES($periodId,$roomId,$stayId,$tenantId,20000,0,'unpaid','2030-02-01')");
    $billId = (int) $db->insert_id;
    post('/master/index.php/login', array('username'=>'codex_trial','password'=>getenv('GULHOUSE_E2E_PASS') ?: 'LocalDemo2026!'), '/master/index.php/login');
    $refund = array('tenant_id'=>$tenantId,'stay_id'=>$stayId,'room_id'=>$roomId,'transaction_date'=>'2030-01-10','transaction_type'=>'refund','amount'=>10000,'method'=>'Transfer demo','description'=>'Synthetic final settlement','refund_reason'=>'INSPECTION-DEMO-'.$stamp);
    post('/master/index.php/ops/deposits/save', $refund, '/master/index.php/deposits');
    check(count_rows("SELECT COUNT(*) n FROM gh_deposit_transactions WHERE stay_id=$stayId AND transaction_type='refund'")===0, 'direct refund rejects missing final inspection');
    $refund['refund_inspection_confirmed'] = 1;
    $refund['refund_reason'] = '';
    post('/master/index.php/ops/deposits/save', $refund, '/master/index.php/deposits');
    check(count_rows("SELECT COUNT(*) n FROM gh_deposit_transactions WHERE stay_id=$stayId AND transaction_type='refund'")===0, 'direct refund rejects missing inspection reference');
    $refund['refund_reason'] = 'INSPECTION-DEMO-'.$stamp;
    post('/master/index.php/ops/deposits/save', $refund, '/master/index.php/deposits');
    check(count_rows("SELECT COUNT(*) n FROM gh_deposit_transactions WHERE stay_id=$stayId AND transaction_type='refund'")===0, 'direct refund rejects issued future invoice debt');
    payment($billId, 20000, 'rent', 'DEPOSIT-POLICY-'.$stamp);
    post('/master/index.php/ops/deposits/save', $refund, '/master/index.php/deposits');
    $entry = row("SELECT * FROM gh_deposit_transactions WHERE stay_id=$stayId AND transaction_type='refund'");
    check($entry && (int) $entry['amount']===10000, 'direct refund accepts inspected settlement after all issued debt paid');
    check(strpos($entry['description'], 'INSPECTION-DEMO-'.$stamp)!==false && strpos($entry['description'], 'pemeriksaan akhir')!==false, 'direct refund persists final inspection audit reference');
    echo json_encode(array('passed'=>$checks,'complete'=>true,'schema'=>$schema)).PHP_EOL;
} finally { if (is_file($cookie)) unlink($cookie); }
