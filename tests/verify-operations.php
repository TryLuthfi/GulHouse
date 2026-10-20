<?php
require __DIR__ . '/http-helpers.php';
try {
    $stamp=bin2hex(random_bytes(4));
    $room=row("SELECT * FROM gh_rooms WHERE status='available' AND is_public=1 AND monthly_price>0 ORDER BY id LIMIT 1"); $roomId=(int)$room['id'];
    $name='DEMO Portal '.$stamp; $phone='089997'.random_int(100000,999999); $identity='0'.random_int(10000000000,99999999999).'4321';
    $insert=$db->prepare('INSERT INTO gh_tenants(fullname,phone,identity_number) VALUES(?,?,?)'); $insert->bind_param('sss',$name,$phone,$identity); $insert->execute(); $tenantId=$db->insert_id;
    $rent=(int)$room['monthly_price'];
    $db->query("INSERT INTO gh_tenant_stays(tenant_id,room_id,stay_status,check_in_date,check_out_date,monthly_price,deposit) VALUES($tenantId,$roomId,'active','2026-11-01','2026-11-30',$rent,0)"); $stayId=$db->insert_id;
    $db->query("INSERT INTO gh_contracts(contract_no,tenant_id,stay_id,room_id,start_date,end_date,monthly_price,deposit_amount,status,signed_at) VALUES('DEMO-PORTAL-$stamp',$tenantId,$stayId,$roomId,'2026-11-01','2026-11-30',$rent,0,'active','2026-11-01')"); $contractId=$db->insert_id;
    $db->query("UPDATE gh_rooms SET status='occupied',is_public=0 WHERE id=$roomId");
    list($code,$html)=post('/master/index.php/login',array('username'=>'codex_trial','password'=>getenv('GULHOUSE_E2E_PASS') ?: 'LocalDemo2026!'),'/master/index.php/login'); check($code===200 && strpos($html,'Dashboard Operasional GulHouse')!==false,'operations admin login');
    post('/master/index.php/billing/generate',array('period_key'=>'2026-11'),'/master/index.php/billing');
    $invoice=row("SELECT * FROM gh_room_bills WHERE stay_id=$stayId LIMIT 1"); $billId=(int)$invoice['id']; check((int)$invoice['base_price']===$rent,'portal tenant invoice seeded');
    payment($billId,250000,'rent','OPS-PART-'.$stamp); $paymentId=(int)row("SELECT id FROM gh_payments WHERE room_bill_id=$billId AND payment_type='rent'")['id'];
    $secondRoom=row("SELECT id FROM gh_rooms WHERE status='available' AND monthly_price>0 ORDER BY id LIMIT 1"); $secondId=(int)$secondRoom['id'];
    $period=(int)$invoice['period_id'];
    $db->query("INSERT INTO gh_room_bills(period_id,room_id,tenant_id,tenant_name_snapshot,base_price,paid_total,bill_status,due_date) VALUES($period,$secondId,$tenantId,'$name',1000000,0,'unpaid','2026-11-01')"); $otherBill=$db->insert_id;
    payment($otherBill,250000,'rent','OPS-MOVE-'.$stamp,$paymentId);
    check((int)bill($billId)['paid_total']===0 && (int)bill($otherBill)['paid_total']===250000,'moving payment refreshes both invoices');
    payment($billId,250000,'rent','OPS-MOVE-BACK-'.$stamp,$paymentId);
    check((int)bill($billId)['paid_total']===250000 && (int)bill($otherBill)['paid_total']===0,'moving payment back refreshes both invoices');
    list($code,$html)=post('/landing/index.php/payment/check',array('phone'=>$phone,'nik_last4'=>'4321'),'/landing/index.php/payment');
    check($code===200 && strpos($html,'DEMO-PORTAL-'.$stamp)!==false,'resident sees own contract');
    check(strpos($html,'Saldo deposit tercatat')!==false,'resident sees deposit balance');
    $complaint=array('category'=>'facility','complaint_title'=>'DEMO AC '.$stamp,'complaint_detail'=>'Synthetic AC test','tenant_id'=>999999,'room_id'=>$secondId);
    post('/landing/index.php/resident/complaints',$complaint,'/landing/index.php/payment/status');
    post('/landing/index.php/resident/complaints',$complaint,'/landing/index.php/payment/status');
    $ticket=row("SELECT * FROM gh_complaints WHERE complaint_title='DEMO AC $stamp'"); $ticketId=(int)$ticket['id'];
    check((int)$ticket['tenant_id']===$tenantId && (int)$ticket['room_id']===$roomId,'complaint ignores forged occupant/room ids');
    check(count_rows("SELECT COUNT(*) n FROM gh_complaints WHERE complaint_title='DEMO AC $stamp'")===1,'resident complaint duplicate idempotent');
    post('/master/index.php/complaints/maintenance/'.$ticketId,array(),'/master/index.php/complaints');
    post('/master/index.php/complaints/maintenance/'.$ticketId,array(),'/master/index.php/complaints');
    check(count_rows('SELECT COUNT(*) n FROM gh_maintenance_tickets WHERE complaint_id='.$ticketId)===1,'complaint escalates to one maintenance ticket');
    $maintenance=row('SELECT * FROM gh_maintenance_tickets WHERE complaint_id='.$ticketId); $maintenanceId=(int)$maintenance['id'];
    $maintenance['status']='done'; $maintenance['finished_at']='2026-11-02'; $maintenance['cost_amount']=125000; $maintenance['pic_name']='Petugas Demo'; $maintenance['id']=$maintenanceId;
    post('/master/index.php/ops/maintenance/save',$maintenance,'/master/index.php/maintenance'); post('/master/index.php/ops/maintenance/save',$maintenance,'/master/index.php/maintenance');
    check(row('SELECT status FROM gh_complaints WHERE id='.$ticketId)['status']==='resolved','completed maintenance resolves linked complaint');
    check(count_rows('SELECT COUNT(*) n FROM gh_expenses WHERE maintenance_ticket_id='.$maintenanceId)===1,'maintenance creates exactly one expense');
    check((int)row('SELECT amount FROM gh_expenses WHERE maintenance_ticket_id='.$maintenanceId)['amount']===125000,'maintenance cost reconciles');
    $modules=array(
        'facilities'=>array('table'=>'gh_facility_checks','label'=>'facility_name','payload'=>array('facility_name'=>'DEMO Pantry '.$stamp,'location_label'=>'Pantry','quantity'=>1,'condition_status'=>'good','followup_status'=>'done','check_date'=>'2026-11-02','officer_name'=>'Petugas Demo','pic_name'=>'Petugas Demo')),
        'housekeeping'=>array('table'=>'gh_housekeeping_checks','label'=>'location_label','payload'=>array('location_label'=>'DEMO Housekeeping '.$stamp,'check_item'=>'Lantai','condition_status'=>'good','check_date'=>'2026-11-02','officer_name'=>'Petugas Demo','status'=>'done')),
        'audits'=>array('table'=>'gh_monthly_audits','label'=>'audit_item','payload'=>array('period_label'=>'2026-11','location_label'=>'Area demo','audit_item'=>'DEMO Audit '.$stamp,'result_status'=>'ok','status'=>'done')),
        'finance'=>array('table'=>'gh_expenses','label'=>'description','payload'=>array('expense_date'=>'2026-11-02','category'=>'operational','description'=>'DEMO Expense '.$stamp,'amount'=>200000,'payment_status'=>'paid')),
        'notifications'=>array('table'=>'gh_notification_queue','label'=>'subject','payload'=>array('recipient_name'=>$name,'recipient_phone'=>$phone,'subject'=>'DEMO Reminder '.$stamp,'message_body'=>'Pesan demo, tidak dikirim otomatis','status'=>'draft')),
        'templates'=>array('table'=>'gh_document_templates','label'=>'template_key','payload'=>array('template_key'=>'demo_'.$stamp,'title'=>'DEMO Template '.$stamp,'document_type'=>'other','is_active'=>1,'body_template'=>'Template demo')),
    );
    foreach($modules as $module=>$config) {
        post('/master/index.php/ops/'.$module.'/save',$config['payload'],'/master/index.php/'.$module);
        $value=$db->real_escape_string($config['payload'][$config['label']]);
        $saved=row('SELECT * FROM '.$config['table'].' WHERE '.$config['label']."='$value' ORDER BY id DESC LIMIT 1");
        check((bool)$saved,$module.' record saved');
        list($code,$csv)=request('/master/index.php/ops/'.$module.'/export'); check($code===200 && strpos($csv,$config['payload'][$config['label']])!==false,$module.' CSV exports record');
    }
    list($code,$csv)=request('/master/index.php/ops/finance/export?period=2026-10');
    check($code===200 && strpos($csv,'DEMO Expense '.$stamp)===false,'finance export respects month filter');
    list($code,$receipt)=request('/master/index.php/payments/receipt/'.$paymentId);
    check($code===200 && strpos($receipt,'OPS-MOVE-BACK-'.$stamp)!==false,'manual payment receipt printable');
    $before=count_rows('SELECT COUNT(*) n FROM gh_payments');
    payment($billId,-100,'rent','OPS-NEG-'.$stamp);
    check(count_rows('SELECT COUNT(*) n FROM gh_payments')===$before,'negative payment rejected');
    $negative='DEMO Negative '.$stamp;
    post('/master/index.php/ops/finance/save',array('expense_date'=>'2026-11-02','category'=>'operational','description'=>$negative,'amount'=>'-100','payment_status'=>'paid'),'/master/index.php/finance');
    check(count_rows("SELECT COUNT(*) n FROM gh_expenses WHERE description='$negative'")===0,'negative expense rejected');
    // Two consecutive historical tenants in one room and month must retain separate invoices.
    $turnoverRoom=(int)row("SELECT r.id FROM gh_rooms r WHERE r.status='available' AND r.monthly_price>0 AND NOT EXISTS (SELECT 1 FROM gh_tenant_stays s WHERE s.room_id=r.id) ORDER BY r.id DESC LIMIT 1")['id'];
    $turnoverStays=array();
    foreach(array(array('2026-10-01','2026-10-15'),array('2026-10-16','2026-10-31')) as $i=>$dates) {
        $turnoverName='DEMO Turnover '.$stamp.' '.$i;
        $db->query("INSERT INTO gh_tenants(fullname,phone) VALUES('$turnoverName','08999900000$i')"); $turnoverTenant=$db->insert_id;
        $db->query("INSERT INTO gh_tenant_stays(tenant_id,room_id,stay_status,check_in_date,check_out_date,monthly_price) VALUES($turnoverTenant,$turnoverRoom,'ended','{$dates[0]}','{$dates[1]}',1500000)"); $turnoverStay=$db->insert_id; $turnoverStays[]=$turnoverStay;
        $db->query("INSERT INTO gh_contracts(contract_no,tenant_id,stay_id,room_id,start_date,end_date,monthly_price,status) VALUES('DEMO-TURN-$stamp-$i',$turnoverTenant,$turnoverStay,$turnoverRoom,'{$dates[0]}','{$dates[1]}',1500000,'ended')");
    }
    post('/master/index.php/billing/generate',array('period_key'=>'2026-10'),'/master/index.php/billing');
    check(count_rows('SELECT COUNT(*) n FROM gh_room_bills WHERE stay_id IN ('.implode(',',$turnoverStays).')')===2,'two occupants in one month have separate invoices');
    post('/master/index.php/billing/generate',array('period_key'=>'2026-10'),'/master/index.php/billing');
    check(count_rows('SELECT COUNT(*) n FROM gh_room_bills WHERE stay_id IN ('.implode(',',$turnoverStays).')')===2,'turnover billing rerun has no duplicates');
    $legacyRoom=(int)row("SELECT r.id FROM gh_rooms r WHERE r.status='available' AND r.monthly_price>0 AND NOT EXISTS (SELECT 1 FROM gh_tenant_stays s WHERE s.room_id=r.id) ORDER BY r.id DESC LIMIT 1")['id'];
    $db->query("INSERT INTO gh_tenant_stays(tenant_id,room_id,stay_status,source_period,monthly_price) VALUES($tenantId,$legacyRoom,'active','September2026',1250000)"); $legacyStay=$db->insert_id;
    post('/master/index.php/billing/generate',array('period_key'=>'2026-11'),'/master/index.php/billing');
    $legacyBill=row('SELECT * FROM gh_room_bills WHERE stay_id='.$legacyStay);
    check($legacyBill && (int)$legacyBill['base_price']===1250000,'legacy active stay bills without invented signed contract');
    check(strpos($legacyBill['notes'],'belum direkonsiliasi')!==false,'legacy contract gap visible on invoice');
    post('/master/index.php/operations/draft_contract_from_stay/'.$legacyStay,array('start_date'=>'2026-11-01','end_date'=>'2026-11-30','monthly_price'=>1250000,'deposit_amount'=>0),'/master/index.php/tenants');
    check(row('SELECT status FROM gh_contracts WHERE stay_id='.$legacyStay)['status']==='draft','legacy stay can draft legal contract');
    list($code,$html)=request('/master/index.php/contracts/print/'.$contractId); check($code===200 && strpos($html,$name)!==false,'contract printable');
    echo json_encode(array('passed'=>$checks,'complete'=>true,'schema'=>$schema)).PHP_EOL;
} finally { if(is_file($cookie)) unlink($cookie); }
