<?php
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';
$rt=json_decode(file_get_contents($root.'/.e2e/mobile/bookingretain26/runtime.json'),TRUE);
if(!preg_match('/^gulhouse_test_mobile_bookingretain26_[0-9_]+$/D',$rt['schema']??'')||($rt['fixture']['contains_real_occupants']??TRUE)!==FALSE)throw new RuntimeException('Owned synthetic schema required.');
list($retention,$db)=booking_retention_cli($rt['schema']);require APPPATH.'models/Master_model.php';
class PaymentSettlementProbe extends Master_model {public $db;}
$m=new PaymentSettlementProbe();$m->db=$db;$checks=0;$suffix=bin2hex(random_bytes(6));
function settlecheck($ok,$label){global $checks;if(!$ok)throw new RuntimeException('FAIL '.$label);$checks++;echo 'PASS '.$label.PHP_EOL;}
function settleinsert($table,$row){global $db;$db->insert($table,$row);return (int)$db->insert_id();}
function settlehash($table,$where){global $db;return hash('sha256',json_encode($db->where($where)->order_by('id')->get($table)->result_array()));}
$prop=$db->get('gh_properties',1)->row_array();$type=$db->get('gh_room_types',1)->row_array();
$room=settleinsert('gh_rooms',array('property_id'=>$prop['id'],'room_type_id'=>$type['id'],'room_code'=>'SET'.$suffix,'room_label'=>'SET-'.$suffix,'monthly_price'=>1000000,'deposit'=>500000,'status'=>'reserved','is_public'=>0));
$tenant=settleinsert('gh_tenants',array('fullname'=>'Synthetic settlement '.$suffix,'phone'=>'089612345678'));
$stay=settleinsert('gh_tenant_stays',array('tenant_id'=>$tenant,'room_id'=>$room,'stay_status'=>'reserved','check_in_date'=>'2031-01-31','check_out_date'=>'2031-02-28','monthly_price'=>1000000,'deposit'=>500000));
$periods=array();foreach(array('2031-01','2031-02') as $key){$p=$db->where('period_key',$key)->get('gh_billing_periods')->row_array();$periods[]=$p?$p['id']:settleinsert('gh_billing_periods',array('period_key'=>$key,'period_label'=>'Synthetic '.$key,'period_year'=>substr($key,0,4),'period_month'=>substr($key,5,2),'source_file'=>'Synthetic'));}
$bills=array();foreach($periods as $period)$bills[]=settleinsert('gh_room_bills',array('period_id'=>$period,'room_id'=>$room,'tenant_id'=>$tenant,'stay_id'=>$stay,'base_price'=>1000000,'deposit_amount'=>500000,'bill_status'=>'unpaid','paid_total'=>900000,'due_date'=>'2031-01-31'));
$rent=settleinsert('gh_payments',array('room_bill_id'=>$bills[0],'amount'=>300000,'payment_type'=>'rent','payment_date'=>'2031-01-01','method'=>'Cash','reference_no'=>'OLD-'.$suffix,'source_key'=>'SETOLD'.$suffix));
$deposit=settleinsert('gh_payments',array('room_bill_id'=>$bills[0],'amount'=>200000,'payment_type'=>'deposit','payment_date'=>'2031-01-01','method'=>'Cash','source_key'=>'SETDEP'.$suffix));
settleinsert('gh_deposit_transactions',array('source_payment_id'=>$deposit,'tenant_id'=>$tenant,'room_id'=>$room,'stay_id'=>$stay,'transaction_date'=>'2031-01-01','transaction_type'=>'received','amount'=>200000,'method'=>'Cash'));
$q=$m->get_payment_quotes($bills);
settlecheck($q[$bills[0]]['rent_remaining']===700000,'rent quote uses actual payments rather than stale cached paid_total');
settlecheck($q[$bills[0]]['deposit_remaining']===300000&&$q[$bills[1]]['deposit_remaining']===300000,'deposit quote shares one stay obligation across monthly invoices');
$base=array('payment_id'=>0,'room_bill_id'=>$bills[0],'amount'=>700000,'payment_type'=>'rent','payment_date'=>'2031-01-02','method'=>'Transfer','request_key'=>'settle_'.bin2hex(random_bytes(16)),'notes'=>'Synthetic exact settlement');
$before=settlehash('gh_payments',array('room_bill_id'=>$bills[0]));
foreach(array(699999,700001) as $bad){$p=$base;$p['amount']=$bad;settlecheck(!$m->save_payment($p),'tampered under or overpayment rejected');}
$p=$base;$p['method']='Cash';settlecheck(!$m->save_payment($p),'new cash payment rejected');$p=$base;$p['payment_type']='other';settlecheck(!$m->save_payment($p),'new unpriced other payment rejected');
settlecheck($before===settlehash('gh_payments',array('room_bill_id'=>$bills[0])),'rejected requests leave payment history unchanged');
settlecheck($m->save_payment($base),'new rent payment settles exact remaining amount');
settlecheck($m->get_payment_quotes($bills)[$bills[0]]['rent_remaining']===0,'settlement updates remaining to zero');
$count=$db->where('room_bill_id',$bills[0])->count_all_results('gh_payments');settlecheck($m->save_payment($base)&&$db->where('room_bill_id',$bills[0])->count_all_results('gh_payments')===$count,'same request retry succeeds idempotently even when balance now zero');
$p=$base;$p['payment_type']='deposit';settlecheck(!$m->save_payment($p),'request key cannot be reused for another payment type');$p=$base;$p['room_bill_id']=$bills[1];settlecheck(!$m->save_payment($p),'request key cannot be reused across invoices');
$p=$base;$p['request_key']='settle_'.bin2hex(random_bytes(16));settlecheck(!$m->save_payment($p),'stale independent form cannot duplicate settled rent');
$p=$base;$p['request_key']='settle_'.bin2hex(random_bytes(16));$p['payment_type']='deposit';$p['amount']=300000;settlecheck($m->save_payment($p),'new deposit settles only shared stay remainder');
$afterDeposit=settlehash('gh_deposit_transactions',array('stay_id'=>$stay));$p['request_key']='settle_'.bin2hex(random_bytes(16));$p['room_bill_id']=$bills[1];settlecheck(!$m->save_payment($p),'another month cannot collect the same deposit twice');
settlecheck($afterDeposit===settlehash('gh_deposit_transactions',array('stay_id'=>$stay)),'duplicate deposit attempts do not change stay ledger');
$old=$db->where('id',$rent)->get('gh_payments')->row_array();settlecheck($m->save_payment(array('payment_id'=>$rent,'room_bill_id'=>$bills[0],'notes'=>'Synthetic updated note')),'historical payment supports notes-only update');$edited=$db->where('id',$rent)->get('gh_payments')->row_array();unset($old['notes'],$edited['notes']);settlecheck($old===$edited,'historical partial amount cash method dates and reference preserved exactly');
foreach(array('amount'=>700000,'payment_type'=>'deposit','method'=>'Transfer','payment_date'=>'2031-01-03','reference_no'=>'FORGED') as $field=>$value)settlecheck(!$m->save_payment(array('payment_id'=>$rent,'room_bill_id'=>$bills[0],'notes'=>'Tampered',$field=>$value)),'historical financial edit rejected '.$field);
$db->where('id',$stay)->update('gh_tenant_stays',array('stay_status'=>'ended'));settleinsert('gh_deposit_transactions',array('tenant_id'=>$tenant,'room_id'=>$room,'stay_id'=>$stay,'transaction_date'=>'2031-02-28','transaction_type'=>'refund','amount'=>500000,'method'=>'Transfer'));
$q=$m->get_payment_quotes($bills);settlecheck(!$q[$bills[1]]['deposit_available']&&!$q[$bills[1]]['can_pay_deposit']&&$q[$bills[1]]['deposit_reason']!=='','closed refunded stay is unavailable rather than invited to repay deposit');
$p['request_key']='settle_'.bin2hex(random_bytes(16));$p['amount']=500000;settlecheck(!$m->save_payment($p),'closed refunded stay cannot be recharged deposit');
// Imported deposit payments without ledger entries must also prevent duplicate receipts.
$db->where('id',$stay)->update('gh_tenant_stays',array('stay_status'=>'active'));$db->where('stay_id',$stay)->delete('gh_deposit_transactions');$q=$m->get_payment_quotes($bills);settlecheck($q[$bills[1]]['deposit_remaining']===0,'imported deposit payments remain counted when ledger entries are missing');
$db->where('id',$bills[1])->update('gh_room_bills',array('stay_id'=>NULL));$q=$m->get_payment_quotes($bills);settlecheck($q[$bills[1]]['rent_available']&&!$q[$bills[1]]['deposit_available']&&$q[$bills[1]]['deposit_reason']!=='','bill without proven stay supports known rent but explicitly denies ambiguous deposit');
$db->where('id',$stay)->update('gh_tenant_stays',array('deposit'=>NULL));$q=$m->get_payment_quotes($bills);settlecheck(!$q[$bills[0]]['deposit_available']&&$q[$bills[0]]['deposit_reason']!=='','unknown deposit obligation is unavailable rather than falsely settled');
echo json_encode(array('passed'=>$checks,'environment'=>'owned_synthetic','actual_data_writes'=>0)).PHP_EOL;
