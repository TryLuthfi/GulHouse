<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Explicit ownership only. Never infer demo ownership from names, phones or notes. */
class Booking_retention_model extends CI_Model
{
    private $edges = array(
        'gh_tenant_stays'=>array('tenant_id'=>'gh_tenants'),
        'gh_contracts'=>array('tenant_id'=>'gh_tenants','stay_id'=>'gh_tenant_stays'),
        'gh_contract_documents'=>array('tenant_id'=>'gh_tenants','contract_id'=>'gh_contracts'),
        'gh_room_bills'=>array('tenant_id'=>'gh_tenants','stay_id'=>'gh_tenant_stays'),
        'gh_payments'=>array('room_bill_id'=>'gh_room_bills'),
        'gh_payment_proofs'=>array('payment_id'=>'gh_payments'),
        'gh_deposit_transactions'=>array('tenant_id'=>'gh_tenants','stay_id'=>'gh_tenant_stays','source_payment_id'=>'gh_payments'),
        'gh_resident_cases'=>array('tenant_id'=>'gh_tenants','stay_id'=>'gh_tenant_stays','ktp_file_id'=>'gh_resident_files','marriage_file_id'=>'gh_resident_files'),
        'gh_facility_inspections'=>array('case_id'=>'gh_resident_cases','tenant_id'=>'gh_tenants','stay_id'=>'gh_tenant_stays','revision_of'=>'gh_facility_inspections'),
        'gh_facility_inspection_rows'=>array('inspection_id'=>'gh_facility_inspections','maintenance_ticket_id'=>'gh_maintenance_tickets'),
        'gh_resident_files'=>array('case_id'=>'gh_resident_cases','inspection_id'=>'gh_facility_inspections'),
        'gh_checkout_requests'=>array('case_id'=>'gh_resident_cases'),
        'gh_refund_payments'=>array('case_id'=>'gh_resident_cases','proof_file_id'=>'gh_resident_files','ledger_id'=>'gh_deposit_transactions'),
        'gh_resident_events'=>array('case_id'=>'gh_resident_cases'),
        'gh_tenant_emergency_contacts'=>array('tenant_id'=>'gh_tenants'),
        'gh_complaints'=>array('tenant_id'=>'gh_tenants'),
        'gh_maintenance_tickets'=>array('tenant_id'=>'gh_tenants','complaint_id'=>'gh_complaints'),
        'gh_expenses'=>array('maintenance_ticket_id'=>'gh_maintenance_tickets'),
        'gh_lifecycle_bill_policies'=>array('bill_id'=>'gh_room_bills','case_id'=>'gh_resident_cases'),
        'gh_lifecycle_penalty_payments'=>array('case_id'=>'gh_resident_cases','bill_id'=>'gh_room_bills','proof_file_id'=>'gh_resident_files'),
        'gh_lifecycle_overdue_reviews'=>array('case_id'=>'gh_resident_cases','bill_id'=>'gh_room_bills'),
    );
    private $crossrefs=array('gh_deposit_transactions'=>array('source_payment_id'),'gh_resident_cases'=>array('ktp_file_id','marriage_file_id'),'gh_facility_inspections'=>array('revision_of'),'gh_facility_inspection_rows'=>array('maintenance_ticket_id'),'gh_refund_payments'=>array('proof_file_id','ledger_id'),'gh_checkout_requests'=>array('overdue_review_id'),'gh_lifecycle_penalty_payments'=>array('proof_file_id'));
    public function ready()
    {
        $ready=$this->db->field_exists('archived_at','gh_booking_requests')
            && $this->db->field_exists('source_booking_id','gh_contracts')
            && $this->db->table_exists('gh_booking_demo_registry')
            && $this->db->table_exists('gh_booking_retention_audit')
            && $this->db->field_exists('content_sha256','gh_booking_file_gc');
        if(!$ready)return FALSE;
        $tables=array_merge(array_keys($this->edges),array('gh_booking_requests','gh_booking_demo_registry','gh_booking_retention_audit','gh_booking_file_gc','gh_rooms','gh_mobile_tokens','gh_admin_users'));
        foreach($this->db->query('SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=?',array($this->db->database))->result_array() as $t)
            if(in_array($t['TABLE_NAME'],$tables,TRUE) && strtoupper((string)$t['ENGINE'])!=='INNODB') return FALSE;
        return TRUE;
    }
    public function can_manage($actor, $purge = FALSE)
    {
        $u=$this->db->where('id',(int)$actor)->where('is_active',1)->get('gh_admin_users')->row_array();
        return $u && ($purge ? $u['role']==='super_admin' : in_array($u['role'],array('owner','super_admin','admin','manager'),TRUE));
    }
    private function lock_actor($actor,$purge=FALSE)
    {
        $u=$this->db->query('SELECT * FROM gh_admin_users WHERE id=? FOR UPDATE',array((int)$actor))->row_array();
        if(!$u || !(int)$u['is_active'] || ($purge ? $u['role']!=='super_admin' : !in_array($u['role'],array('owner','super_admin','admin','manager'),TRUE))) throw new RuntimeException('Hak akun berubah. Tindakan ditolak.');
    }
    public function registered($id)
    {
        return $this->ready() ? $this->db->where('booking_id',(int)$id)->get('gh_booking_demo_registry')->row_array() : NULL;
    }
    private function identity($booking)
    {
        $out=array(); foreach(array('id','fullname','phone','room_interest','move_in_plan','message','source','created_at') as $k) $out[$k]=isset($booking[$k])?$booking[$k]:NULL;
        return hash('sha256',json_encode($out));
    }
    private function reason($payload,$min)
    {
        $r=isset($payload['reason']) && is_string($payload['reason']) ? trim($payload['reason']):'';
        if (mb_strlen($r)<$min || mb_strlen($r)>500) throw new RuntimeException('Alasan wajib '.$min.'–500 karakter.');
        return $r;
    }
    public function archive($id,$actor,array $payload,$restore=FALSE)
    {
        if (!$this->ready() || !$this->can_manage($actor)) throw new RuntimeException('Akses arsip ditolak atau migrasi belum tersedia.');
        $reason=$this->reason($payload,5);
        if ((string)($payload['confirmed']??'')!=='1') throw new RuntimeException('Konfirmasi tindakan wajib dicentang.');
        $this->db->trans_begin();
        try {
            $this->lock_actor($actor);
            $b=$this->db->query('SELECT * FROM gh_booking_requests WHERE id=? FOR UPDATE',array((int)$id))->row_array();
            if (!$b || !$this->can_manage($actor) || ($restore ? empty($b['archived_at']) : !empty($b['archived_at']))) throw new RuntimeException('Status booking berubah. Muat ulang daftar.');
            $this->db->where('id',(int)$id)->update('gh_booking_requests',array('archived_at'=>$restore?NULL:date('Y-m-d H:i:s'),'archived_by'=>$restore?NULL:(int)$actor,'archive_reason'=>$restore?NULL:$reason));
            $this->audit($id,$actor,$restore?'restore':'archive',$reason);
            if (!$this->db->trans_status()) throw new RuntimeException('Arsip gagal disimpan.');
            $this->db->trans_commit(); return TRUE;
        } catch(Throwable $e) { $this->db->trans_rollback(); throw $e; }
    }
    private function audit($id,$actor,$action,$reason,array $extra=array())
    {
        $this->db->insert('gh_booking_retention_audit',array_merge(array('booking_id'=>(int)$id,'actor_id'=>$actor?(int)$actor:NULL,'action'=>$action,'reason'=>$reason),$extra));
    }
    private function add_rows(&$sets,$table,$rows)
    {
        $changed=FALSE;
        $key=$table==='gh_lifecycle_bill_policies'?'bill_id':'id';
        foreach($rows as $r) if (!isset($sets[$table][(int)$r[$key]])) { $sets[$table][(int)$r[$key]]=$r; $changed=TRUE; }
        return $changed;
    }
    /** Graph includes both incoming children and outgoing non-master references. */
    private function graph($id,$lock=FALSE,array $virtualRoots=array())
    {
        if($this->db->field_exists('overdue_review_id','gh_checkout_requests'))$this->edges['gh_checkout_requests']['overdue_review_id']='gh_lifecycle_overdue_reviews';
        $suffix=$lock?' FOR UPDATE':'';
        $booking=$this->db->query('SELECT * FROM gh_booking_requests WHERE id=?'.$suffix,array((int)$id))->row_array();
        if (!$booking) throw new RuntimeException('Booking tidak ditemukan.');
        $sets=array('gh_booking_requests'=>array((int)$id=>$booking)); $blockers=array();
        foreach(array('gh_tenants','gh_tenant_stays','gh_contracts') as $table)
            $this->add_rows($sets,$table,$this->db->query('SELECT * FROM `'.$table.'` WHERE source_booking_id=?'.$suffix,array((int)$id))->result_array());
        foreach($virtualRoots as $table=>$rid) {
            if(!in_array($table,array('gh_tenants','gh_tenant_stays','gh_contracts'),TRUE))throw new RuntimeException('Pemetaan kandidat tidak valid.');
            $r=$this->db->query('SELECT * FROM `'.$table.'` WHERE id=?'.$suffix,array((int)$rid))->row_array();
            if(!$r || (!empty($r['source_booking_id']) && (int)$r['source_booking_id']!==(int)$id))throw new RuntimeException('Kandidat sudah dimiliki booking lain.');
            $r['source_booking_id']=(string)$id;
            $this->add_rows($sets,$table,array($r));
        }
        $changed=TRUE; $rounds=0;
        while($changed && ++$rounds<100) {
            $changed=FALSE;
            foreach($this->edges as $child=>$edges) {
                if (!$this->db->table_exists($child)) continue;
                foreach($edges as $col=>$parent) {
                    if (!$this->db->field_exists($col,$child)) continue;
                    if(in_array($col,$this->crossrefs[$child]??array(),TRUE))continue;
                    if (!empty($sets[$parent])) {
                        $ids=array_keys($sets[$parent]);
                        $rows=$this->db->query('SELECT * FROM `'.$child.'` WHERE `'.$col.'` IN ('.implode(',',array_map('intval',$ids)).')'.$suffix)->result_array();
                        $changed=$this->add_rows($sets,$child,$rows)||$changed;
                    }
                }
            }
        }
        if ($rounds>=100) $blockers[]='Graf relasi terlalu kompleks untuk penghapusan aman.';
        // A reference is not ownership. Every outgoing reference must already be independently owned;
        // incoming cross references from outside the closure block deletion instead of adopting that row.
        foreach($this->edges as $child=>$edges) if($this->db->table_exists($child)) foreach($edges as $col=>$parent) {
            if(!$this->db->field_exists($col,$child))continue;
            foreach($sets[$child]??array() as $r) if(!empty($r[$col]) && empty($sets[$parent][(int)$r[$col]])) $blockers[]='Referensi keluar mengarah ke data yang kepemilikannya tidak terbukti.';
            if(!empty($sets[$parent])) foreach($this->db->query('SELECT * FROM `'.$child.'` WHERE `'.$col.'` IN ('.implode(',',array_keys($sets[$parent])).')'.$suffix)->result_array() as $r)
                if(empty($sets[$child][(int)$r[$child==='gh_lifecycle_bill_policies'?'bill_id':'id']])) $blockers[]='Data di luar demo masih mereferensikan data yang akan dihapus.';
        }
        foreach(array('gh_tenants','gh_tenant_stays','gh_contracts') as $table) foreach($sets[$table]??array() as $r)
            if ((int)$r['source_booking_id']!==(int)$id) $blockers[]='Ada penghuni, masa tinggal, atau kontrak bersama yang kepemilikannya belum terbukti.';
        if ($booking['status']==='converted' && (empty($sets['gh_tenants']) || empty($sets['gh_tenant_stays']) || empty($sets['gh_contracts']))) $blockers[]='Booking lama hasil konversi belum memiliki pemetaan kepemilikan yang ditinjau.';
        $rooms=array(); foreach($sets['gh_tenant_stays']??array() as $r) $rooms[(int)$r['room_id']]=TRUE;
        foreach(array_keys($rooms) as $room) {
            if ($lock) $this->db->query('SELECT id FROM gh_rooms WHERE id=? FOR UPDATE',array($room));
            foreach(array('gh_expenses','gh_maintenance_tickets') as $table) {
                foreach($this->db->query('SELECT * FROM `'.$table.'` WHERE room_id=?'.$suffix,array($room))->result_array() as $r)
                    if(empty($sets[$table][(int)$r['id']])) $blockers[]='Ada biaya atau perawatan kamar lain yang tidak dapat dibuktikan sebagai milik demo.';
            }
        }
        if ($this->db->table_exists('gh_mobile_tokens')) {
            foreach(array('resident'=>'gh_resident_cases','payment'=>'gh_tenant_stays') as $kind=>$parent) if(!empty($sets[$parent]))
                $this->add_rows($sets,'gh_mobile_tokens',$this->db->query('SELECT * FROM gh_mobile_tokens WHERE subject_kind=? AND subject_id IN ('.implode(',',array_keys($sets[$parent])).')'.$suffix,array($kind))->result_array());
        }
        $this->unknown_relations($sets,$blockers);
        $tables=array_merge(array_keys($sets),array('gh_booking_demo_registry','gh_booking_retention_audit','gh_booking_file_gc','gh_rooms'));
        foreach($this->db->query('SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=?',array($this->db->database))->result_array() as $t)
            if(in_array($t['TABLE_NAME'],$tables,TRUE) && strtoupper((string)$t['ENGINE'])!=='INNODB') $blockers[]='Tabel terkait belum mendukung transaksi penuh. Penghapusan diblokir.';
        foreach($sets as &$rows) ksort($rows); unset($rows); ksort($sets);
        return array($booking,$sets,array_values(array_unique($blockers)),array_keys($rooms));
    }
    private function unknown_relations($sets,&$blockers)
    {
        $sensitive=array('booking_id','source_booking_id','tenant_id','stay_id','contract_id','case_id','inspection_id','payment_id','source_payment_id','room_bill_id','ledger_id','proof_file_id','maintenance_ticket_id','complaint_id','revision_of','ktp_file_id','marriage_file_id');
        $retained=array('gh_booking_demo_registry','gh_booking_retention_audit','gh_booking_file_gc');
        foreach($this->db->list_tables() as $table) {
            if (in_array($table,$retained,TRUE)) continue;
            foreach($this->db->list_fields($table) as $col) if (in_array($col,$sensitive,TRUE) || preg_match('/(^|_)(booking|tenant|stay|contract|case|inspection|payment|bill|ledger|proof|ticket|complaint|device)_id$/D',$col)) {
                $known=isset($this->edges[$table][$col]) || ($col==='source_booking_id' && in_array($table,array('gh_tenants','gh_tenant_stays','gh_contracts'),TRUE));
                if (!$known) $blockers[]='Skema memiliki relasi baru yang belum didukung. Penghapusan diblokir untuk pemeriksaan teknis.';
            }
        }
        $fks=$this->db->query('SELECT TABLE_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=? AND REFERENCED_TABLE_NAME IS NOT NULL',array($this->db->database))->result_array();
        foreach($fks as $fk) if(isset($sets[$fk['REFERENCED_TABLE_NAME']]) && (($this->edges[$fk['TABLE_NAME']][$fk['COLUMN_NAME']]??'')!==$fk['REFERENCED_TABLE_NAME'] || $fk['REFERENCED_COLUMN_NAME']!=='id')) $blockers[]='Foreign key tambahan atau pemetaannya belum ditinjau; penghapusan diblokir.';
    }
    private function file_candidate($kind,$name)
    {
        if (!$name || basename($name)!==$name || strpos($name,'\\')!==FALSE || in_array($name,array('.','..'),TRUE)) return FALSE;
        if($kind==='payment' && !preg_match('/^proof_'.substr(hash('sha256',$this->db->database),0,8).'[a-f0-9]{24}\.(pdf|jpg|jpeg|png)$/D',(string)$name))return FALSE;
        $roots=array('resident'=>APPPATH.'private_lifecycle/'.substr(hash('sha256',$this->db->database),0,16),'payment'=>APPPATH.'private_payment_proofs','document'=>APPPATH.'private_documents','legacy_document'=>FCPATH.'../uploads/DOCUMENTS');
        if(!isset($roots[$kind])) return FALSE;
        $root=realpath($roots[$kind]);
        return $root && is_dir($root) && !is_link($roots[$kind]) ? $root.DIRECTORY_SEPARATOR.$name : FALSE;
    }
    private function file_path($kind,$name)
    {
        $candidate=$this->file_candidate($kind,$name);$path=$candidate?realpath($candidate):FALSE;
        return $path && dirname($path)===dirname($candidate) && is_file($path) && !is_link($candidate)?$path:FALSE;
    }
    private function files($sets,&$blockers)
    {
        $files=array();
        $definitions=array('gh_resident_files'=>array('resident','storage_name'),'gh_payment_proofs'=>array('payment','stored_filename'),'gh_contract_documents'=>array('document','file_path'));
        foreach($definitions as $table=>$def) foreach($sets[$table]??array() as $r) {
            $value=(string)$r[$def[1]]; if($value==='') continue;
            $kind=$def[0]; $name=$value;
            if ($table==='gh_contract_documents') {
                if(strpos($value,'uploads/DOCUMENTS/')===0) { $kind='legacy_document'; $name=substr($value,18); }
                elseif(strpos($value,'private_documents/')===0) $name=substr($value,18);
                elseif(strpos($value,'private/')===0) $name=substr($value,8);
                elseif(basename($value)!==$value) { $blockers[]='Lampiran memakai lokasi yang tidak didukung untuk backup aman.'; continue; }
            }
            $path=$this->file_path($kind,$name);
            if(!$path) { $blockers[]='Lampiran tidak ditemukan atau lokasi file tidak aman.'; continue; }
            $others=$table==='gh_contract_documents'?$this->db->get($table)->result_array():$this->db->where($def[1],$value)->get($table)->result_array();
            foreach($others as $other) if(empty($sets[$table][(int)$other['id']]) && ($table!=='gh_contract_documents' || basename((string)$other[$def[1]])===$name)) $blockers[]='Lampiran masih direferensikan data di luar demo.';
            $files[$kind.':'.$name]=array('kind'=>$kind,'reference_id'=>(int)$r['id'],'name'=>$name,'sha256'=>hash_file('sha256',$path),'size'=>filesize($path));
        }
        ksort($files); return array_values($files);
    }
    private function build($id,$lock=FALSE)
    {
        list($booking,$sets,$blockers,$rooms)=$this->graph($id,$lock);
        $registry=$this->registered($id);
        if(!$registry || !hash_equals($registry['identity_hash'],$this->identity($booking))) $blockers[]='Provenans demo terpercaya belum terdaftar atau identitas booking berubah.';
        $files=$this->files($sets,$blockers);
        $hash=hash('sha256',json_encode(array('rows'=>$sets,'files'=>$files,'registry'=>$registry,'rooms'=>$rooms),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        return array($booking,$sets,array_values(array_unique($blockers)),$rooms,$files,$hash,$registry);
    }
    private function token($id,$actor,$hash,$expires,$purpose='purge')
    {
        $this->strong_key();
        return $expires.'.'.hash_hmac('sha256',$purpose.'|'.$this->db->database.'|'.$id.'|'.$actor.'|'.$hash.'|'.$expires,$this->strong_key());
    }
    private function strong_key()
    {
        $key=(string)getenv('GULHOUSE_BOOKING_RETENTION_KEY');
        if($key==='') {
            $envFile=dirname(APPPATH).'/.env';
            if(is_file($envFile)) { $env=parse_ini_file($envFile);$key=(string)($env['GULHOUSE_BOOKING_RETENTION_KEY']??''); }
        }
        $path=APPPATH.'private_booking_backups/'.substr(hash('sha256',$this->db->database),0,16).'/retention.key';
        if($key==='' && is_file($path) && !is_link($path))$key=trim(file_get_contents($path));
        if($key==='')$key=(string)$this->config->item('encryption_key');
        if(strlen($key)<32 || $key==='gulhouse_landing_change_this_key_on_production' || count(array_unique(str_split($key)))<8) throw new RuntimeException('Konfigurasikan kunci aplikasi rahasia minimal 32 karakter sebelum menghapus demo.');
        return $key;
    }
    public function init_key()
    {
        if(PHP_SAPI!=='cli')throw new RuntimeException('Inisialisasi kunci hanya melalui operator CLI.');
        $dir=APPPATH.'private_booking_backups/'.substr(hash('sha256',$this->db->database),0,16);
        if(!is_dir($dir) && !mkdir($dir,0700,TRUE))throw new RuntimeException('Direktori kunci tidak dapat dibuat.');
        if(is_link($dir))throw new RuntimeException('Direktori kunci tidak aman.');
        $path=$dir.'/retention.key';
        if(is_file($path)) {$this->strong_key();return FALSE;}
        $handle=@fopen($path,'x');if(!$handle)throw new RuntimeException('Kunci belum dapat dibuat secara atomik.');
        $key=bin2hex(random_bytes(32));$ok=chmod($path,0600) && fwrite($handle,$key)===64;fclose($handle);
        if(!$ok || !hash_equals($key,trim((string)file_get_contents($path))))throw new RuntimeException('Penyimpanan kunci tidak terverifikasi. Jangan gunakan fitur purge.');
        return TRUE;
    }
    public function preview($id,$actor)
    {
        if(!$this->ready() || !$this->can_manage($actor,TRUE)) throw new RuntimeException('Pratinjau hanya untuk super admin aktif; migrasi harus tersedia.');
        list($b,$sets,$blockers,$rooms,$files,$hash,$registry)=$this->build((int)$id);
        $scope=$this->scope($sets,$files);$verification=array();
        if(!$registry) {
            list($candidateBooking,$candidateSets,$candidateBlockers,$candidateFiles,$candidateHash,$mapping,$kind)=$this->verification_candidate((int)$id);
            $candidateScope=$this->scope($candidateSets,$candidateFiles);$expires=time()+300;
            $verification=array_merge($candidateScope,array('hash'=>$candidateHash,'token'=>$this->token($id,$actor,$candidateHash,$expires,'verify-demo'),'expires_at'=>date('Y-m-d H:i:s',$expires),'blockers'=>$candidateBlockers,'mapping_summary'=>$kind==='legacy'?'Kandidat konversi lama ditemukan dari catatan booking dan label sumber yang persis cocok. Catatan ini hanya petunjuk; super admin wajib memastikan seluruh cakupan merupakan data demo.':($kind==='linked'?'Konversi mempunyai tautan booking eksplisit. Super admin tetap wajib memastikan seluruh cakupan merupakan data demo.':'Booking belum dikonversi. Super admin wajib memastikan permintaan ini memang data demo.')));
            $scope=$candidateScope;
        }
        if(empty($b['archived_at'])) $blockers[]='Arsipkan booking terlebih dahulu sebelum penghapusan permanen.';
        $expires=time()+300;
        return array_merge($scope,array('booking_id'=>(int)$id,'is_archived'=>!empty($b['archived_at']),'is_demo'=>(bool)$registry,'can_purge'=>!$blockers,'can_verify_demo'=>!$registry && empty($verification['blockers']),'verification'=>$verification,'blockers'=>$blockers,'confirmation_phrase'=>'HAPUS DEMO #'.(int)$id,'plan_hash'=>$hash,'review_token'=>$this->token($id,$actor,$hash,$expires),'expires_at'=>date('Y-m-d H:i:s',$expires)));
    }
    private function scope($sets,$files)
    {
        $labels=array('gh_booking_requests'=>'Permintaan booking','gh_tenants'=>'Identitas penghuni','gh_tenant_stays'=>'Masa tinggal','gh_contracts'=>'Kontrak','gh_room_bills'=>'Tagihan','gh_payments'=>'Pembayaran','gh_contract_documents'=>'Dokumen kontrak','gh_resident_cases'=>'Portal penghuni','gh_mobile_tokens'=>'Token perangkat','gh_resident_files'=>'Lampiran penghuni','gh_facility_inspections'=>'Pemeriksaan fasilitas','gh_facility_inspection_rows'=>'Rincian fasilitas','gh_checkout_requests'=>'Pengajuan keluar','gh_refund_payments'=>'Pembayaran refund','gh_deposit_transactions'=>'Transaksi deposit','gh_payment_proofs'=>'Bukti pembayaran','gh_resident_events'=>'Riwayat portal','gh_tenant_emergency_contacts'=>'Kontak darurat','gh_maintenance_tickets'=>'Tiket perawatan','gh_complaints'=>'Komplain','gh_expenses'=>'Biaya terkait','gh_lifecycle_bill_policies'=>'Kebijakan tagihan','gh_lifecycle_penalty_payments'=>'Pembayaran denda','gh_lifecycle_overdue_reviews'=>'Tinjauan tunggakan');
        $nodes=array();$counts=array();foreach($sets as $table=>$rows){$counts[$table]=count($rows);$nodes[]=array('table'=>$table,'label'=>$labels[$table]??'Data terkait','count'=>count($rows),'ids'=>array_keys($rows));}
        return array('nodes'=>$nodes,'counts'=>$counts,'files'=>array('count'=>count($files),'items'=>array_map(function($f){return array_intersect_key($f,array_flip(array('kind','reference_id','name')));},$files)));
    }
    /** Read-only discovery. Source hints identify candidates, never demo provenance. */
    private function verification_candidate($id,$lock=FALSE)
    {
        $suffix=$lock?' FOR UPDATE':'';
        $b=$this->db->query('SELECT * FROM gh_booking_requests WHERE id=?'.$suffix,array((int)$id))->row_array();
        if(!$b)throw new RuntimeException('Booking tidak ditemukan.');
        $mapping=array();$kind='fresh';$blockers=array();$virtual=array();
        if($b['status']==='converted') {
            $linked=array();foreach(array('gh_tenants','gh_tenant_stays','gh_contracts') as $table)$linked[$table]=$this->db->query('SELECT * FROM `'.$table.'` WHERE source_booking_id=?'.$suffix,array((int)$id))->result_array();
            if(count($linked['gh_tenants'])===1 && count($linked['gh_tenant_stays'])===1 && count($linked['gh_contracts'])===1) {
                $kind='linked';foreach($linked as $table=>$rows)$mapping[$table]=(int)$rows[0]['id'];
            } elseif(array_filter($linked))$blockers[]='Tautan konversi tidak tunggal atau belum lengkap. Verifikasi demo diblokir.';
            else {
                $kind='legacy';
                $contracts=$this->db->query('SELECT * FROM gh_contracts WHERE BINARY notes=?'.$suffix,array('Dibuat dari booking #'.(int)$id))->result_array();
                if(count($contracts)!==1)$blockers[]='Tidak ada satu kandidat kontrak dengan catatan booking yang persis cocok, atau kandidat lebih dari satu.';
                else {
                    $c=$contracts[0];$t=$this->db->query('SELECT * FROM gh_tenants WHERE id=?'.$suffix,array((int)$c['tenant_id']))->row_array();$s=$this->db->query('SELECT * FROM gh_tenant_stays WHERE id=?'.$suffix,array((int)$c['stay_id']))->row_array();
                    if(!$t || !$s || (string)$t['source_label']!=='Booking #'.(int)$id)$blockers[]='Label sumber penghuni tidak persis cocok dengan booking ini.';
                    else $mapping=array('gh_tenants'=>(int)$t['id'],'gh_tenant_stays'=>(int)$s['id'],'gh_contracts'=>(int)$c['id']);
                    if(!empty($c['source_booking_id']) || ($t&&!empty($t['source_booking_id'])) || ($s&&!empty($s['source_booking_id'])))$blockers[]='Kandidat sudah mempunyai tautan sumber lain.';
                }
            }
            if($mapping) {
                $t=$this->db->query('SELECT * FROM gh_tenants WHERE id=?'.$suffix,array($mapping['gh_tenants']))->row_array();$s=$this->db->query('SELECT * FROM gh_tenant_stays WHERE id=?'.$suffix,array($mapping['gh_tenant_stays']))->row_array();$c=$this->db->query('SELECT * FROM gh_contracts WHERE id=?'.$suffix,array($mapping['gh_contracts']))->row_array();
                if(!$t || !$s || !$c || (int)$s['tenant_id']!==(int)$t['id'] || (int)$c['tenant_id']!==(int)$t['id'] || (int)$c['stay_id']!==(int)$s['id'] || (int)$c['room_id']!==(int)$s['room_id'] || (string)$c['start_date']!==(string)$s['check_in_date'] || (string)$c['end_date']!==(string)$s['check_out_date'] || (string)$t['fullname']!==(string)$b['fullname'] || (string)$t['phone']!==(string)$b['phone'])$blockers[]='Identitas atau hubungan penghuni, masa tinggal, dan kontrak bertentangan dengan booking.';
                if($kind==='legacy' && !$blockers)$virtual=$mapping;
            }
        }
        list($unused,$sets,$graphBlockers)=$this->graph($id,$lock,$virtual);$blockers=array_merge($blockers,$graphBlockers);$files=$this->files($sets,$blockers);
        $hash=hash('sha256',json_encode(array('purpose'=>'verify-demo','booking'=>$b,'kind'=>$kind,'mapping'=>$mapping,'rows'=>$sets,'files'=>$files),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        return array($b,$sets,array_values(array_unique($blockers)),$files,$hash,$mapping,$kind);
    }
    public function verify_demo($id,$actor,array $payload)
    {
        if(!$this->ready() || !$this->can_manage($actor,TRUE))throw new RuntimeException('Verifikasi demo hanya untuk super admin aktif.');
        $attestation=is_string($payload['attestation']??NULL)?trim($payload['attestation']):'';
        if((string)($payload['confirmed_demo']??'')!=='1' || mb_strlen($attestation)<20 || mb_strlen($attestation)>500)throw new RuntimeException('Konfirmasi seluruh cakupan sebagai demo dan isi pernyataan 20–500 karakter.');
        $hash=(string)($payload['verification_hash']??'');$token=(string)($payload['verification_token']??'');$parts=explode('.',$token);
        if(count($parts)!==2 || !ctype_digit($parts[0]) || (int)$parts[0]<time() || (int)$parts[0]>time()+305 || !hash_equals($this->token($id,$actor,$hash,(int)$parts[0],'verify-demo'),$token))throw new RuntimeException('Tinjauan verifikasi tidak valid atau kedaluwarsa. Muat ulang pemeriksaan.');
        $this->db->trans_begin();
        try {
            $this->lock_actor($actor,TRUE);
            list($b,$sets,$blockers,$files,$actualHash,$mapping,$kind)=$this->verification_candidate((int)$id,TRUE);
            if($this->registered($id) || $blockers || !hash_equals($actualHash,$hash))throw new RuntimeException('Kandidat atau relasi berubah. Muat ulang dan tinjau seluruh cakupan.');
            if($kind==='legacy')$this->bind_legacy((int)$id,array('tenant_id'=>$mapping['gh_tenants'],'stay_id'=>$mapping['gh_tenant_stays'],'contract_id'=>$mapping['gh_contracts']));
            $this->save_registration($id,$b,'web_'.$kind,'super_admin#'.(int)$actor,$attestation,$actor);
            if(!$this->db->trans_status())throw new RuntimeException('Verifikasi gagal; seluruh perubahan dibatalkan.');
            $this->db->trans_commit();return TRUE;
        }catch(Throwable $e){$this->db->trans_rollback();throw $e;}
    }
    private function backup($id,$sets,$files)
    {
        $bytes=0; $contents=array(); foreach($files as $f) { $bytes+=$f['size']; if($bytes>32*1024*1024) throw new RuntimeException('Backup lampiran melebihi batas 32 MB. Penghapusan diblokir.'); $p=$this->file_path($f['kind'],$f['name']); if(!$p || !hash_equals($f['sha256'],hash_file('sha256',$p))) throw new RuntimeException('Lampiran berubah selama peninjauan.'); $contents[]=array_merge($f,array('base64'=>base64_encode(file_get_contents($p)))); }
        $key=$this->strong_key(); if(!function_exists('openssl_encrypt')) throw new RuntimeException('Enkripsi backup belum tersedia.');
        $iv=random_bytes(12);$tag='';$data=openssl_encrypt(json_encode(array('schema'=>$this->db->database,'booking_id'=>$id,'rows'=>$sets,'files'=>$contents)), 'aes-256-gcm',hash('sha256',$key,TRUE),OPENSSL_RAW_DATA,$iv,$tag);
        if($data===FALSE) throw new RuntimeException('Enkripsi backup gagal.');
        $dir=APPPATH.'private_booking_backups/'.substr(hash('sha256',$this->db->database),0,16);
        if(!is_dir($dir) && !mkdir($dir,0700,TRUE)) throw new RuntimeException('Folder backup tidak dapat dibuat.');
        $name='booking-'.$id.'-'.bin2hex(random_bytes(16)).'.enc'; $path=$dir.'/'.$name;
        $blob='GHBR1'.$iv.$tag.$data;
        if(file_put_contents($path,$blob,LOCK_EX)!==strlen($blob) || !chmod($path,0600) || !hash_equals(hash('sha256',$blob),hash_file('sha256',$path))) throw new RuntimeException('Backup tidak dapat diverifikasi; penghapusan dibatalkan.');
        return array($name,hash('sha256',$blob));
    }
    public function purge($id,$actor,array $payload)
    {
        if(!$this->ready() || !$this->can_manage($actor,TRUE)) throw new RuntimeException('Penghapusan hanya untuk super admin aktif.');
        $reason=$this->reason($payload,10);$hash=(string)($payload['plan_hash']??'');$token=(string)($payload['review_token']??'');
        $parts=explode('.',$token);
        if((string)($payload['confirmation']??'')!=='HAPUS DEMO #'.(int)$id || count($parts)!==2 || !ctype_digit($parts[0]) || (int)$parts[0]<time() || (int)$parts[0]>time()+305 || !hash_equals($this->token($id,$actor,$hash,(int)$parts[0]),$token)) throw new RuntimeException('Konfirmasi atau pratinjau kedaluwarsa. Muat ulang pratinjau.');
        $this->db->trans_begin();
        try {
            $this->lock_actor($actor,TRUE);
            list($b,$sets,$blockers,$rooms,$files,$actualHash)=$this->build((int)$id,TRUE);
            if(!$this->can_manage($actor,TRUE) || empty($b['archived_at']) || $blockers || !hash_equals($actualHash,$hash)) throw new RuntimeException('Rencana berubah atau terdapat relasi yang tidak aman. Tinjau ulang booking.');
            list($backup,$sha)=$this->backup($id,$sets,$files);
            // Null cyclic references inside the proven graph only; immutable finalized data is deleted as one demo aggregate.
            foreach(array('gh_resident_cases'=>array('ktp_file_id','marriage_file_id'),'gh_facility_inspections'=>array('revision_of'),'gh_facility_inspection_rows'=>array('maintenance_ticket_id')) as $table=>$cols) if(!empty($sets[$table])) $this->db->where_in('id',array_keys($sets[$table]))->update($table,array_fill_keys($cols,NULL));
            $order=array('gh_mobile_tokens','gh_lifecycle_penalty_payments','gh_refund_payments','gh_checkout_requests','gh_lifecycle_overdue_reviews','gh_lifecycle_bill_policies','gh_resident_events','gh_resident_files','gh_facility_inspection_rows','gh_facility_inspections','gh_expenses','gh_maintenance_tickets','gh_complaints','gh_payment_proofs','gh_deposit_transactions','gh_payments','gh_room_bills','gh_contract_documents','gh_resident_cases','gh_contracts','gh_tenant_emergency_contacts','gh_tenant_stays','gh_tenants','gh_booking_requests');
            foreach($order as $table) if(!empty($sets[$table])) { $this->db->where_in($table==='gh_lifecycle_bill_policies'?'bill_id':'id',array_keys($sets[$table]))->delete($table); if(!$this->db->trans_status()) throw new RuntimeException('Penghapusan gagal; seluruh perubahan dibatalkan.'); }
            $this->db->where('booking_id',(int)$id)->delete('gh_booking_demo_registry');
            foreach($rooms as $room) if(!$this->db->where('room_id',$room)->where_in('stay_status',array('active','reserved','internal'))->count_all_results('gh_tenant_stays')) $this->db->where('id',$room)->where_in('status',array('occupied','reserved'))->update('gh_rooms',array('status'=>'available'));
            foreach($files as $f) $this->db->insert('gh_booking_file_gc',array('booking_id'=>(int)$id,'storage_kind'=>$f['kind'],'storage_name'=>$f['name'],'content_sha256'=>$f['sha256']));
            $counts=array();foreach($sets as $t=>$r)$counts[$t]=count($r);
            $this->audit($id,$actor,'purge',$reason,array('plan_hash'=>$hash,'counts_json'=>json_encode($counts),'backup_name'=>$backup,'backup_sha256'=>$sha));
            if(!$this->db->trans_status()) throw new RuntimeException('Transaksi gagal; penghapusan dibatalkan.');
            $this->db->trans_commit();return TRUE;
        } catch(Throwable $e) { $this->db->trans_rollback();throw $e; }
    }
    public function collect_files($bookingId)
    {
        if(!$this->ready()) throw new RuntimeException('Migrasi GC belum tersedia.');
        $result=array('removed'=>0,'pending'=>0);
        foreach($this->db->where('booking_id',(int)$bookingId)->where('status','pending')->get('gh_booking_file_gc')->result_array() as $gc) {
            $kind=$gc['storage_kind'];$name=$gc['storage_name'];$path=$this->file_path($kind,$name);
            $references=0;
            if($kind==='resident') $references=$this->db->where('storage_name',$name)->count_all_results('gh_resident_files');
            elseif($kind==='payment') $references=$this->db->where('stored_filename',$name)->count_all_results('gh_payment_proofs');
            elseif(in_array($kind,array('document','legacy_document'),TRUE)) {
                foreach($this->db->select('file_path')->get('gh_contract_documents')->result_array() as $r) if(basename((string)$r['file_path'])===$name)$references++;
            } else {$result['pending']++;continue;}
            $candidate=$this->file_candidate($kind,$name);
            $safeAbsent=$candidate && !file_exists($candidate) && !is_link($candidate);
            if($references || (!$path && !$safeAbsent) || !preg_match('/^[a-f0-9]{64}$/D',(string)$gc['content_sha256']) || ($path && !hash_equals($gc['content_sha256'],hash_file('sha256',$path)))) {$result['pending']++;continue;}
            // No file means an earlier postcommit GC completed before the status update.
            if($path && !@unlink($path)) {$result['pending']++;continue;}
            $this->db->where('id',$gc['id'])->where('status','pending')->update('gh_booking_file_gc',array('status'=>'removed','finished_at'=>date('Y-m-d H:i:s')));
            $result['removed']++;
        }
        return $result;
    }
    private function bind_legacy($id,array $legacy)
    {
        if(empty($legacy['tenant_id']) || empty($legacy['stay_id']) || empty($legacy['contract_id'])) throw new RuntimeException('Manifest legacy memerlukan ID penghuni, masa tinggal, dan kontrak.');
        $declared=array('gh_tenants'=>(int)$legacy['tenant_id'],'gh_tenant_stays'=>(int)$legacy['stay_id'],'gh_contracts'=>(int)$legacy['contract_id']);$rows=array();
        foreach($declared as $table=>$rid) {
            $row=$this->db->query('SELECT * FROM `'.$table.'` WHERE id=? FOR UPDATE',array($rid))->row_array();
            if(!$row || !empty($row['source_booking_id'])) throw new RuntimeException('Manifest legacy tidak sesuai atau sudah dimiliki booking lain.');
            $rows[$table]=$row;
        }
        if((int)$rows['gh_tenant_stays']['tenant_id']!==$declared['gh_tenants'] || (int)$rows['gh_contracts']['tenant_id']!==$declared['gh_tenants'] || (int)$rows['gh_contracts']['stay_id']!==$declared['gh_tenant_stays'] || (int)$rows['gh_contracts']['room_id']!==(int)$rows['gh_tenant_stays']['room_id']) throw new RuntimeException('ID manifest legacy tidak saling sesuai.');
        foreach($declared as $table=>$rid) {
            $data=array('source_booking_id'=>(int)$id);
            if(array_key_exists('updated_at',$rows[$table]))$data['updated_at']=$rows[$table]['updated_at'];
            $this->db->where('id',$rid)->update($table,$data);
        }
    }
    public function legacy_plan($id,array $legacy)
    {
        if(PHP_SAPI!=='cli' || !$this->ready()) throw new RuntimeException('Rencana legacy hanya tersedia melalui CLI operator.');
        $this->db->trans_begin();
        try {
            $b=$this->db->query('SELECT * FROM gh_booking_requests WHERE id=? FOR UPDATE',array((int)$id))->row_array();
            if(!$b || $b['status']!=='converted' || $this->registered($id)) throw new RuntimeException('Booking bukan konversi legacy yang belum terdaftar.');
            $this->bind_legacy($id,$legacy);list($unused,$sets,$blockers)=$this->graph($id,TRUE);$this->files($sets,$blockers);
            $counts=array();foreach($sets as $t=>$rows)$counts[$t]=array_keys($rows);
            $out=array('booking_id'=>(int)$id,'can_register'=>!$blockers,'blockers'=>$blockers,'nodes'=>$counts,'fingerprint'=>hash('sha256',json_encode($sets,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)));
            $this->db->trans_rollback();return $out;
        } catch(Throwable $e) {$this->db->trans_rollback();throw $e;}
    }
    /** CLI-only trusted operator boundary, never exposed as a controller or native API action. */
    public function register_demo($id,$operator,$attestation,array $legacy=array())
    {
        if(PHP_SAPI!=='cli' || !$this->ready() || strlen(trim($operator))<3 || strlen(trim($attestation))<20 || strlen($attestation)>500) throw new RuntimeException('Registrasi memerlukan operator CLI dan pernyataan peninjauan minimal 20 karakter.');
        $this->db->trans_begin();
        try {
            $b=$this->db->query('SELECT * FROM gh_booking_requests WHERE id=? FOR UPDATE',array((int)$id))->row_array();
            if(!$b || $this->registered($id)) throw new RuntimeException('Booking tidak ada atau sudah terdaftar.');
            if($b['status']==='converted') {
                if(empty($legacy['tenant_id']) || empty($legacy['stay_id']) || empty($legacy['contract_id']) || empty($legacy['expected_hash'])) throw new RuntimeException('Konversi lama memerlukan manifest ID lengkap dan fingerprint yang ditinjau.');
                $this->bind_legacy($id,$legacy);
                list($unused,$sets,$blockers)=$this->graph($id,TRUE);
                $this->files($sets,$blockers);
                $fingerprint=hash('sha256',json_encode($sets,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
                if($blockers || !hash_equals($fingerprint,(string)$legacy['expected_hash'])) throw new RuntimeException('Manifest legacy belum aman atau fingerprint tidak cocok. Gunakan CLI plan-legacy dahulu.');
            } elseif($legacy) throw new RuntimeException('Manifest legacy hanya untuk booking hasil konversi.');
            $this->save_registration($id,$b,$legacy?'reviewed_legacy':'trusted_fresh',$operator,$attestation,NULL);
            if(!$this->db->trans_status()) throw new RuntimeException('Registrasi gagal.');
            $this->db->trans_commit();return TRUE;
        } catch(Throwable $e) {$this->db->trans_rollback();throw $e;}
    }
    private function save_registration($id,$booking,$kind,$operator,$attestation,$actor)
    {
        $this->db->insert('gh_booking_demo_registry',array('booking_id'=>(int)$id,'identity_hash'=>$this->identity($booking),'registration_kind'=>$kind,'registered_by'=>substr($operator,0,120),'attestation'=>$attestation));
        $this->audit($id,$actor,'register_demo','Verifikasi '.$kind.' oleh '.substr($operator,0,120));
    }
}
