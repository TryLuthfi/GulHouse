<?php
// Pure API regression: synthetic rows, fake model/query boundaries, no DB/files/network writes.
define('BASEPATH',dirname(__DIR__).'/master/system/');define('APPPATH',dirname(__DIR__).'/master/application/');
class CI_Model { public $Master_model; public $Operations_model; public $db; }
require APPPATH.'models/Mobile_service.php';
class TenantMetricsOperationsFixture {
    public function get_config($module){return NULL;}
    public function __call($name,$arguments){return array();}
}
class TenantMetricsDatabaseFixture {
    public function field_data($table){return array();}
    public function __call($name,$arguments){throw new RuntimeException('Unexpected database operation.');}
}
class TenantMetricsMasterFixture {
    public $rows=array(); public $calls=0; public $lastFilters;
    public function get_payment_bills($filters){return array(array('id'=>900));}
    public function get_payment_summary($filters){return array('bills'=>1,'paid_total'=>987654321);}
    public function get_tenant_stays($filters){
        $this->calls++;$this->lastFilters=$filters;
        return array_values(array_filter($this->rows,function($row)use($filters){
            foreach(array('property_id','room_type_id','room_id','stay_status') as $key)if(!empty($filters[$key])&&(string)$row[$key]!== (string)$filters[$key])return FALSE;
            return empty($filters['q'])||strpos($row['fullname'],$filters['q'])!==FALSE;
        }));
    }
    public function __call($name,$arguments){return array();}
}
$service=(new ReflectionClass('Mobile_service'))->newInstanceWithoutConstructor();
$master=new TenantMetricsMasterFixture();$service->Master_model=$master;$service->Operations_model=new TenantMetricsOperationsFixture();$service->db=new TenantMetricsDatabaseFixture();
$principal=array('kind'=>'admin','role'=>'admin','id'=>1,'name'=>'Synthetic');$passed=0;
function check($ok,$message){global $passed;if(!$ok)throw new RuntimeException($message);$passed++;}
function get_list($filters=array(),$actor=NULL){global $service,$master,$principal;$before=$master->calls;$result=$service->admin($actor?:$principal,array('tenants'),'GET',array(),$filters);check($master->calls===$before+1,'Stay rows fetched more than once.');check($master->lastFilters===$filters,'Existing filters changed.');return $result;}
$statuses=array('active','reserved','ended','internal','empty');
for($i=1;$i<=75;$i++){$tenant=(int)floor(($i-1)/3)+1;$master->rows[]=array('id'=>$i,'tenant_id'=>$tenant,'fullname'=>'Person-'.$tenant.'-END','stay_status'=>$statuses[($i-1)%5],'property_id'=>$i%2+1,'room_type_id'=>$i%3+1,'room_id'=>$i,'monthly_price'=>987654321,'deposit'=>123456789);}
$original=$master->rows;
$first=get_list(array('limit'=>50,'offset'=>0));
check(count($first['rows'])===50&&$first['count']===75,'Page or full count changed.');
check($first['metrics']===array('tenant_count'=>25,'stay_count'=>75,'active_stays'=>15,'reserved_stays'=>15,'ended_stays'=>15),'Full filtered aggregates incorrect.');
check($first['metrics']['stay_count']>$first['metrics']['tenant_count'],'Multiple stays counted as distinct people.');
$second=get_list(array('limit'=>50,'offset'=>50));
check(count($second['rows'])===25&&$second['rows'][0]['id']===51,'Second page changed.');
check($first['metrics']===$second['metrics'],'Metrics depend on pagination.');
$past=get_list(array('limit'=>50,'offset'=>100));
check($past['rows']===array()&&$past['count']===75&&$past['metrics']===$first['metrics'],'Empty page lost full filtered summary.');
$person=get_list(array('q'=>'Person-1-END','limit'=>1));
check($person['count']===3&&count($person['rows'])===1,'Search pagination changed.');
check($person['metrics']===array('tenant_count'=>1,'stay_count'=>3,'active_stays'=>1,'reserved_stays'=>1,'ended_stays'=>1),'Search must distinguish one person and three stays.');
$active=get_list(array('property_id'=>1,'stay_status'=>'active','limit'=>2));
check($active['count']===7&&count($active['rows'])===2,'Combined filter list changed.');
check($active['metrics']===array('tenant_count'=>7,'stay_count'=>7,'active_stays'=>7,'reserved_stays'=>0,'ended_stays'=>0),'Combined filtered aggregates wrong.');
$empty=get_list(array('q'=>'NO_MATCH'));
check($empty['count']===0&&$empty['rows']===array(),'Empty filter changed.');
check($empty['metrics']===array('tenant_count'=>0,'stay_count'=>0,'active_stays'=>0,'reserved_stays'=>0,'ended_stays'=>0),'Empty aggregates must be integer zero.');
check($master->rows===$original,'Aggregation mutated source rows.');
check(array_keys($first['rows'][0])===array_merge(array_keys($original[0]),array('actions')),'Row data shape changed beyond existing actions metadata.');
$payments=$service->admin($principal,array('payments'),'GET',array(),array());
check($payments['metrics']===array('bills'=>1,'paid_total'=>987654321),'Existing payment metrics changed.');
check(!array_key_exists('tenant_count',$payments['metrics']),'Tenant metrics leaked into another module.');
$detail=$service->admin($principal,array('tenants','1'),'GET',array(),array());
check($detail['record']['id']===1&&!array_key_exists('metrics',$detail),'Existing detail response shape changed.');
foreach($first['metrics'] as $key=>$number)check(is_int($number)&&!preg_match('/amount|price|deposit|paid|cost/',$key),'Count summary introduced a financial metric.');
$master->rows=array(array('id'=>1,'tenant_id'=>0,'stay_status'=>'active'),array('id'=>2,'tenant_id'=>-2,'stay_status'=>'reserved'),array('id'=>3,'tenant_id'=>NULL,'stay_status'=>'ended'),array('id'=>4,'stay_status'=>'internal'),array('id'=>5,'tenant_id'=>'9','stay_status'=>'empty'),array('id'=>6,'tenant_id'=>9,'stay_status'=>'unknown'));
$invalid=get_list();check($invalid['metrics']===array('tenant_count'=>1,'stay_count'=>6,'active_stays'=>1,'reserved_stays'=>1,'ended_stays'=>1),'Nonpositive/missing IDs or unknown statuses miscounted.');
$viewer=get_list(array(),array('kind'=>'admin','role'=>'viewer','id'=>2));check($viewer['metrics']===$invalid['metrics']&&$viewer['schema']['readonly']===TRUE,'Existing viewer read permission changed.');
$before=$master->calls;$forbidden=FALSE;try{$service->admin(array('kind'=>'resident','role'=>'resident'),array('tenants'),'GET',array(),array());}catch(Mobile_api_error $e){$forbidden=$e->status===403;}
check($forbidden&&$master->calls===$before,'Resident access bypassed existing permission guard.');
echo json_encode(array('passed'=>$passed,'synthetic_rows'=>75,'database_connections'=>0,'network_requests'=>0,'writes'=>0)).PHP_EOL;
