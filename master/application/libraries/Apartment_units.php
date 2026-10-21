<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once __DIR__.'/Apartment_buildings.php';
class Gulhouse_apartment_units
{
    public static function merge($db,array $owned)
    {
        $db->trans_begin();
        try {
            $apt=$db->query("SELECT * FROM gh_properties WHERE code='APT' FOR UPDATE")->row_array();
            if(!$apt||$apt['property_type']!=='apartment')throw new RuntimeException('Master APT tidak cocok.');
            $changed=array();$removed=array();$locationChanges=array();
            foreach(Gulhouse_apartment_buildings::definitions() as $type=>$def) {
                $id=$owned[$type]??null;if(!is_int($id)||$id<1||$id===(int)$apt['id'])throw new RuntimeException('Manifest properti milik migrasi tidak valid.');
                $child=$db->query('SELECT * FROM gh_properties WHERE id=? FOR UPDATE',array($id))->row_array();
                if($child&&($child['code']!==$def['code']||$child['property_type']!=='apartment'))throw new RuntimeException('Identitas properti tujuan berubah.');
                if($child) {
                    foreach($db->where('property_id',$id)->get('gh_rooms')->result_array() as $r)if(!in_array($r['room_code'],$def['room_codes'],true))throw new RuntimeException('Ada unit tambahan pada properti migrasi; tinjau dahulu.');
                    foreach($db->query('SHOW TABLES')->result_array() as $item){$t=array_values($item)[0];if($t!=='gh_rooms'&&$db->field_exists('property_id',$t)&&$db->where('property_id',$id)->count_all_results($t)>0)throw new RuntimeException('Ada relasi langsung ke properti migrasi; penggabungan ditunda.');}
                }
                foreach($def['room_codes'] as $code) {
                    $rows=$db->query('SELECT r.*,rt.name AS merge_type FROM gh_rooms r JOIN gh_room_types rt ON rt.id=r.room_type_id WHERE r.room_code=? AND r.property_id IN (?,?) FOR UPDATE',array($code,$apt['id'],$id))->result_array();
                    if(count($rows)!==1||$rows[0]['merge_type']!==$type)throw new RuntimeException('Unit tidak cocok atau ganda.');
                    $room=$rows[0];$data=array('property_id'=>(int)$apt['id']);
                    foreach(array('city'=>'unit_city','address'=>'unit_address') as $parent=>$field){$value=$child?trim((string)($child[$parent]??'')):'';if($value!==''){if(trim((string)$room[$field])!==''&&trim((string)$room[$field])!==$value)throw new RuntimeException('Lokasi unit dan gedung berbeda; tidak ditimpa.');$data[$field]=$value;}}
                    if(array_key_exists('updated_at',$room))$data['updated_at']=$room['updated_at'];
                    if(!$db->where('id',$room['id'])->update('gh_rooms',$data))throw new RuntimeException('Penggabungan unit gagal.');
                    if((int)$room['property_id']!==(int)$apt['id'])$changed[]=(int)$room['id'];
                    $locationChanges[(int)$room['id']]=array('unit_city'=>$data['unit_city']??$room['unit_city'],'unit_address'=>$data['unit_address']??$room['unit_address']);
                }
                if($child){if($db->where('property_id',$id)->count_all_results('gh_rooms')!==0||!$db->where('id',$id)->where('code',$def['code'])->delete('gh_properties')||$db->affected_rows()!==1)throw new RuntimeException('Properti migrasi masih digunakan.');$removed[]=$id;}
            }
            $data=array('is_active'=>1);if(array_key_exists('updated_at',$apt))$data['updated_at']=$apt['updated_at'];
            if(!$db->where('id',$apt['id'])->update('gh_properties',$data)||!$db->trans_status())throw new RuntimeException('Penggabungan gagal.');
            return array('apt_id'=>(int)$apt['id'],'moved_room_ids'=>$changed,'removed_owned_properties'=>$removed,'locations'=>$locationChanges);
        }catch(Throwable $e){$db->trans_rollback();throw $e;}
    }
}
