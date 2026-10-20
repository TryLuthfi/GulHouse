<?php
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';
$rt=json_decode(file_get_contents($root.'/.e2e/mobile/bookingretain26/runtime.json'),TRUE);
if(!preg_match('/^gulhouse_test_mobile_bookingretain26_[0-9_]+$/D',$rt['schema']??'')||($rt['fixture']['contains_real_occupants']??TRUE)!==FALSE)throw new RuntimeException('Synthetic schema required');
list($retention,$db)=booking_retention_cli($rt['schema']);require APPPATH.'models/Resident_access_model.php';
$access=new Resident_access_model();$access->db=$db;
$access->session=new class {public $data=array();function userdata($key){return $this->data[$key]??NULL;}function set_userdata($key,$value){$this->data[$key]=$value;}function unset_userdata($keys){foreach((array)$keys as $key)unset($this->data[$key]);}function sess_regenerate($destroy){}};
$checks=0;function access_check($ok,$name){global $checks;if(!$ok)throw new RuntimeException('FAIL '.$name);$checks++;echo 'PASS '.$name.PHP_EOL;}
$marker='AUTHUNIT_'.bin2hex(random_bytes(5));$code=bin2hex(random_bytes(32));$otherCode=bin2hex(random_bytes(32));
$phone='081234567890';$template=$db->get('gh_rooms')->row_array();unset($template['id']);$template['room_code']=$marker;$template['room_label']=$marker;$template['status']='reserved';$db->insert('gh_rooms',$template);$room=$db->insert_id();
$db->insert('gh_tenants',array('fullname'=>$marker,'phone'=>$phone,'source_label'=>'Synthetic access unit'));$tenant=$db->insert_id();
$db->insert('gh_tenant_stays',array('tenant_id'=>$tenant,'room_id'=>$room,'stay_status'=>'reserved','check_in_date'=>'2027-01-31','check_out_date'=>'2027-02-28','monthly_price'=>1000000,'deposit'=>500000));$stay=$db->insert_id();
$db->insert('gh_resident_cases',array('full_name'=>$marker,'phone'=>$phone,'room_id'=>$room,'planned_start'=>'2027-01-31','source'=>'website','tenant_id'=>$tenant,'stay_id'=>$stay,'access_hash'=>hash('sha256',$code)));$id=$db->insert_id();
$db->insert('gh_resident_cases',array('full_name'=>$marker.' other','phone'=>$phone,'room_id'=>$room,'planned_start'=>'2027-01-31','source'=>'website','access_hash'=>hash('sha256',$otherCode)));$other=$db->insert_id();
$db->insert('gh_admin_users',array('fullname'=>$marker,'username'=>$marker,'password_hash'=>password_hash(bin2hex(random_bytes(20)),PASSWORD_DEFAULT),'role'=>'super_admin','is_active'=>1));$actor=$db->insert_id();
foreach(array(array('resident',$id),array('payment',$stay),array('resident',$other)) as $subject)$db->insert('gh_mobile_tokens',array('token_hash'=>hash('sha256',random_bytes(32)),'subject_kind'=>$subject[0],'subject_id'=>$subject[1],'expires_at'=>date('Y-m-d H:i:s',time()+86400),'device_label'=>$marker));
$case=$db->where('id',$id)->get('gh_resident_cases')->row_array();
access_check(Resident_access_model::phone('082345678901')==='6282345678901'&&Resident_access_model::phone('+62082345678901')==='6282345678901','local and malformed country-prefix normalization');
access_check(!$access->credentials($phone,'wrong')&&!$access->credentials('089999999999',$code),'wrong code or phone rejected');
access_check((int)$access->credentials('+62 (812) 3456-7890',$code)['id']===$id,'phone plus private code resolves exact case');
access_check((int)$access->credentials($phone,$otherCode)['id']===$other,'shared phone never chooses a case without its own code');
$access->establish($case);access_check((int)$access->current()['id']===$id,'new proof-bound web session authenticates');
$access->session->data['gh_resident_case']=array('id'=>$id,'verified_at'=>time());access_check($access->current()===NULL,'legacy unbound sessions cannot bypass unified login');
$access->establish($case);$access->session->data['gh_resident_case']['verified_at']=time()-3601;access_check($access->current()===NULL,'expired portal session rejected');
$access->establish($case);$historyBefore=$db->where('id',$stay)->get('gh_tenant_stays')->row_array();$roomBefore=$db->where('id',$room)->get('gh_rooms')->row_array();
try{$access->reset($id,$actor,'short','1');access_check(FALSE,'reason required');}catch(RuntimeException $e){access_check(TRUE,'short reason rejected');}
try{$access->reset($id,$actor,'Verified synthetic identity reset','0');access_check(FALSE,'identity required');}catch(RuntimeException $e){access_check(TRUE,'identity confirmation required');}
$db->where('id',$actor)->update('gh_admin_users',array('role'=>'admin'));
try{$access->reset($id,$actor,'Verified synthetic identity reset','1');access_check(FALSE,'role required');}catch(RuntimeException $e){access_check(TRUE,'current database role overrides stale super-admin claims');}
$db->where('id',$actor)->update('gh_admin_users',array('role'=>'super_admin'));
$new=$access->reset($id,$actor,'Verified synthetic identity reset','1');
access_check(preg_match('/^[a-f0-9]{64}$/D',$new)&&$new!==$code,'reset returns fresh high-entropy private code');
access_check(!$access->credentials($phone,$code)&&(int)$access->credentials($phone,$new)['id']===$id,'old code invalid and new code valid');
access_check($access->current()===NULL,'prior authenticated web session invalidated by rotation');
access_check($db->where('subject_kind','resident')->where('subject_id',$id)->where('revoked_at',NULL)->count_all_results('gh_mobile_tokens')===0&&$db->where('subject_kind','payment')->where('subject_id',$stay)->where('revoked_at',NULL)->count_all_results('gh_mobile_tokens')===0,'resident and payment API sessions revoked');
access_check($db->where('subject_kind','resident')->where('subject_id',$other)->where('revoked_at',NULL)->count_all_results('gh_mobile_tokens')===1,'another case sharing phone retains its own session');
$after=$db->where('id',$id)->get('gh_resident_cases')->row_array();$before=$case;unset($after['access_hash'],$after['updated_at'],$before['access_hash'],$before['updated_at']);
access_check($after===$before&&$db->where('id',$stay)->get('gh_tenant_stays')->row_array()===$historyBefore&&$db->where('id',$room)->get('gh_rooms')->row_array()===$roomBefore,'reset preserves resident fields stay room and rental history');
$events=$db->where('case_id',$id)->where('event_type','access_reset')->get('gh_resident_events')->result_array();
access_check(count($events)===1&&strpos(json_encode($events),$new)===FALSE&&strpos(json_encode($events),hash('sha256',$new))===FALSE,'audit records actor and reason without credentials');
echo json_encode(array('passed'=>$checks,'environment'=>'owned_synthetic','real_resident_resets'=>0)).PHP_EOL;
