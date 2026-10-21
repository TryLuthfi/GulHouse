<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Shared validation for property and apartment-unit administrative regions. */
class Gulhouse_region_location
{
    public static function fields()
    {
        return array('region_province_id','region_regency_id','region_district_id');
    }

    public static function catalog($db)
    {
        $out=array('provinces'=>array(),'regencies'=>array(),'districts'=>array());
        foreach(array('provinces','regencies','districts') as $level) {
            if (!$db->table_exists('gh_region_'.$level)) return $out;
            $out[$level]=$db->order_by('name','ASC')->get('gh_region_'.$level)->result_array();
        }
        return $out;
    }

    public static function resolve($db,array $payload,array $existing,$cityField)
    {
        $ids=array();$provided=false;
        foreach(self::fields() as $field) {
            if (array_key_exists($field,$payload)) $provided=true;
            $value=$payload[$field]??'';
            if (!is_scalar($value) && $value!==null) throw new InvalidArgumentException('Pilihan wilayah tidak valid.');
            $ids[$field]=trim((string)$value);
        }
        // Empty legacy forms and older clients must not clear existing locations.
        if (!$provided || !array_filter($ids,static function($v){return $v!=='';})) {
            if (!empty($existing['region_district_id'])) {
                $saved=array_intersect_key($existing,array_flip(self::fields()));
                return self::resolve($db,$saved,array(),$cityField);
            }
            return array();
        }
        foreach(array(2,4,7) as $index=>$length) {
            $field=self::fields()[$index];
            if (!preg_match('/^[0-9]{'.$length.'}$/D',$ids[$field])) throw new InvalidArgumentException('Pilih provinsi, kabupaten/kota, dan kecamatan secara lengkap.');
        }
        if (!$db->table_exists('gh_region_districts')) throw new InvalidArgumentException('Master wilayah belum tersedia.');
        $province=$db->where('id',$ids['region_province_id'])->get('gh_region_provinces')->row_array();
        $regency=$db->where('id',$ids['region_regency_id'])->get('gh_region_regencies')->row_array();
        $district=$db->where('id',$ids['region_district_id'])->get('gh_region_districts')->row_array();
        if (!$province || !$regency || !$district || $regency['province_id']!==$province['id'] || $district['regency_id']!==$regency['id']) {
            throw new InvalidArgumentException('Kecamatan harus sesuai kabupaten/kota dan provinsi yang dipilih.');
        }
        $ids[$cityField]=mb_convert_case($regency['name'],MB_CASE_TITLE,'UTF-8');
        return $ids;
    }
}
