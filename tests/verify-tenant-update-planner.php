<?php
require dirname(__DIR__).'/scripts/import-gulhouse-tenants-update.php';
$checks=0;
function verify_import($ok,$label){global $checks;if(!$ok)throw new RuntimeException($label);$checks++;echo 'PASS '.$label.PHP_EOL;}
function snapshot_import(){return array('database'=>'gulhouse_test_import','data'=>array(
 'gh_properties'=>array(array('id'=>1,'code'=>'GH 1')),
 'gh_rooms'=>array(array('id'=>1,'property_id'=>1,'room_code'=>'A-101','room_label'=>'Demo 101','status'=>'occupied','is_public'=>0),array('id'=>2,'property_id'=>1,'room_code'=>'A-102','room_label'=>'Demo 102','status'=>'available','is_public'=>1)),
 'gh_tenants'=>array(array('id'=>1,'fullname'=>'Demo Person','identity_number'=>'1111222233334444','phone'=>'081234567890','occupation'=>'Old Job','address'=>'Preserve Address')),
 'gh_tenant_stays'=>array(array('id'=>1,'tenant_id'=>1,'room_id'=>1,'stay_status'=>'active','check_in_date'=>null,'check_out_date'=>null,'monthly_price'=>'1500000','deposit'=>'500000')),
 'gh_tenant_emergency_contacts'=>array(array('id'=>1,'tenant_id'=>1,'contact_name'=>'Demo Contact','phone'=>'081299999999','relationship'=>'Sibling')),
 'gh_contracts'=>array(),'gh_deposit_transactions'=>array(),'gh_room_bills'=>array(),'gh_booking_requests'=>array()));}
function source_import($extra=array()){return array_merge(array('property_code'=>'GH1','room_code'=>'A-101','fullname'=>'Demo Person','identity_number'=>'1111222233334444','phone'=>'081234567890','stay_status'=>'active'),$extra);}
function plan_import($rows,$s=null){return GulhouseTenantUpdate::plan(array('rows'=>$rows),$s?:snapshot_import());}
$p=plan_import(array(source_import()));verify_import(count($p['operations'])===0,'identical input is no-op');
$p=plan_import(array(source_import(array('occupation'=>'New Job','address'=>''))));verify_import(count($p['operations'])===1 && $p['operations'][0]['values']===array('occupation'=>'New Job'),'blank preserves existing fields');
verify_import($p['operations'][0]['before']===array('occupation'=>'Old Job'),'field-level before diff recorded');
$p=plan_import(array(source_import(array('identity_number'=>null,'phone'=>'+62 81234567890','occupation'=>'New Job'))));verify_import($p['rows'][0]['match']==='phone_and_name','international phone matches with corroborated name');
$p=plan_import(array(source_import(array('identity_number'=>null,'phone'=>null,'occupation'=>'New Job'))));verify_import(!$p['operations'] && $p['issues'][0]['reasons'][0]==='room_name_candidate_needs_verified_mapping','room-name requires explicit source verification');
$p=plan_import(array(source_import(array('identity_number'=>null,'phone'=>null,'verified_room_name_match'=>true,'occupation'=>'New Job'))));verify_import(count($p['operations'])===1,'verified unique room-name updates clear profile');
$p=plan_import(array(source_import(),source_import(array('fullname'=>'Other Person','room_code'=>'A-102'))));verify_import(!$p['operations'] && count($p['issues'])===2,'colliding identity holds both rows');
$p=plan_import(array(source_import(array('identity_number'=>'12345'))));verify_import(!$p['operations'],'invalid identity never guessed');
$p=plan_import(array(source_import(array('identity_number'=>null,'fullname'=>'Other Person'))));verify_import(!$p['operations'],'phone shared with different name never merges');
$p=plan_import(array(source_import(array('monthly_price'=>'-100','occupation'=>'New Job'))));verify_import(count($p['operations'])===1 && $p['operations'][0]['table']==='gh_tenants','invalid stay money staged while profile preserved');
$p=plan_import(array(source_import(array('check_in_date'=>'2026-02-30','occupation'=>'New Job'))));verify_import(count($p['operations'])===1 && count($p['issues'])===1,'invalid dates cannot corrupt stay');
$s=snapshot_import();$s['data']['gh_contracts'][]=array('id'=>1,'stay_id'=>1,'status'=>'active');
$p=plan_import(array(source_import(array('monthly_price'=>'1700000','occupation'=>'New Job'))),$s);verify_import(count($p['operations'])===1 && in_array('contract_backed_stay_terms_protected',$p['issues'][0]['reasons'],true),'contract terms protected while profile updated');
$p=plan_import(array(source_import(array('room_code'=>'A-102','check_in_date'=>null,'occupation'=>'New Job'))));verify_import(count($p['operations'])===1 && in_array('undated_new_or_returning_stay_requires_review',$p['issues'][0]['reasons'],true),'undated transfer never invents new stay');
$p=plan_import(array(source_import(array('stay_status'=>'ended'))));verify_import(!$p['operations'] && in_array('ended_stay_requires_actual_checkout_date',$p['issues'][0]['reasons'],true),'unknown checkout staged by default');
$p=plan_import(array(source_import(array('fullname'=>'New Person','identity_number'=>'5555666677778888','phone'=>'081211112222','room_code'=>'A-102','check_in_date'=>'2026-10-15','occupation'=>'Analyst'))));
verify_import(count(array_filter($p['operations'],function($op){return $op['table']==='gh_tenant_stays';}))===1,'clear new occupant creates one stay');
$stay=array_values(array_filter($p['operations'],function($op){return $op['table']==='gh_tenant_stays';}))[0];verify_import(!isset($stay['values']['monthly_price'])&&!isset($stay['values']['deposit'])&&!isset($stay['values']['check_out_date']),'new stay never invents absent money or exit');
$p=plan_import(array(source_import(array('fullname'=>'New Person','identity_number'=>'5555666677778888','phone'=>'081211112222','check_in_date'=>'2026-10-15'))));verify_import(count($p['operations'])===1 && $p['operations'][0]['table']==='gh_tenants','occupied room conflict preserves clear identity only');
$p=plan_import(array());verify_import(!$p['operations'],'absent source row never ends or deletes stay');
$p=plan_import(array(source_import(array('emergency'=>array('phone'=>null,'contact_name'=>'Updated Contact')))));verify_import(count($p['operations'])===1 && $p['operations'][0]['values']===array('contact_name'=>'Updated Contact'),'blank emergency phone preserved');
verify_import(!array_filter($p['operations'],function($op){return in_array($op['table'],array('gh_payments','gh_room_bills','gh_contracts'),true);}), 'financial history outside mutation scope');
$p=plan_import(array(array('property_code'=>'GH1','room_code'=>'A-101','stay_status'=>'empty')));
$vacancy=$p['operations'][0];verify_import($vacancy['values']['stay_status']==='ended'&&!array_key_exists('check_out_date',$vacancy['values'])&&strpos($vacancy['values']['notes'],'actual_exit_unknown')!==false,'explicit vacancy records observation without inventing exit');
verify_import($p['operations'][1]['values']===array('status'=>'available','is_public'=>1),'safe vacancy releases room');
$s=snapshot_import();$s['data']['gh_rooms'][0]['status']='maintenance';$p=plan_import(array(array('property_code'=>'GH1','room_code'=>'A-101','stay_status'=>'empty')),$s);verify_import(count($p['operations'])===1,'vacancy preserves maintenance room');
$s=snapshot_import();$s['data']['gh_contracts'][]=array('id'=>1,'stay_id'=>1,'status'=>'draft');$p=plan_import(array(array('property_code'=>'GH1','room_code'=>'A-101','stay_status'=>'empty')),$s);verify_import(!$p['operations'],'vacancy cannot close contract-backed stay');
$p=plan_import(array(source_import(array('fullname'=>'New Person','identity_number'=>'5555666677778888','phone'=>'081211112222','check_in_date'=>'2026-10-15','verified_current_occupant'=>true))));
verify_import(count(array_filter($p['operations'],function($op){return $op['table']==='gh_tenant_stays';}))===2,'verified replacement closes old and inserts separate stay');
$old=array_values(array_filter($p['operations'],function($op){return $op['table']==='gh_tenant_stays'&&$op['action']==='update';}))[0];verify_import(!array_key_exists('tenant_id',$old['values'])&&!array_key_exists('check_out_date',$old['values']),'replacement never reassigns history or invents exit');
$s=snapshot_import();$s['data']['gh_tenants'][0]['identity_number']='12345';$p=plan_import(array(source_import(array('identity_number'=>'12345','verified_room_name_match'=>true,'occupation'=>'New Job'))),$s);verify_import(count($p['operations'])===1&&!isset($p['operations'][0]['values']['identity_number']),'verified invalid legacy identity preserves key and updates other profile fields');
$p=plan_import(array(source_import(array('emergency'=>array('phone'=>'invalid','contact_name'=>'Updated Contact')))));verify_import(count($p['operations'])===1&&!isset($p['operations'][0]['values']['phone'])&&count($p['issues'])===1,'invalid emergency phone staged while valid contact field updates');
$s=snapshot_import();$s['data']['gh_tenant_stays'][0]['tenant_id']=null;$s['data']['gh_tenant_stays'][0]['stay_status']='reserved';
$replacement=source_import(array('fullname'=>'New Person','identity_number'=>'5555666677778888','phone'=>'081211112222','check_in_date'=>'2026-10-15','verified_current_occupant'=>true));
$p=plan_import(array($replacement),$s);verify_import(count(array_filter($p['operations'],function($op){return $op['table']==='gh_tenant_stays';}))===2,'verified actual occupant supersedes strictly orphan reservation');
$s['data']['gh_booking_requests'][]=array('id'=>1,'status'=>'new');$p=plan_import(array($replacement),$s);verify_import(count(array_filter($p['operations'],function($op){return $op['table']==='gh_tenant_stays';}))===0,'unconverted booking protects orphan reservation');
$s['data']['gh_booking_requests']=array();$s['data']['gh_room_bills'][]=array('id'=>1,'stay_id'=>1);$p=plan_import(array($replacement),$s);verify_import(count(array_filter($p['operations'],function($op){return $op['table']==='gh_tenant_stays';}))===0,'stay-linked invoice protects orphan reservation');
$s=snapshot_import();$s['data']['gh_tenant_stays'][0]['stay_status']='reserved';$p=plan_import(array($replacement),$s);verify_import(count(array_filter($p['operations'],function($op){return $op['table']==='gh_tenant_stays';}))===0,'named reservation is never superseded');
$s=snapshot_import();$s['data']['gh_tenant_stays'][0]['check_in_date']='2026-11-01';$p=plan_import(array($replacement),$s);verify_import(count(array_filter($p['operations'],function($op){return $op['table']==='gh_tenant_stays';}))===0,'replacement cannot predate existing occupancy');
echo "PASS {$checks} tenant planner assertions\n";
