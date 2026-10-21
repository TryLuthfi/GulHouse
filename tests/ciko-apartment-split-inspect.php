<?php
// Independent read-only inventory; no business row writes.
require dirname(__DIR__).'/scripts/booking-retention-bootstrap.php';
list($unused,$db)=booking_retention_cli('gulhouse_db');
$dir=$root.'/.e2e/apartment-split-20261021';if(!is_dir($dir))mkdir($dir,0700,true);
$out=array('schema'=>$db->database,'tables'=>array(),'reference_columns'=>array());
foreach($db->query('SHOW TABLES')->result_array() as $row){$t=array_values($row)[0];$rows=$db->get($t)->result_array();$encoded=array_map('json_encode',$rows);sort($encoded,SORT_STRING);$out['tables'][$t]=array('rows'=>count($rows),'sha256'=>hash('sha256',json_encode($encoded)));foreach($db->field_data($t) as $f)if(in_array($f->name,array('property_id','property_code','room_id'),true))$out['reference_columns'][$t][]=$f->name;}
$out['apartments']=$db->select('r.id,r.property_id,r.room_type_id,r.room_code,r.room_label,r.status,r.is_public,r.monthly_price,r.deposit,r.created_at,r.updated_at,rt.name AS type_name')->from('gh_rooms r')->join('gh_properties p','p.id=r.property_id')->join('gh_room_types rt','rt.id=r.room_type_id')->where('p.property_type','apartment')->order_by('r.id')->get()->result_array();
$ids=array_column($out['apartments'],'id');$out['direct_expenses']=$db->where('property_id',4)->count_all_results('gh_expenses');$out['room_expenses']=$db->where_in('room_id',$ids)->count_all_results('gh_expenses');$out['legacy_apt_rows']=$db->where('property_code','APT')->count_all_results('gh_room_database');
$out['room_indexes']=$db->query('SHOW INDEX FROM gh_rooms')->result_array();
$out['engines']=$db->query("SELECT TABLE_NAME,ENGINE FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('gh_properties','gh_rooms','gh_expenses')")->result_array();
$file=$dir.'/ciko-before.json';if(is_file($file))throw new RuntimeException('Baseline exists; will not overwrite');file_put_contents($file,json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo json_encode(array('tables'=>count($out['tables']),'apartments'=>count($out['apartments']),'direct_expenses'=>$out['direct_expenses'],'room_expenses'=>$out['room_expenses'],'legacy_apt_rows'=>$out['legacy_apt_rows'],'engines'=>$out['engines'],'evidence'=>$file));
