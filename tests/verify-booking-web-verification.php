<?php
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';
$runtime=json_decode(file_get_contents($root.'/.e2e/mobile/bookingretain26/runtime.json'),TRUE);
if(!preg_match('/^gulhouse_test_mobile_bookingretain26_[0-9_]+$/D',$runtime['schema']??''))throw new RuntimeException('Owned isolated schema required.');
list($m,$db)=booking_retention_cli($runtime['schema']);require APPPATH.'models/Operations_model.php';$ops=new Operations_model();$ops->db=$db;
$checks=0;function wcheck($ok,$name){global $checks;if(!$ok)throw new RuntimeException('FAIL '.$name);$checks++;echo 'PASS '.$name.PHP_EOL;}
function wreject($fn,$name){try{$fn();}catch(Throwable $e){wcheck(TRUE,$name);return;}wcheck(FALSE,$name);}
function wpayload($p){return array('confirmed_demo'=>'1','attestation'=>'I reviewed this exact synthetic fixture and all descendants; no real occupant data.','verification_hash'=>$p['verification']['hash'],'verification_token'=>$p['verification']['token']);}
$suffix=bin2hex(random_bytes(6));$db->insert('gh_admin_users',array('fullname'=>'Web retention '.$suffix,'username'=>'webret_'.$suffix,'password_hash'=>password_hash('SyntheticOnly123!',PASSWORD_DEFAULT),'role'=>'super_admin','is_active'=>1));$actor=(int)$db->insert_id();
$db->insert('gh_booking_requests',array('fullname'=>'Web fresh '.$suffix,'phone'=>'085'.$suffix,'room_interest'=>'Synthetic','source'=>'web verification fixture','status'=>'new'));$fresh=(int)$db->insert_id();
$queryStart=count($db->queries);$p=$m->preview($fresh,$actor);$readOnly=TRUE;foreach(array_slice($db->queries,$queryStart) as $sql)if(preg_match('/^\s*(UPDATE|INSERT|DELETE|REPLACE|ALTER|CREATE|DROP)\b/i',$sql))$readOnly=FALSE;
wcheck($readOnly && !$m->registered($fresh),'GET-style preview performs no mutating SQL or registration');
wcheck($p['can_verify_demo']&&!$p['is_demo']&&!$p['can_purge'],'fresh candidate available for human verification, never classified automatically');
$payload=wpayload($p);$bad=$payload;unset($bad['confirmed_demo']);wreject(function()use($m,$fresh,$actor,$bad){$m->verify_demo($fresh,$actor,$bad);},'explicit demo attestation checkbox required');
$bad=$payload;$bad['verification_hash']=$p['plan_hash'];$bad['verification_token']=$p['review_token'];wreject(function()use($m,$fresh,$actor,$bad){$m->verify_demo($fresh,$actor,$bad);},'purge review token cannot authorize verification');
wreject(function()use($m,$fresh,$actor,$payload){$m->verify_demo($fresh,$actor+999999,$payload);},'verification actor identity bound and fresh-authorized');
$db->where('id',$fresh)->update('gh_booking_requests',array('message'=>'Synthetic changed since preview'));wreject(function()use($m,$fresh,$actor,$payload){$m->verify_demo($fresh,$actor,$payload);},'stale verification graph rejected');
$p=$m->preview($fresh,$actor);$m->verify_demo($fresh,$actor,wpayload($p));$b=$ops->get_row('bookings',$fresh);
wcheck($m->registered($fresh)['registration_kind']==='web_fresh' && !$b['archived_at'] && $b['status']==='new','web verification only registers provenance, never archives or deletes');
wreject(function()use($m,$fresh,$actor,$p){$m->verify_demo($fresh,$actor,wpayload($p));},'verified candidate cannot be registered twice');
wcheck(!$m->preview($fresh,$actor)['can_verify_demo'],'registered case hides verification action');
$db->insert('gh_booking_requests',array('fullname'=>'Web legacy '.$suffix,'phone'=>'084'.$suffix,'room_interest'=>'Synthetic','source'=>'web verification fixture','status'=>'new'));$legacy=(int)$db->insert_id();$room=$db->where('status','available')->get('gh_rooms')->row_array();
wcheck($ops->convert_booking($legacy,array('room_id'=>$room['id'],'start_date'=>'2044-03-01','end_date'=>'2044-03-31','stay_status'=>'reserved')),'legacy fixture converted with real operational method');
$t=$db->where('source_booking_id',$legacy)->get('gh_tenants')->row_array();$s=$db->where('source_booking_id',$legacy)->get('gh_tenant_stays')->row_array();$c=$db->where('source_booking_id',$legacy)->get('gh_contracts')->row_array();
foreach(array('gh_tenants'=>$t,'gh_tenant_stays'=>$s,'gh_contracts'=>$c) as $table=>$r)$db->where('id',$r['id'])->update($table,array('source_booking_id'=>NULL,'updated_at'=>$r['updated_at']));
$before=$db->where('id',$t['id'])->get('gh_tenants')->row_array();$p=$m->preview($legacy,$actor);
wcheck($p['can_verify_demo']&&($p['verification']['counts']['gh_contract_documents']??0)>0,'unique exact legacy hints expose full candidate descendant scope');
wcheck($db->where('id',$t['id'])->get('gh_tenants')->row_array()===$before && !$m->registered($legacy),'legacy candidate discovery leaves original rows untouched');
$db->where('id',$t['id'])->update('gh_tenants',array('source_label'=>'Booking #'.$legacy.' copied'));
wcheck(!$m->preview($legacy,$actor)['can_verify_demo'],'nonexact source label never establishes candidate');
$db->where('id',$t['id'])->update('gh_tenants',array('source_label'=>'Booking #'.$legacy,'updated_at'=>$before['updated_at']));
$copy=$c;unset($copy['id']);$copy['source_booking_id']=NULL;$copy['contract_no']='WEB-DUP-'.$suffix;$db->insert('gh_contracts',$copy);$duplicate=(int)$db->insert_id();
wcheck(!$m->preview($legacy,$actor)['can_verify_demo'],'multiple exact contract candidates block human verification');$db->where('id',$duplicate)->delete('gh_contracts');
$db->insert('gh_tenant_stays',array('tenant_id'=>$t['id'],'room_id'=>$room['id'],'stay_status'=>'ended','check_in_date'=>'2040-01-01','check_out_date'=>'2040-01-31'));$shared=(int)$db->insert_id();
wcheck(!$m->preview($legacy,$actor)['can_verify_demo'],'shared tenant graph prevents legacy adoption');$db->where('id',$shared)->delete('gh_tenant_stays');
$p=$m->preview($legacy,$actor);$db->where('id',$actor)->update('gh_admin_users',array('role'=>'admin'));
wreject(function()use($m,$legacy,$actor,$p){$m->verify_demo($legacy,$actor,wpayload($p));},'role downgrade blocks verification despite issued token');$db->where('id',$actor)->update('gh_admin_users',array('role'=>'super_admin'));
$p=$m->preview($legacy,$actor);$m->verify_demo($legacy,$actor,wpayload($p));
wcheck($m->registered($legacy)['registration_kind']==='web_legacy','human-approved exclusive legacy graph becomes trusted registration');
$roots=TRUE;foreach(array('gh_tenants'=>$t,'gh_tenant_stays'=>$s,'gh_contracts'=>$c) as $table=>$r){$after=$db->where('id',$r['id'])->get($table)->row_array();$roots=$roots&&(int)$after['source_booking_id']===$legacy&&$after['updated_at']===$r['updated_at'];}
wcheck($roots,'web legacy binding persists exact roots and preserves historical timestamps');
wcheck((bool)$ops->get_row('bookings',$legacy) && !$ops->get_row('bookings',$legacy)['archived_at'],'legacy web verification performs no archive or purge');
$m->archive($legacy,$actor,array('reason'=>'Synthetic fixture archive after verification','confirmed'=>'1'));$p=$m->preview($legacy,$actor);
wcheck($p['can_purge'],'separate archive then existing purge review remains operational');
// Only synthetic rows created by this test. Keep audit/registry as evidence; disable temporary actor.
$db->where('id',$actor)->update('gh_admin_users',array('is_active'=>0));
echo json_encode(array('passed'=>$checks,'environment'=>'isolated_synthetic','actual_user_booking_touched'=>FALSE)).PHP_EOL;
