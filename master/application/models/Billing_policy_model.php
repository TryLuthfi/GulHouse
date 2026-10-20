<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Versioned rental policy. Reads never issue bills, charges, checkout or transfers. */
class Billing_policy_model extends CI_Model
{
    public $db;
    public function __construct($db=NULL)
    {
        if (method_exists(get_parent_class($this), '__construct')) parent::__construct();
        if ($db === NULL && function_exists('get_instance')) {
            $ci = get_instance();
            $db = isset($ci->db) ? $ci->db : NULL;
        }
        $this->db = $db;
    }
    const VERSION='rent-v1-20261020';
    public function ready()
    {
        return $this->db->field_exists('billing_anchor_date','gh_resident_cases')&&$this->db->table_exists('gh_lifecycle_bill_policies')&&$this->db->table_exists('gh_lifecycle_penalty_payments')&&$this->db->table_exists('gh_lifecycle_overdue_reviews');
    }
    public static function today(){return (new DateTimeImmutable('now',new DateTimeZone('Asia/Jakarta')))->format('Y-m-d');}
    private static function date($date){$d=DateTimeImmutable::createFromFormat('!Y-m-d',(string)$date,new DateTimeZone('Asia/Jakarta'));return $d&&$d->format('Y-m-d')===$date?$d:NULL;}
    public static function boundary($anchor,$offset=1)
    {
        $d=self::date($anchor);if(!$d || !is_int($offset)||$offset<0||$offset>1200)return '';
        $m=$d->modify('first day of this month')->modify('+'.$offset.' months');return $m->setDate((int)$m->format('Y'),(int)$m->format('m'),min((int)$d->format('d'),(int)$m->format('t')))->format('Y-m-d');
    }
    public static function due_in_period($anchor,$period)
    {
        if(!self::date($anchor)||!preg_match('/^([0-9]{4})-([0-9]{2})$/D',$period,$m)||!checkdate((int)$m[2],1,(int)$m[1]))return '';
        $offset=((int)$m[1]-(int)substr($anchor,0,4))*12+(int)$m[2]-(int)substr($anchor,5,2);
        return self::boundary($anchor,$offset);
    }
    public static function assess(array $bill,array $payments,$asOf,$exitDate=NULL)
    {
        $due=self::date($bill['due_date']??'');$as=self::date($asOf);if(!$due||!$as)throw new RuntimeException('Tanggal kebijakan tagihan tidak valid.');
        usort($payments,function($a,$b){return strcmp((string)$a['payment_date'],(string)$b['payment_date'])?:((int)$a['id']<=>(int)$b['id']);});
        $paid=0;$settled=NULL;$base=(int)$bill['base_price'];
        foreach($payments as $p) {
            if(($p['payment_type']??'')!=='rent')continue;
            if(!self::date($p['payment_date']??''))throw new RuntimeException('Tanggal pembayaran sewa perlu rekonsiliasi sebelum menghitung denda.');
            if($p['payment_date']>$asOf)continue;
            $paid+=(int)$p['amount'];if($base>0&&$paid>=$base&&$settled===NULL)$settled=$p['payment_date'];
        }
        $stop=$settled&&$settled<$asOf?$settled:$asOf;
        if($exitDate&&self::date($exitDate)&&$exitDate<$stop)$stop=$exitDate;
        $late=max(0,(int)$due->diff(self::date($stop))->format('%r%a'));$days=max(0,$late-2);
        return array('bill_id'=>(int)$bill['id'],'due_date'=>$bill['due_date'],'payment_deadline'=>$due->modify('-2 days')->format('Y-m-d'),'late_days'=>$late,'penalty_days'=>$days,'penalty_accrued'=>$days*100000,'rent_paid'=>$paid,'rent_outstanding'=>max($base-$paid,0),'rent_settled_date'=>$settled,'accrual_stopped_at'=>$stop,'takeout_review_required'=>!$exitDate&&$paid<$base&&$late>=7);
    }
    public function enroll_reservation($case,$bill)
    {
        if(!$this->ready())return FALSE;
        if(!empty($case['billing_policy_version']))return TRUE;
        $anchor=$case['planned_start'];if(!self::date($anchor))throw new RuntimeException('Tanggal awal sewa belum valid.');
        $this->db->where('id',$case['id'])->update('gh_resident_cases',array('billing_anchor_date'=>$anchor,'billing_policy_version'=>self::VERSION,'rolling_monthly'=>1));
        $case['billing_anchor_date']=$anchor;$case['billing_policy_version']=self::VERSION;$this->attach_new_bill($case,$bill);return TRUE;
    }
    public function attach_new_bill($case,$bill)
    {
        if(!$this->ready()||($case['billing_policy_version']??'')!==self::VERSION)return FALSE;
        if($this->db->where('bill_id',$bill['id'])->count_all_results('gh_lifecycle_bill_policies'))return TRUE;
        $anchor=$case['billing_anchor_date'];$period=$this->db->where('id',$bill['period_id'])->get('gh_billing_periods')->row_array();$due=self::due_in_period($anchor,$period['period_key']??'');
        if(!$due)throw new RuntimeException('Periode tagihan tidak sesuai tanggal awal sewa.');
        $this->db->insert('gh_lifecycle_bill_policies',array('bill_id'=>$bill['id'],'case_id'=>$case['id'],'anchor_date'=>$anchor,'due_date'=>$due,'policy_version'=>self::VERSION));
        $this->db->where('id',$bill['id'])->update('gh_room_bills',array('due_date'=>$due));return TRUE;
    }
    public function summary($case,$asOf=NULL)
    {
        $out=array('enabled'=>FALSE,'anchor_date'=>NULL,'next_due_date'=>NULL,'payment_deadline'=>NULL,'grace_days'=>2,'daily_penalty'=>100000,'takeout_after_days'=>7,'takeout_review_required'=>FALSE,'penalty_accrued'=>0,'penalty_paid'=>0,'penalty_outstanding'=>0,'bills'=>array(),'fee_payments'=>array(),'overdue_review'=>NULL);
        if(!$this->ready()||($case['billing_policy_version']??'')!==self::VERSION)return $out;
        $asOf=$asOf?:self::today();$out['enabled']=TRUE;$out['anchor_date']=$case['billing_anchor_date'];
        $offset=max(1,((int)substr($asOf,0,4)-(int)substr($out['anchor_date'],0,4))*12+(int)substr($asOf,5,2)-(int)substr($out['anchor_date'],5,2));
        $next=self::boundary($out['anchor_date'],$offset);if($next<$asOf)$next=self::boundary($out['anchor_date'],$offset+1);
        $out['next_due_date']=$next;$out['payment_deadline']=self::date($next)->modify('-2 days')->format('Y-m-d');
        $q=$this->db->where('case_id',$case['id'])->get('gh_checkout_requests')->row_array();$exit=$q['actual_date']??NULL;
        if($exit){$out['next_due_date']=NULL;$out['payment_deadline']=NULL;}
        $bills=$this->db->select('b.*,pol.due_date AS policy_due')->from('gh_lifecycle_bill_policies pol')->join('gh_room_bills b','b.id=pol.bill_id')->where('pol.case_id',$case['id'])->order_by('pol.due_date')->get()->result_array();
        foreach($bills as $bill) {
            if((int)$bill['stay_id']!==(int)$case['stay_id'] || (int)$bill['tenant_id']!==(int)$case['tenant_id'])throw new RuntimeException('Relasi tagihan kebijakan perlu rekonsiliasi.');
            if(in_array($bill['bill_status'],array('empty','internal','reserved'),TRUE)||(int)$bill['base_price']<1)continue;
            $bill['due_date']=$bill['policy_due'];$payments=$this->db->where('room_bill_id',$bill['id'])->get('gh_payments')->result_array();$row=self::assess($bill,$payments,$asOf,$exit);
            $fees=$this->db->where('case_id',$case['id'])->where('bill_id',$bill['id'])->where('payment_date <=',$asOf)->get('gh_lifecycle_penalty_payments')->result_array();$paid=0;foreach($fees as $f)$paid+=(int)$f['amount'];
            $row['penalty_paid']=$paid;$row['penalty_outstanding']=max($row['penalty_accrued']-$paid,0);$out['bills'][]=$row;
            $out['penalty_accrued']+=$row['penalty_accrued'];$out['penalty_paid']+=$paid;$out['penalty_outstanding']+=$row['penalty_outstanding'];
            $out['takeout_review_required']=$out['takeout_review_required']||($row['takeout_review_required']&&in_array($case['status'],array('active','notice'),TRUE));
        }
        $out['fee_payments']=$this->db->select('id,bill_id,amount,payment_date,reference_no,proof_file_id')->where('case_id',$case['id'])->order_by('id','DESC')->get('gh_lifecycle_penalty_payments')->result_array();
        $out['overdue_review']=$this->db->select('id,bill_id,due_date,unpaid_snapshot,reviewed_at,actual_exit_date')->where('case_id',$case['id'])->get('gh_lifecycle_overdue_reviews')->row_array();return $out;
    }
    public function actor_id($actor,$finance=FALSE)
    {
        if(!preg_match('/^(admin|mobile_admin):([1-9][0-9]*)$/D',(string)$actor,$m))throw new RuntimeException('Aktor administrasi tidak valid.');
        $u=$this->db->query('SELECT * FROM gh_admin_users WHERE id=? FOR UPDATE',array((int)$m[2]))->row_array();$roles=array('owner','super_admin','admin','manager');if($finance)$roles[]='finance';
        if(!$u||!(int)$u['is_active']||!in_array($u['role'],$roles,TRUE))throw new RuntimeException('Peran akun tidak mengizinkan transaksi kebijakan ini.');return (int)$u['id'];
    }
    public function record_fee($case,$p,$actor,$lifecycle)
    {
        $actorId=$this->actor_id($actor,TRUE);$bill=(int)($p['bill_id']??0);$date=(string)($p['payment_date']??'');$amount=(string)($p['amount']??'');$key=(string)($p['request_key']??'');$ref=trim((string)($p['reference_no']??''));
        if(!preg_match('/^[0-9]{1,12}$/D',$amount)||(int)$amount<1||!self::date($date)||$date>self::today()||!preg_match('/^[A-Za-z0-9_-]{16,100}$/D',$key)||$ref===''||strlen($ref)>160)throw new RuntimeException('Nominal, tanggal, referensi, atau kunci pembayaran denda tidak valid.');
        $old=$this->db->query('SELECT * FROM gh_lifecycle_penalty_payments WHERE request_key=? FOR UPDATE',array($key))->row_array();
        if($old){if((int)$old['case_id']===(int)$case['id']&&(int)$old['bill_id']===$bill&&(int)$old['amount']===(int)$amount&&$old['payment_date']===$date&&$old['reference_no']===$ref)return (int)$old['id'];throw new RuntimeException('Kunci transaksi sudah digunakan untuk data berbeda.');}
        $b=$this->db->query('SELECT * FROM gh_room_bills WHERE id=? FOR UPDATE',array($bill))->row_array();if(!$b||(int)$b['stay_id']!==(int)$case['stay_id'])throw new RuntimeException('Tagihan denda bukan milik penghuni ini.');
        $this->db->query('SELECT id FROM gh_payments WHERE room_bill_id=? FOR UPDATE',array($bill));$summary=$this->summary($case,$date);$target=NULL;foreach($summary['bills'] as $row)if($row['bill_id']===$bill)$target=$row;
        $current=$this->summary($case);$currentTarget=NULL;foreach($current['bills'] as $row)if($row['bill_id']===$bill)$currentTarget=$row;
        if(!$target || !$currentTarget || (int)$amount>$target['penalty_outstanding'] || (int)$amount>$currentTarget['penalty_outstanding'])throw new RuntimeException('Pembayaran melebihi denda terutang pada tanggal tersebut atau saldo denda terkini.');
        if($this->db->where('bill_id',$bill)->where('reference_no',$ref)->count_all_results('gh_lifecycle_penalty_payments'))throw new RuntimeException('Referensi pembayaran denda sudah dicatat.');
        $proof=$lifecycle->upload($case['id'],'proof_file','penalty_proof');
        $this->db->insert('gh_lifecycle_penalty_payments',array('case_id'=>$case['id'],'bill_id'=>$bill,'amount'=>(int)$amount,'payment_date'=>$date,'reference_no'=>$ref,'request_key'=>$key,'proof_file_id'=>$proof,'recorded_by'=>$actorId));return (int)$this->db->insert_id();
    }
    public function review_overdue($case,$p,$actor)
    {
        $actorId=$this->actor_id($actor);$reason=trim((string)($p['reason']??''));
        if((string)($p['review_confirmed']??'')!=='1'||(string)($p['retain_debt']??'')!=='1'||mb_strlen($reason)<10||mb_strlen($reason)>1000)throw new RuntimeException('Konfirmasi peninjauan dan piutang tetap tercatat, serta isi alasan 10–1.000 karakter.');
        $summary=$this->summary($case);$eligible=NULL;foreach($summary['bills'] as $row)if($row['takeout_review_required']){$eligible=$row;break;}
        if(!$summary['takeout_review_required']||!$eligible)throw new RuntimeException('Peninjauan take-out memerlukan tunggakan sewa minimal H+7 yang belum lunas.');
        $lockedBill=$this->db->query('SELECT * FROM gh_room_bills WHERE id=? FOR UPDATE',array($eligible['bill_id']))->row_array();$lockedPayments=$this->db->query('SELECT * FROM gh_payments WHERE room_bill_id=? FOR UPDATE',array($eligible['bill_id']))->result_array();
        $lockedBill['due_date']=$eligible['due_date'];$eligible=self::assess($lockedBill,$lockedPayments,self::today());if(!$eligible['takeout_review_required'])throw new RuntimeException('Tagihan sudah dibayar atau belum memenuhi H+7. Muat ulang pemeriksaan.');
        if($this->db->where('case_id',$case['id'])->count_all_results('gh_lifecycle_overdue_reviews'))throw new RuntimeException('Peninjauan sudah tercatat. Lanjutkan pemeriksaan dan checkout dengan konfirmasi piutang.');
        $this->db->insert('gh_lifecycle_overdue_reviews',array('case_id'=>$case['id'],'bill_id'=>$eligible['bill_id'],'due_date'=>$eligible['due_date'],'unpaid_snapshot'=>$eligible['rent_outstanding'],'reviewed_by'=>$actorId,'reason'=>$reason,'retain_debt'=>1,'policy_version'=>self::VERSION));return (int)$this->db->insert_id();
    }
    public function validate_forced_exit($case,$q,$p,$actor,$date)
    {
        $this->actor_id($actor);
        if((string)($p['retain_debt_confirmed']??'')!=='1'||empty($q['overdue_review_id']))throw new RuntimeException('Checkout tunggakan memerlukan peninjauan H+7 dan konfirmasi piutang tetap tercatat.');
        $r=$this->db->query('SELECT * FROM gh_lifecycle_overdue_reviews WHERE id=? FOR UPDATE',array((int)$q['overdue_review_id']))->row_array();
        if(!$r||(int)$r['case_id']!==(int)$case['id']||!$r['retain_debt']||$r['policy_version']!==self::VERSION||$r['unpaid_snapshot']<1||!empty($r['actual_exit_date'])||substr($r['reviewed_at'],0,10)>$date||self::date($r['due_date'])->modify('+7 days')->format('Y-m-d')>$date)throw new RuntimeException('Peninjauan tunggakan atau tanggal aktual keluar tidak sesuai.');
        return $r;
    }
}
