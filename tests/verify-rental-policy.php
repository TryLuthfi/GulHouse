<?php
define('BASEPATH',dirname(__DIR__).'/master/system/');define('APPPATH',dirname(__DIR__).'/master/application/');class CI_Model{}
require APPPATH.'models/Billing_policy_model.php';
$checks=0;function polcheck($ok,$label){global $checks;if(!$ok)throw new RuntimeException('FAIL '.$label);$checks++;echo 'PASS '.$label.PHP_EOL;}
$bill=array('id'=>17,'base_price'=>1000000,'due_date'=>'2026-11-20');
foreach(array('2026-11-18'=>0,'2026-11-20'=>0,'2026-11-21'=>0,'2026-11-22'=>0,'2026-11-23'=>100000,'2026-11-24'=>200000,'2026-11-27'=>500000) as $as=>$amount){$r=Billing_policy_model::assess($bill,array(),$as);polcheck($r['penalty_accrued']===$amount,'grace and daily penalty '.$as);polcheck($r['payment_deadline']==='2026-11-18','H-2 deadline stays separate from due date');}
$r=Billing_policy_model::assess($bill,array(),'2026-11-27');polcheck($r['takeout_review_required']&&$r['rent_outstanding']===1000000,'H+7 produces review flag with retained rent debt');
$r=Billing_policy_model::assess($bill,array(),'2026-11-26');polcheck(!$r['takeout_review_required'],'before H+7 no takeout flag');
$p=array(array('id'=>1,'payment_type'=>'rent','payment_date'=>'2026-11-21','amount'=>500000));$r=Billing_policy_model::assess($bill,$p,'2026-11-27');polcheck($r['penalty_accrued']===500000&&$r['rent_outstanding']===500000,'partial rent does not stop accrual');
$p[]=array('id'=>2,'payment_type'=>'rent','payment_date'=>'2026-11-24','amount'=>500000);$r=Billing_policy_model::assess($bill,$p,'2026-12-20');polcheck($r['penalty_accrued']===200000&&$r['rent_settled_date']==='2026-11-24'&&!$r['takeout_review_required'],'actual full settlement date freezes accrual');
$p=array(array('id'=>1,'payment_type'=>'deposit','payment_date'=>'2026-11-20','amount'=>1000000));$r=Billing_policy_model::assess($bill,$p,'2026-11-27');polcheck($r['rent_outstanding']===1000000&&$r['penalty_accrued']===500000,'deposit never counts as rent settlement');
$p=array(array('id'=>1,'payment_type'=>'rent','payment_date'=>'2026-12-01','amount'=>1000000));$r=Billing_policy_model::assess($bill,$p,'2026-11-27');polcheck($r['rent_outstanding']===1000000,'future dated payment cannot settle historical assessment');
$r=Billing_policy_model::assess($bill,array(),'2026-12-20','2026-11-27');polcheck($r['penalty_accrued']===500000&&$r['accrual_stopped_at']==='2026-11-27'&&!$r['takeout_review_required'],'actual physical exit freezes fee accrual without waiving debt');
polcheck(Billing_policy_model::boundary('2031-01-31',1)==='2031-02-28'&&Billing_policy_model::boundary('2031-01-31',2)==='2031-03-31','month-end clamp recovers original day');
polcheck(Billing_policy_model::boundary('2032-01-31',1)==='2032-02-29','leap year clamp');
polcheck(Billing_policy_model::due_in_period('2031-01-31','2031-03')==='2031-03-31','future bills use original anchor not previous clamp');
polcheck(Billing_policy_model::boundary('2031-02-30',1)===''&&Billing_policy_model::due_in_period('2031-01-31','2030-12')==='','invalid and preceding periods rejected');
echo json_encode(array('passed'=>$checks,'database_connections'=>0,'writes'=>0)).PHP_EOL;
