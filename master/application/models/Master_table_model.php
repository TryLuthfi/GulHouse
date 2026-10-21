<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Read-only listing queries. Summary and page use the same filtered SQL source. */
class Master_table_model extends CI_Model
{
    public function sorts($resource)
    {
        $all=array(
            'properties'=>array('code'=>'Kode','name'=>'Nama','city'=>'Kota','status'=>'Status'),
            'room-types'=>array('name'=>'Nama','status'=>'Status','rooms'=>'Jumlah kamar'),
            'rooms'=>array('property'=>'Properti','code'=>'Kode kamar','type'=>'Tipe','price'=>'Harga','status'=>'Status'),
            'tenants'=>array('status'=>'Status tinggal','name'=>'Nama','property'=>'Properti','room'=>'Kamar','check_in'=>'Tanggal masuk','price'=>'Harga'),
        );
        if (!isset($all[$resource])) throw new InvalidArgumentException('Tabel master tidak dikenal.');
        return $all[$resource];
    }

    public function page($resource,array $filters=array(),array $options=array())
    {
        $allowed=$this->sorts($resource);
        $size=isset($options['per_page'])&&is_scalar($options['per_page']) ? (string)$options['per_page'] : '';
        $size=in_array($size,array('10','25','50','100'),true)?(int)$size:10;
        $requested=isset($options['page'])&&is_scalar($options['page'])&&preg_match('/^[0-9]+$/D',(string)$options['page']) ? max(1,(int)$options['page']) : 1;
        $sort=isset($options['sort'])&&is_string($options['sort'])&&isset($allowed[$options['sort']])?$options['sort']:array_keys($allowed)[0];
        $dir=isset($options['dir'])&&is_string($options['dir'])&&strtolower($options['dir'])==='desc'?'desc':'asc';
        $base=$this->source($resource,$filters);
        $metrics=array(
            'properties'=>'COUNT(*) AS total, COALESCE(SUM(is_active=1),0) AS active, COALESCE(SUM(property_type="house"),0) AS house, COALESCE(SUM(property_type="apartment"),0) AS apartment',
            'room-types'=>'COUNT(*) AS total, COALESCE(SUM(is_active=1),0) AS active, COALESCE(SUM(room_count>0),0) AS used, COALESCE(SUM(room_count),0) AS rooms',
            'rooms'=>'COUNT(*) AS total, COALESCE(SUM(status="available"),0) AS available, COALESCE(SUM(status="occupied"),0) AS occupied, COALESCE(SUM(is_public=1),0) AS public',
            'tenants'=>'COUNT(*) AS total, COALESCE(SUM(stay_status="active"),0) AS active, COALESCE(SUM(stay_status="reserved"),0) AS reserved, COALESCE(SUM(stay_status="internal"),0) AS internal, COALESCE(SUM(stay_status="empty"),0) AS empty, COALESCE(SUM(stay_status="ended"),0) AS ended',
        );
        $summary=$this->db->query('SELECT '.$metrics[$resource].' FROM ('.$base.') AS src')->row_array();
        $summary=array_map('intval',$summary);$total=$summary['total'];$pages=max(1,(int)ceil($total/$size));$page=min($requested,$pages);$offset=($page-1)*$size;
        $columns=array(
            'properties'=>array('code'=>'code','name'=>'name','city'=>'city','status'=>'is_active'),
            'room-types'=>array('name'=>'name','status'=>'is_active','rooms'=>'room_count'),
            'rooms'=>array('property'=>'property_code','code'=>'room_code','type'=>'type_name','price'=>'monthly_price','status'=>'status'),
            'tenants'=>array('status'=>"FIELD(stay_status,'active','reserved','internal','empty','ended')",'name'=>'fullname','property'=>'property_code','room'=>'room_code','check_in'=>'check_in_date','price'=>'monthly_price'),
        );
        $order=$columns[$resource][$sort].' '.strtoupper($dir);
        if($resource==='rooms'&&$sort==='property')$order.=', room_code ASC';
        if($resource==='tenants'&&$sort==='status')$order.=', property_code ASC, room_code ASC';
        $order.=', id ASC';
        $rows=$this->db->query('SELECT * FROM ('.$base.') AS src ORDER BY '.$order.' LIMIT '.$size.' OFFSET '.$offset)->result_array();
        return array('rows'=>$rows,'summary'=>$summary,'allowed_sorts'=>$allowed,'pagination'=>array('total'=>$total,'page'=>$page,'per_page'=>$size,'pages'=>$pages,'from'=>$total?$offset+1:0,'to'=>$total?$offset+count($rows):0,'sort'=>$sort,'dir'=>$dir));
    }

    public function find($resource,$id)
    {
        $this->sorts($resource);
        if(!is_scalar($id)||!preg_match('/^[1-9][0-9]*$/D',(string)$id))return null;
        $base=$this->source($resource,array(),(int)$id,true);
        return $this->db->query('SELECT * FROM ('.$base.') AS src LIMIT 1')->row_array();
    }

    public function room_filter_catalog()
    {
        return $this->db->select('property_id,room_type_id')->distinct()->get('gh_rooms')->result_array();
    }

    private function source($resource,array $filters,$id=null,$detail=false)
    {
        // Invalid structured inputs are ignored, never interpolated as SQL.
        foreach($filters as $key=>$value) if(!is_scalar($value)&&$value!==null)$filters[$key]='';
        $q=trim((string)($filters['q']??''));
        if($resource==='properties') {
            $this->db->select('p.*')->from('gh_properties p');$identity='p.id';
            if(in_array($filters['type']??'',array('house','apartment','other'),true))$this->db->where('p.property_type',$filters['type']);
            $this->active_filter('p.is_active',$filters['status']??'');
            $search=array('p.code','p.name','p.city','p.address');
        } elseif($resource==='room-types') {
            $this->db->select('rt.*')->select('COUNT(r.id) AS room_count,COUNT(DISTINCT p.id) AS property_count,GROUP_CONCAT(DISTINCT p.code ORDER BY p.code SEPARATOR ", ") AS property_codes,GROUP_CONCAT(DISTINCT p.property_type ORDER BY p.property_type SEPARATOR ", ") AS property_types',false)->from('gh_room_types rt')->join('gh_rooms r','r.room_type_id=rt.id','left')->join('gh_properties p','p.id=r.property_id','left')->group_by('rt.id');$identity='rt.id';
            if(in_array($filters['property_type']??'',array('house','apartment','other'),true))$this->db->where('p.property_type',$filters['property_type']);
            $this->active_filter('rt.is_active',$filters['status']??'');
            if(($filters['usage']??'')==='used')$this->db->having('room_count >',0);
            $search=array('rt.name','rt.description','p.code');
        } elseif($resource==='rooms') {
            $this->db->select('r.*,p.code AS property_code,rt.name AS type_name')->from('gh_rooms r')->join('gh_properties p','p.id=r.property_id','left')->join('gh_room_types rt','rt.id=r.room_type_id','left');$identity='r.id';
            $this->number_filter($filters,'property_id','r.property_id');$this->number_filter($filters,'room_type_id','r.room_type_id');
            if(in_array($filters['status']??'',array('available','occupied','reserved','maintenance','mess','inactive'),true))$this->db->where('r.status',$filters['status']);
            if(in_array($filters['public']??'',array('public','hidden'),true))$this->db->where('r.is_public',$filters['public']==='public'?1:0);
            $search=array('r.room_code','r.room_name','r.room_label','p.code','rt.name');
            foreach(array('unit_city','unit_address') as $field)if($this->db->field_exists($field,'gh_rooms'))$search[]='r.'.$field;
        } else {
            $this->db->select('s.*,t.fullname,t.phone,t.occupation,ec.contact_name,ec.relationship,ec.phone AS emergency_phone,r.room_code,r.room_name,r.room_label,r.status AS room_status,r.is_public,p.code AS property_code,rt.name AS type_name')->from('gh_tenant_stays s')->join('gh_tenants t','t.id=s.tenant_id','left')->join('gh_tenant_emergency_contacts ec','ec.id=(SELECT MIN(ec_one.id) FROM gh_tenant_emergency_contacts ec_one WHERE ec_one.tenant_id=t.id)','left',false)->join('gh_rooms r','r.id=s.room_id','left')->join('gh_properties p','p.id=r.property_id','left')->join('gh_room_types rt','rt.id=r.room_type_id','left');$identity='s.id';
            if($detail){
                $this->db->select('t.birth_place_date,t.religion,t.identity_number,t.marital_status,t.address');
            } else {
                $this->db->select('CASE WHEN t.identity_number IS NOT NULL AND t.identity_number<>"" THEN 1 ELSE 0 END AS identity_number',false);
            }
            $this->number_filter($filters,'property_id','r.property_id');$this->number_filter($filters,'room_type_id','r.room_type_id');$this->number_filter($filters,'room_id','s.room_id');
            if(in_array($filters['stay_status']??'',array('active','reserved','ended','internal','empty'),true))$this->db->where('s.stay_status',$filters['stay_status']);
            $search=array('t.fullname','t.phone','t.identity_number','s.source_unit_name','s.notes','r.room_label','r.room_code','p.code');
        }
        if($id!==null)$this->db->where($identity,$id);
        if($q!==''){$this->db->group_start();foreach($search as $i=>$column){if($i===0)$this->db->like($column,$q);else $this->db->or_like($column,$q);}$this->db->group_end();}
        return $this->db->get_compiled_select();
    }

    private function number_filter(array $filters,$key,$column)
    {
        if(isset($filters[$key])&&is_scalar($filters[$key])&&preg_match('/^[1-9][0-9]*$/D',(string)$filters[$key]))$this->db->where($column,(int)$filters[$key]);
    }

    private function active_filter($column,$status)
    {
        if($status==='active')$this->db->where($column,1);elseif($status==='inactive')$this->db->where($column,0);
    }
}
