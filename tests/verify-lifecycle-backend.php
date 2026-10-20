<?php
// Synthetic-only integration assertions. No real occupant data or credentials in output.
$runtime=json_decode(file_get_contents(dirname(__DIR__).'/.e2e/lifecycle/runtime.json'),TRUE);
if(!is_array($runtime)||!preg_match('/^gulhouse_test_[a-zA-Z0-9_]+$/D',$runtime['schema']??''))throw new RuntimeException('Isolated runtime required.');
putenv('GULHOUSE_DATABASE='.$runtime['schema']); putenv('GULHOUSE_TEST_URL='.$runtime['url']);
require __DIR__.'/http-helpers.php';
function multipart_post($path,$fields,$files,$page) {
    global $base,$cookie;
    list($code,$html)=request($page); if(!preg_match('/name="(csrf_[^"]+)" value="([a-f0-9]+)"/',$html,$token))throw new RuntimeException('CSRF missing on '.$page);
    $fields[$token[1]]=$token[2]; foreach($files as $key=>$file)$fields[$key]=new CURLFile($file,'image/png',basename($file));
    $curl=curl_init($base.$path); curl_setopt_array($curl,array(CURLOPT_RETURNTRANSFER=>TRUE,CURLOPT_FOLLOWLOCATION=>TRUE,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_POST=>TRUE,CURLOPT_POSTFIELDS=>$fields,CURLOPT_TIMEOUT=>90));
    $out=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);return array($status,$out);
}
function action_call($id,$a,$p=array(),$files=array()) { $p['action']=$a; $path='/master/index.php/lifecycle/action/'.$id; $page='/master/index.php/lifecycle/detail/'.$id;return $files?multipart_post($path,$p,$files,$page):post($path,$p,$page); }
$residentCookie=$cookie;$adminCookie=tempnam(sys_get_temp_dir(),'gh_lc_admin_');$files=array();$suffix=(string)time().random_int(100,999);$phone='089'.substr($suffix,-9);
try {
    $room=row("SELECT * FROM gh_rooms WHERE status='available' ORDER BY id DESC LIMIT 1");if(!$room)throw new RuntimeException('Synthetic available room required.');
    foreach(array('ktp','initial','routine','checkout','refund') as $k){$p=tempnam(sys_get_temp_dir(),'gh_lc_'.$k).' .png';$p=str_replace(' .png','.png',$p);file_put_contents($p,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+Xj1sAAAAASUVORK5CYII=')."\nsynthetic-$k-$suffix");$files[$k]=$p;}
    $intake=array('full_name'=>'Synthetic Backend '.$suffix,'phone'=>$phone,'room_id'=>$room['id'],'planned_start'=>date('Y-m-d'),'source'=>'walk_in','consent'=>'1');
    post('/landing/index.php/resident/start',$intake,'/landing/index.php/resident/start');
    check(count_rows("SELECT COUNT(*) n FROM gh_resident_cases WHERE phone='$phone'")===0,'KTP mandatory, intake without upload rejected');
    list($status,$html)=multipart_post('/landing/index.php/resident/start',$intake,array('ktp_file'=>$files['ktp']),'/landing/index.php/resident/start');
    $c=row("SELECT * FROM gh_resident_cases WHERE phone='$phone'");check($status===200&&$c&&$c['ktp_file_id'],'valid synthetic KTP creates private intake');$id=(int)$c['id'];
    preg_match('/[a-f0-9]{64}/',$html,$m);check(!empty($m[0]),'one-time capability shown');
    $profile=array('action'=>'full_profile','identity_number'=>'9999'.substr(str_pad($suffix,12,'0',STR_PAD_LEFT),-12),'address'=>'Synthetic address','birth_date'=>'1995-01-01','gender'=>'male','occupation'=>'Synthetic employee','emergency_name'=>'Synthetic contact','emergency_phone'=>'081234567890','emergency_relation'=>'Family','emergency_address'=>'Synthetic emergency address','consent'=>'1');
    post('/landing/index.php/resident/action',$profile,'/landing/index.php/resident/portal');check(!row("SELECT profile_completed_at FROM gh_resident_cases WHERE id=$id")['profile_completed_at'],'full biodata blocked before actual payment');
    $cookie=$adminCookie;post('/master/index.php/login',array('username'=>$runtime['username'],'password'=>$runtime['password']),'/master/index.php/login');
    action_call($id,'reserve',array('end_date'=>date('Y-m-d',strtotime('+1 month')),'monthly_price'=>'1500000','deposit'=>'500000'));
    $c=row("SELECT * FROM gh_resident_cases WHERE id=$id");check($c['stay_id']&&$c['tenant_id'],'reserve creates tenant and reserved stay');$stay=(int)$c['stay_id'];
    $bill=row("SELECT * FROM gh_room_bills WHERE stay_id=$stay ORDER BY id LIMIT 1");check($bill&&(int)$bill['base_price']===1500000,'initial invoice created before activation');
    $cookie=$residentCookie;multipart_post('/landing/index.php/resident/action',array('action'=>'payment_proof','invoice_id'=>$bill['id']),array('payment_file'=>$files['initial']),'/landing/index.php/resident/portal');
    check(count_rows("SELECT COUNT(*) n FROM gh_resident_files WHERE case_id=$id AND kind='rent_proof'")===1,'resident uploads private payment claim from own portal');
    check(count_rows("SELECT COUNT(*) n FROM gh_payments WHERE room_bill_id=".(int)$bill['id'])===0&&!row("SELECT payment_verified_at FROM gh_resident_cases WHERE id=$id")['payment_verified_at'],'uploaded proof never creates actual payment or unlocks biodata');
    $cookie=$adminCookie;
    action_call($id,'verify_payment');check(!row("SELECT payment_verified_at FROM gh_resident_cases WHERE id=$id")['payment_verified_at'],'verification rejects no actual rent');
    $db->query("INSERT INTO gh_payments(room_bill_id,payment_date,amount,payment_type,method,reference_no,source_key) VALUES({$bill['id']},'".date('Y-m-d')."',1500000,'rent','Synthetic','LC-PAY-$suffix','LC-PAY-$suffix')");
    $db->query("UPDATE gh_room_bills SET paid_total=1500000,bill_status='paid',past_due_amount=0,future_due_amount=0 WHERE id=".(int)$bill['id']);
    $db->query("INSERT INTO gh_deposit_transactions(tenant_id,stay_id,room_id,transaction_date,transaction_type,amount,method,reference_no) VALUES({$c['tenant_id']},$stay,{$c['room_id']},'".date('Y-m-d')."','received',500000,'Synthetic','LC-DEP-$suffix')");
    action_call($id,'verify_payment');check((bool)row("SELECT payment_verified_at FROM gh_resident_cases WHERE id=$id")['payment_verified_at'],'actual initial rent verifies');
    $cookie=$residentCookie;post('/landing/index.php/resident/action',$profile,'/landing/index.php/resident/portal');check((bool)row("SELECT profile_completed_at FROM gh_resident_cases WHERE id=$id")['profile_completed_at'],'resident submits complete biodata after verified rent');
    $cookie=$adminCookie;action_call($id,'checkin',array('contract_signed'=>'1','inspection_confirmed'=>'1'));check(row("SELECT stay_status FROM gh_tenant_stays WHERE id=$stay")['stay_status']==='reserved','checkin rejects missing final facility handover');
    $items=$db->query('SELECT * FROM gh_lifecycle_facility_catalog WHERE is_active=1')->fetch_all(MYSQLI_ASSOC);$fields=array();foreach($items as $it){$fields['items['.$it['id'].'][condition_status]']='good';$fields['items['.$it['id'].'][actual_qty]']=$it['standard_qty'];$fields['items['.$it['id'].'][notes]']='Synthetic observation';}
    $common=array('check_date'=>date('Y-m-d'),'period'=>date('Y-m'),'officer_name'=>'Synthetic officer');
    foreach(array('check_in'=>'initial','routine'=>'routine','check_out'=>'checkout') as $stage=>$fileKey){
        if($stage==='routine'){
            action_call($id,'checkin',array('contract_signed'=>'1','inspection_confirmed'=>'1'));check(row("SELECT stay_status FROM gh_tenant_stays WHERE id=$stay")['stay_status']==='active','checkin activates after KTP biodata rent deposit final handover');
            $cookie=$residentCookie;post('/landing/index.php/resident/action',array('action'=>'notice','planned_exit'=>date('Y-m-d'),'reason'=>'Synthetic move','refund_bank'=>'Synthetic bank','refund_account'=>'00000000'),'/landing/index.php/resident/portal');$cookie=$adminCookie;
            $q=row("SELECT * FROM gh_checkout_requests WHERE case_id=$id");check($q&&(int)$q['late_notice']===1,'late H-15 notice flagged without deduction');
            action_call($id,'notice_update',array('planned_exit'=>date('Y-m-d',strtotime('+1 day'))));check(row("SELECT planned_exit FROM gh_checkout_requests WHERE case_id=$id")['planned_exit']===date('Y-m-d'),'notice correction rejects missing revision reason');
            action_call($id,'notice_update',array('planned_exit'=>date('Y-m-d',strtotime('+1 day')),'revision_reason'=>'Synthetic date correction','refund_bank'=>'Corrected synthetic bank','refund_account'=>'11111111'));
            $revisedNotice=row("SELECT * FROM gh_checkout_requests WHERE case_id=$id");check($revisedNotice['planned_exit']===date('Y-m-d',strtotime('+1 day'))&&$revisedNotice['notice_date']===$q['notice_date']&&$revisedNotice['refund_account']==='11111111','admin corrects plan and bank without resetting original notice date');
            $event=row("SELECT description FROM gh_resident_events WHERE case_id=$id AND event_type='notice_update' ORDER BY id DESC LIMIT 1");check(strpos($event['description'],$q['planned_exit'])!==FALSE&&strpos($event['description'],$revisedNotice['planned_exit'])!==FALSE&&strpos($event['description'],'Synthetic date correction')!==FALSE,'notice revision audit preserves old new dates and reason');
            $cookie=$residentCookie;post('/landing/index.php/resident/action',array('action'=>'notice_update','planned_exit'=>date('Y-m-d',strtotime('+20 days')),'revision_reason'=>'Unauthorized correction'),'/landing/index.php/resident/portal');$cookie=$adminCookie;check(row("SELECT planned_exit FROM gh_checkout_requests WHERE case_id=$id")['planned_exit']===$revisedNotice['planned_exit'],'resident cannot invoke admin notice correction');
        }
        $save=$common+array('stage'=>$stage)+$fields;action_call($id,'inspection_save',$save,array('scan_file'=>$files[$fileKey]));$i=row("SELECT * FROM gh_facility_inspections WHERE case_id=$id AND stage='$stage' ORDER BY id DESC LIMIT 1");check((bool)$i,"$stage scan and item draft saved");$iid=(int)$i['id'];
        action_call($id,'inspection_finalize',array('inspection_id'=>$iid));check(row("SELECT status FROM gh_facility_inspections WHERE id=$iid")['status']==='draft',"$stage finalization requires human signature confirmation");
        action_call($id,'inspection_finalize',array('inspection_id'=>$iid,'resident_signed'=>'1','manager_signed'=>'1','supervisor_signed'=>'1'));check(row("SELECT status FROM gh_facility_inspections WHERE id=$iid")['status']==='final',"$stage signed scan finalizes");
        action_call($id,'inspection_save',$save+array('inspection_id'=>$iid));check(row("SELECT status FROM gh_facility_inspections WHERE id=$iid")['status']==='final',"$stage final stays immutable");
    }
    action_call($id,'checkout',array('actual_date'=>date('Y-m-d'),'keys_returned'=>'1','deduction_amount'=>'100000','deduction_reason'=>'Synthetic agreed repair','disputed_amount'=>'50000','refund_bank'=>'Synthetic bank','refund_account'=>'00000000'));
    $q=row("SELECT * FROM gh_checkout_requests WHERE case_id=$id");check($q['actual_date']===date('Y-m-d')&&$q['refund_due']===date('Y-m-d',strtotime('+2 days')),'checkout closes stay and calculates H+2 calendar');
    check((int)$q['refundable_amount']===350000&&(int)$q['paid_amount']===0,'undisputed refund obligation separate from real payment');
    action_call($id,'notice_update',array('planned_exit'=>date('Y-m-d',strtotime('+30 days')),'revision_reason'=>'Post-checkout correction attempt'));check(row("SELECT planned_exit FROM gh_checkout_requests WHERE case_id=$id")['planned_exit']===$revisedNotice['planned_exit']&&count_rows("SELECT COUNT(*) n FROM gh_resident_events WHERE case_id=$id AND event_type='notice_update'")===1,'notice correction blocked after actual checkout');
    action_call($id,'refund',array('amount'=>'350000','transfer_date'=>date('Y-m-d'),'reference_no'=>'LC-REF-'.$suffix));check((int)row("SELECT paid_amount FROM gh_checkout_requests WHERE case_id=$id")['paid_amount']===0,'refund without transfer proof rejected');
    action_call($id,'refund',array('amount'=>'350000','transfer_date'=>date('Y-m-d'),'reference_no'=>'LC-REF-'.$suffix),array('proof_file'=>$files['refund']));check((int)row("SELECT paid_amount FROM gh_checkout_requests WHERE case_id=$id")['paid_amount']===350000,'undisputed refund paid despite unresolved disputed balance');
    action_call($id,'refund',array('amount'=>'350000','transfer_date'=>date('Y-m-d'),'reference_no'=>'LC-REF-'.$suffix),array('proof_file'=>$files['refund']));check(count_rows("SELECT COUNT(*) n FROM gh_refund_payments WHERE case_id=$id")===1,'duplicate transfer cannot duplicate refund ledger');
    $cookie=$residentCookie;list($status,$html)=request('/landing/index.php/resident/portal');check($status===200&&strpos($html,'350.000')!==FALSE,'resident retains refund visibility after checkout');
    multipart_post('/landing/index.php/resident/action',array('action'=>'payment_proof'),array('payment_file'=>$files['checkout']),'/landing/index.php/resident/portal');check(count_rows("SELECT COUNT(*) n FROM gh_resident_events WHERE case_id=$id AND event_type='payment_proof'")===1,'payment claim submission blocked after checkout');
    $cookie=$adminCookie;action_call($id,'resolve_dispute',array('released_amount'=>'50000','resolution_note'=>'Synthetic full release'));$q=row("SELECT * FROM gh_checkout_requests WHERE case_id=$id");check((int)$q['disputed_amount']===0&&(int)$q['refundable_amount']===400000,'dispute resolution adds released refund without losing history');
    $db->query("INSERT INTO gh_tenants(fullname,phone,identity_number,address,occupation,religion,marital_status,source_label) VALUES('Synthetic existing $suffix','087".substr($suffix,-9)."','8888".substr(str_pad($suffix,12,'0',STR_PAD_LEFT),-12)."','Existing address','Existing occupation','Existing religion','Existing marital','Synthetic legacy')");$legacyTenant=$db->insert_id;
    $db->query("INSERT INTO gh_tenant_stays(tenant_id,room_id,stay_status,check_in_date,check_out_date,monthly_price,deposit,source_period) VALUES($legacyTenant,{$room['id']},'active','".date('Y-m-d')."','".date('Y-m-d',strtotime('+1 month'))."',1200000,500000,'Synthetic imported')");$legacyStay=$db->insert_id;$beforeLegacy=row("SELECT * FROM gh_tenant_stays WHERE id=$legacyStay");
    post('/master/index.php/lifecycle/enroll',array('stay_id'=>$legacyStay),'/master/index.php/lifecycle');check(count_rows("SELECT COUNT(*) n FROM gh_resident_cases WHERE stay_id=$legacyStay")===0,'existing enrollment requires private KTP upload');
    multipart_post('/master/index.php/lifecycle/enroll',array('stay_id'=>$legacyStay),array('ktp_file'=>$files['ktp']),'/master/index.php/lifecycle');$legacyCase=row("SELECT * FROM gh_resident_cases WHERE stay_id=$legacyStay");check($legacyCase&&$legacyCase['status']==='active','explicit existing active onboarding creates managed case');
    check(row("SELECT * FROM gh_tenant_stays WHERE id=$legacyStay")===$beforeLegacy,'existing onboarding preserves all original stay fields');
    $seed=json_decode($legacyCase['profile_json'],TRUE);check($seed['religion']==='Existing religion'&&$seed['marital_status']==='Existing marital','existing onboarding preserves prior biodata');
    multipart_post('/master/index.php/lifecycle/enroll',array('stay_id'=>$legacyStay),array('ktp_file'=>$files['ktp']),'/master/index.php/lifecycle');check(count_rows("SELECT COUNT(*) n FROM gh_resident_cases WHERE stay_id=$legacyStay")===1,'duplicate existing enrollment rejected');
    echo json_encode(array('passed'=>$checks,'complete'=>TRUE,'synthetic_case_id'=>$id)).PHP_EOL;
} finally { foreach($files as $p)if(is_file($p))unlink($p);foreach(array($adminCookie,$residentCookie) as $p)if(is_file($p))unlink($p); }
