<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Explicit legacy APT split: IDs and all room-linked records remain unchanged. */
class Gulhouse_apartment_buildings
{
    public static function definitions()
    {
        return array(
            'The Nest'=>array('code'=>'APT THE NEST','name'=>'Apartemen The Nest','room_codes'=>array('E-1606','E-1607')),
            'Mejestic'=>array('code'=>'APT MEJESTIC','name'=>'Apartemen Mejestic','room_codes'=>array('L-1003')),
            'PIK 2'=>array('code'=>'APT PIK 2','name'=>'Apartemen PIK 2','room_codes'=>array('TRBP-68-59')),
            'Taman Anggrek'=>array('code'=>'APT TAMAN ANGGREK','name'=>'Apartemen Taman Anggrek','room_codes'=>array('STD-50P')),
        );
    }

    public static function apply($db)
    {
        $db->trans_begin();
        try {
            $legacy=$db->query("SELECT * FROM gh_properties WHERE code='APT' FOR UPDATE")->row_array();
            if(!$legacy||$legacy['property_type']!=='apartment')throw new RuntimeException('Properti APT tidak cocok.');
            // Lock the complete source before evaluating the explicit manifest.
            $source=$db->query('SELECT * FROM gh_rooms WHERE property_id=? FOR UPDATE',array($legacy['id']))->result_array();
            $allowed=array();foreach(self::definitions() as $def)$allowed=array_merge($allowed,$def['room_codes']);
            foreach($source as $room)if(!in_array($room['room_code'],$allowed,true))throw new RuntimeException('Ada unit APT di luar manifest; tinjau sebelum pemindahan.');
            $expenses=$db->where('property_id',$legacy['id'])->count_all_results('gh_expenses');
            if($expenses)throw new RuntimeException('Ada biaya langsung pada properti APT; perlu pemetaan sebelum pemindahan.');
            $moved=array();$properties=array();
            foreach(self::definitions() as $type=>$def) {
                $existing=$db->query('SELECT * FROM gh_properties WHERE code=? FOR UPDATE',array($def['code']))->row_array();
                if($existing&&($existing['property_type']!=='apartment'||!(int)$existing['is_active']))throw new RuntimeException('Kode gedung tujuan sudah dipakai atau nonaktif.');
                if(!$existing) {
                    if(!$db->insert('gh_properties',array('code'=>$def['code'],'name'=>$def['name'],'property_type'=>'apartment','city'=>null,'address'=>null,'is_active'=>1)))throw new RuntimeException('Gagal membuat gedung.');
                    $existing=$db->where('id',$db->insert_id())->get('gh_properties')->row_array();
                }
                $pid=(int)$existing['id'];$properties[$type]=$pid;
                foreach($def['room_codes'] as $code) {
                    $rooms=$db->query('SELECT r.*,rt.name AS split_type FROM gh_rooms r JOIN gh_room_types rt ON rt.id=r.room_type_id WHERE r.room_code=? AND r.property_id IN (?,?) FOR UPDATE',array($code,$legacy['id'],$pid))->result_array();
                    if(count($rooms)!==1||$rooms[0]['split_type']!==$type)throw new RuntimeException('Unit/tipe hilang, ganda, atau tidak cocok dengan manifest.');
                    $room=$rooms[0];
                    if((int)$room['property_id']===$pid)continue;
                    $data=array('property_id'=>$pid);if(array_key_exists('updated_at',$room))$data['updated_at']=$room['updated_at'];
                    if(!$db->where('id',$room['id'])->where('property_id',$legacy['id'])->update('gh_rooms',$data)||$db->affected_rows()!==1)throw new RuntimeException('Pemindahan unit gagal.');
                    $moved[]=(int)$room['id'];
                }
            }
            if($db->where('property_id',$legacy['id'])->count_all_results('gh_rooms')!==0)throw new RuntimeException('APT masih memiliki unit.');
            $data=array('is_active'=>0);if(array_key_exists('updated_at',$legacy))$data['updated_at']=$legacy['updated_at'];
            if(!$db->where('id',$legacy['id'])->update('gh_properties',$data)||!$db->trans_status())throw new RuntimeException('Transaksi pemisahan gagal.');
            return array('legacy_id'=>(int)$legacy['id'],'properties'=>$properties,'moved_room_ids'=>$moved,'transaction_open'=>true);
        }catch(Throwable $e){$db->trans_rollback();throw $e;}
    }
}
