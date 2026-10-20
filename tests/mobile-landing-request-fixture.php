<?php
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';
$dir=$root.'/.e2e/mobile/bookingretain26';$rt=json_decode(file_get_contents($dir.'/runtime.json'),true);
if(!preg_match('/^gulhouse_test_mobile_bookingretain26_[0-9_]+$/D',$rt['schema']??'')||($rt['fixture']['contains_real_occupants']??true)!==false)throw new RuntimeException('Synthetic schema required');
list($model,$db)=booking_retention_cli($rt['schema']);$file=$dir.'/ciko-landing-ui-stock-private.json';$mode=$argv[1]??'inspect';
if($mode==='setup'){
 if(is_file($file))throw new RuntimeException('Restore old owned snapshot first');
 $groups=$db->query('SELECT r.property_id,r.room_type_id,p.code,rt.name,COUNT(*) n,SUM(r.status="available" AND r.is_public=1 AND r.monthly_price IS NOT NULL) available FROM gh_rooms r JOIN gh_properties p ON p.id=r.property_id JOIN gh_room_types rt ON rt.id=r.room_type_id GROUP BY r.property_id,r.room_type_id HAVING available>0 ORDER BY n,property_id,room_type_id')->result_array();
 if(count($groups)<2)throw new RuntimeException('Need another available type preserved');$g=$groups[0];$rows=$db->select('id,status')->where('property_id',$g['property_id'])->where('room_type_id',$g['room_type_id'])->order_by('id')->get('gh_rooms')->result_array();
 $snap=array('schema'=>$rt['schema'],'group'=>$g,'rows'=>$rows,'before_hash'=>hash('sha256',json_encode($rows)));file_put_contents($file,json_encode($snap));
 foreach($rows as $r)$db->where('id',$r['id'])->update('gh_rooms',array('status'=>'maintenance'));
 echo json_encode(array('stock_zero_label'=>$g['code'].' - '.$g['name'],'room_count'=>count($rows)));exit;
}
if($mode==='restore'){
 if(!is_file($file)){echo 'No owned snapshot';exit;}$s=json_decode(file_get_contents($file),true);if($s['schema']!==$rt['schema'])throw new RuntimeException('Snapshot schema differs');
 foreach($s['rows'] as $r)$db->where('id',$r['id'])->update('gh_rooms',array('status'=>$r['status']));
 $rows=$db->select('id,status')->where('property_id',$s['group']['property_id'])->where('room_type_id',$s['group']['room_type_id'])->order_by('id')->get('gh_rooms')->result_array();if(hash('sha256',json_encode($rows))!==$s['before_hash'])throw new RuntimeException('Restored hash mismatch');
 file_put_contents($dir.'/ciko-landing-ui-stock-restored.json',json_encode(array('restored'=>true,'hash_matches'=>true,'room_count'=>count($rows))));unlink($file);echo 'Owned synthetic room status snapshot restored with matching hash';exit;
}
echo json_encode($db->query('SELECT p.code,rt.name,COUNT(*) total,SUM(r.status="available" AND r.is_public=1 AND r.monthly_price IS NOT NULL) available FROM gh_rooms r JOIN gh_properties p ON p.id=r.property_id JOIN gh_room_types rt ON rt.id=r.room_type_id GROUP BY r.property_id,r.room_type_id')->result_array());
