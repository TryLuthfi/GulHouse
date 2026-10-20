<?php
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';
$runtime=json_decode(file_get_contents($root.'/.e2e/mobile/bookingretain26/runtime.json'),TRUE);if(!preg_match('/^gulhouse_test_mobile_bookingretain26_[0-9_]+$/D',$runtime['schema']??''))throw new RuntimeException('Owned test schema required.');
list($m,$db)=booking_retention_cli($runtime['schema']);require APPPATH.'models/Operations_model.php';$ops=new Operations_model();$ops->db=$db;
$checks=0;function donecheck($ok,$label){global $checks;if(!$ok)throw new RuntimeException('FAIL '.$label);$checks++;echo 'PASS '.$label.PHP_EOL;}
$suffix=bin2hex(random_bytes(6));$db->insert('gh_booking_requests',array('fullname'=>'Done fixture '.$suffix,'phone'=>'082312345678','room_interest'=>'Synthetic','created_at'=>date('Y-m-d H:i:s'),'status'=>'new'));$id=(int)$db->insert_id();
$tables=array('gh_tenants','gh_tenant_stays','gh_contracts','gh_room_bills','gh_payments','gh_deposit_transactions','gh_rooms','gh_resident_cases');$before=array();foreach($tables as $table)$before[$table]=hash('sha256',json_encode($db->order_by('id')->get($table)->result_array()));
donecheck($ops->convert_booking($id,array('room_id'=>1,'start_date'=>'2099-01-01','end_date'=>'2099-02-01','stay_status'=>'active')),'legacy conversion method now completes lead');
donecheck($db->where('id',$id)->get('gh_booking_requests')->row_array()['status']==='done','status Done persisted');
$same=TRUE;foreach($tables as $table)$same=$same&&$before[$table]===hash('sha256',json_encode($db->order_by('id')->get($table)->result_array()));donecheck($same,'completion leaves all tenant stay contract bill payment deposit room case bytes unchanged');
donecheck($ops->convert_booking($id,array()),'repeat completion idempotent');donecheck(!$ops->update_booking_status($id,'survey'),'Done is distinct terminal outcome');
$db->where('id',$id)->update('gh_booking_requests',array('status'=>'converted'));$legacy=$db->where('id',$id)->get('gh_booking_requests')->row_array();donecheck(!$ops->convert_booking($id,array())&&$db->where('id',$id)->get('gh_booking_requests')->row_array()===$legacy,'legacy converted record remains immutable and retains meaning');
$db->where('id',$id)->update('gh_booking_requests',array('status'=>'new','archived_at'=>date('Y-m-d H:i:s')));donecheck(!$ops->convert_booking($id,array()),'archived lead cannot be completed');
foreach(array('082312345678','+6282312345678','+62082312345678','62082312345678','6282312345678','82312345678','0823 1234-5678') as $number)donecheck(gh_booking_whatsapp_url($number)==='https://wa.me/6282312345678','Indonesian WhatsApp normalization without storage edit');
foreach(array('','abc082312345678','1234','+33123456789','++6282312345678','62+82312345678') as $number)donecheck(gh_booking_whatsapp_url($number)==='','invalid WhatsApp destination blocked');
echo json_encode(array('passed'=>$checks,'environment'=>'isolated_synthetic','actual_data_writes'=>0)).PHP_EOL;
