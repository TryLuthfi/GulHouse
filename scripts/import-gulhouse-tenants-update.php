<?php
/** Local, reviewable tenant updates. Console output never contains tenant PII. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

class GulhouseTenantUpdate
{
    public static function key($value) { return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper(trim((string) $value), 'UTF-8')); }
    public static function phone($value) {
        $value = preg_replace('/[^0-9]/', '', (string) $value);
        if (strpos($value, '62') === 0) $value = '0' . substr($value, 2);
        return $value;
    }
    public static function present($value) { return $value !== null && trim((string) $value) !== '' && trim((string) $value) !== '-'; }
    public static function date($value) {
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', (string) $value, $m) || ! checkdate((int)$m[2], (int)$m[3], (int)$m[1])) return false;
        return $value;
    }
    public static function fingerprint(array $data) { return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)); }
    public static function observation_note($old, $kind) {
        $line='Tenant update observed_'.$kind.'_at=2026-10-15;actual_exit_unknown';
        return strpos((string)$old,$line)!==false?$old:trim((string)$old."\n".$line);
    }
    public static function orphan_reservation(array $stay, array $data) {
        if($stay['stay_status']!=='reserved'||$stay['tenant_id']!==null||$stay['check_in_date']!==null||$stay['check_out_date']!==null)return false;
        foreach(array('gh_contracts','gh_deposit_transactions','gh_room_bills','gh_booking_requests') as $table)if(!array_key_exists($table,$data))return false;
        foreach(array('gh_contracts','gh_deposit_transactions','gh_room_bills') as $table)foreach($data[$table] as $r)if(isset($r['stay_id'])&&(string)$r['stay_id']===(string)$stay['id'])return false;
        foreach($data['gh_booking_requests'] as $booking)if(!in_array($booking['status'],array('converted','cancelled'),true))return false;
        return true;
    }

    public static function plan(array $input, array $snapshot)
    {
        $data = $snapshot['data']; $operations = array(); $issues = array(); $rowResults = array();
        $properties = array(); foreach ($data['gh_properties'] as $p) $properties[$p['id']] = self::key($p['code']);
        $tenants = array(); foreach ($data['gh_tenants'] as $t) $tenants[(string)$t['id']] = $t;
        $stays = array(); foreach ($data['gh_tenant_stays'] as $s) $stays[(string)$s['id']] = $s;
        $emergency = $data['gh_tenant_emergency_contacts'];
        $seen = array(); $newCounter = 0; $sourceKeys = array();
        foreach($input['rows'] as $r)foreach(array('identity_number','phone') as $field){$v=$field==='phone'?self::phone($r[$field]??''):trim((string)($r[$field]??''));if($v!=='')$sourceKeys[$field.':'.$v][self::key($r['fullname']??'')]=true;}
        foreach ($input['rows'] as $index => $row) {
            $ref = array('index'=>$index,'source_file'=>$row['source_file']??null,'sheet'=>$row['sheet']??null,'row'=>$row['row']??null);
            $errors = array(); $rowOps = array(); $stayErrors = array();
            $name = trim((string)($row['fullname']??'')); $nik = trim((string)($row['identity_number']??'')); $phone = self::phone($row['phone']??'');
            $invalidNik=$nik !== '' && (! preg_match('/^\d{16}$/D', $nik) || preg_match('/^0+$/', $nik));
            if ($phone !== '' && ! preg_match('/^0\d{8,14}$/D', $phone)) $errors[] = 'invalid_phone_requires_review';
            foreach(array('identity_number'=>$nik,'phone'=>$phone) as $field=>$value)if($value!=='' && count($sourceKeys[$field.':'.$value]??array())>1)$errors[]='source_identity_collision_'.$field;
            foreach(array('duplicate_source_identity_number','duplicate_source_phone') as $blockedFlag)if(in_array($blockedFlag,$row['flags']??array(),true))$errors[]=$blockedFlag;
            foreach (array('check_in_date','check_out_date') as $field) if (self::present($row[$field]??null) && ! self::date($row[$field])) $stayErrors[] = 'invalid_' . $field;
            foreach (array('monthly_price','deposit') as $field) if (self::present($row[$field]??null) && (! preg_match('/^\d+$/D',(string)$row[$field]) || strlen((string)$row[$field]) > 15)) $stayErrors[] = 'invalid_' . $field;
            $roomMatches = array_filter($data['gh_rooms'], function($r) use($row,$properties) {
                if (($properties[$r['property_id']]??'') !== self::key($row['property_code']??'')) return false;
                return (self::present($row['room_code']??null) && self::key($r['room_code']) === self::key($row['room_code'])) || (self::present($row['room_label']??null) && self::key($r['room_label']) === self::key($row['room_label']));
            });
            if (count($roomMatches) !== 1) $errors[] = 'room_missing_or_ambiguous';
            $room = count($roomMatches) === 1 ? reset($roomMatches) : null;
            $status = $row['stay_status']??null;
            if (self::present($status) && ! in_array($status,array('active','reserved','ended','empty','internal'),true)) $errors[] = 'invalid_stay_status';
            if($status==='empty' && !$errors && $room){
                $current=array_filter($stays,function($s)use($room){return (int)$s['room_id']===(int)$room['id']&&in_array($s['stay_status'],array('active','reserved','internal'),true);});
                foreach($current as $s){
                    if($s['stay_status']!=='active')$errors[]='vacancy_conflicts_reserved_or_internal_stay';
                    foreach($data['gh_contracts'] as $contract)if((int)$contract['stay_id']===(int)$s['id']&&$contract['status']!=='cancelled')$errors[]='vacancy_contract_backed_stay_protected';
                }
                if($errors){$issues[]=array('source'=>$ref,'reasons'=>array_values(array_unique($errors)));continue;}
                foreach($current as $s){
                    $values=array('stay_status'=>'ended','notes'=>self::observation_note($s['notes']??null,'vacant'));
                    $rowOps[]=array('table'=>'gh_tenant_stays','action'=>'update','id'=>(string)$s['id'],'values'=>$values,'before'=>array_intersect_key($s,$values));
                    $stays[(string)$s['id']]=array_merge($s,$values);
                }
                if(!in_array($room['status'],array('inactive','maintenance','mess'),true)&&($room['status']!=='available'||(int)$room['is_public']!==1))$rowOps[]=array('table'=>'gh_rooms','action'=>'update','id'=>(string)$room['id'],'values'=>array('status'=>'available','is_public'=>1),'before'=>array('status'=>$room['status'],'is_public'=>$room['is_public']));
                foreach($rowOps as $op){$op['source']=$ref;$operations[]=$op;}
                $rowResults[]=array('source'=>$ref,'match'=>'explicit_vacancy_observation','operation_count'=>count($rowOps));continue;
            }
            if ($name === '') $errors[] = 'missing_fullname';
            $nikMatches = $nik !== '' ? array_filter($tenants, function($t)use($nik){return trim((string)$t['identity_number']) === $nik;}) : array();
            $phoneMatches = $phone !== '' ? array_filter($tenants, function($t)use($phone){return self::phone($t['phone']) === $phone;}) : array();
            $candidate = null; $match = null;
            if (count($nikMatches) === 1) { $candidate = reset($nikMatches); $match = 'identity_number'; }
            elseif (count($nikMatches)>1) $errors[] = 'ambiguous_identity_number';
            if (! $candidate && count($phoneMatches) === 1) {
                $p = reset($phoneMatches);
                if (self::key($p['fullname']) === self::key($name)) { $candidate=$p; $match='phone_and_name'; }
                else $errors[] = 'phone_matches_different_name';
            } elseif (! $candidate && count($phoneMatches)>1) $errors[] = 'ambiguous_phone';
            if ($candidate && $nik !== '' && self::present($candidate['identity_number']) && $candidate['identity_number'] !== $nik) $errors[]='identity_number_conflict';
            if ($candidate && $phoneMatches) foreach ($phoneMatches as $p) if ((string)$p['id'] !== (string)$candidate['id']) $errors[]='phone_points_to_other_tenant';
            $nameRoom = array();
            if ($room) foreach ($stays as $s) if ((int)$s['room_id']===(int)$room['id'] && isset($tenants[(string)$s['tenant_id']]) && self::key($tenants[(string)$s['tenant_id']]['fullname'])===self::key($name)) $nameRoom[(string)$s['tenant_id']]=$tenants[(string)$s['tenant_id']];
            if (! $candidate && $nameRoom) {
                if (count($nameRoom) === 1 && ! empty($row['verified_room_name_match'])) { $candidate=reset($nameRoom); $match='verified_room_and_exact_name'; }
                else $errors[]='room_name_candidate_needs_verified_mapping';
            }
            if (! $candidate && ! $nik && ! $phone) $errors[] = 'new_tenant_requires_identity_or_phone';
            if($invalidNik && !($candidate && (string)$candidate['identity_number']===$nik && !empty($row['verified_room_name_match']) && self::key($candidate['fullname'])===self::key($name)))$errors[]='invalid_identity_number_requires_review';
            if($status==='internal' && !$candidate)$errors[]='internal_observation_no_new_tenant';
            if ($candidate && $nik !== '' && self::present($candidate['identity_number']) && $candidate['identity_number'] !== $nik) $errors[]='identity_number_conflict';
            $tenantId = $candidate ? (string)$candidate['id'] : 'new:' . (++$newCounter);
            $duplicateKey = $room ? $tenantId . ':' . $room['id'] : '';
            if ($duplicateKey && isset($seen[$duplicateKey])) $errors[]='duplicate_source_tenant_room';
            if ($errors) { $issues[]=array('source'=>$ref,'reasons'=>array_values(array_unique($errors))); continue; }
            $seen[$duplicateKey]=true;
            $changes = array(); $limits=array('fullname'=>150,'identity_number'=>32,'phone'=>32,'birth_place_date'=>150,'religion'=>60,'marital_status'=>40,'occupation'=>120,'address'=>65535);
            foreach ($limits as $field=>$limit) {
                $value = $field === 'phone' ? $phone : ($row[$field]??null);
                if($field==='identity_number' && $invalidNik)continue;
                if (! self::present($value)) continue;
                $value=trim((string)$value);
                if (mb_strlen($value,'UTF-8') > $limit) { $errors[]='field_too_long_' . $field; continue; }
                if (! $candidate || (string)($candidate[$field]??'') !== $value) $changes[$field]=$value;
            }
            if (! $candidate) $rowOps[]=array('table'=>'gh_tenants','action'=>'insert','id'=>$tenantId,'values'=>$changes+array('source_label'=>'Tenant update 2026-10-15'),'before'=>null);
            elseif ($changes) $rowOps[]=array('table'=>'gh_tenants','action'=>'update','id'=>$tenantId,'values'=>$changes,'before'=>array_intersect_key($candidate,$changes));
            $sameStays=array_filter($stays,function($s)use($tenantId,$room){return (string)$s['tenant_id']===$tenantId && (int)$s['room_id']===(int)$room['id'] && in_array($s['stay_status'],array('active','reserved','internal'),true);});
            $stay=count($sameStays)===1?reset($sameStays):null;
            if(count($sameStays)>1)$stayErrors[]='multiple_current_stays_for_tenant_room';
            $stayValues=array();
            foreach(array('check_in_date','check_out_date','monthly_price','deposit','stay_status') as $field) if(self::present($row[$field]??null) && (!$stay || (string)$stay[$field] !== (string)$row[$field]))$stayValues[$field]=$row[$field];
            $effective=array_merge($stay?:array(),$stayValues);
            if($stay && !empty($stay['check_in_date']) && isset($stayValues['check_in_date']) && $stayValues['check_in_date']!==$stay['check_in_date'])$stayErrors[]='existing_historical_checkin_differs_requires_review';
            if (!empty($effective['check_in_date']) && !empty($effective['check_out_date']) && $effective['check_out_date']<$effective['check_in_date'])$stayErrors[]='checkout_before_checkin';
            if (($effective['stay_status']??'')==='ended' && empty($effective['check_out_date']))$stayErrors[]='ended_stay_requires_actual_checkout_date';
            if ($stay && $stayValues) foreach($data['gh_contracts'] as $contract)if((int)$contract['stay_id']===(int)$stay['id'] && $contract['status']!=='cancelled')$stayErrors[]='contract_backed_stay_terms_protected';
            if(!$stay && !in_array($status,array('active','reserved'),true))$stayErrors[]='new_stay_requires_explicit_active_or_reserved';
            if(!$stay && empty($effective['check_in_date']))foreach($stays as $old)if((string)$old['tenant_id']===$tenantId)$stayErrors[]='undated_new_or_returning_stay_requires_review';
            if(!$stay && !empty($effective['check_in_date']))foreach($stays as $old)if((string)$old['tenant_id']===$tenantId && (int)$old['room_id']!==(int)$room['id'] && $old['check_in_date']===$effective['check_in_date'])$stayErrors[]='move_date_equals_previous_historical_checkin';
            $replacementOps=array();
            if(in_array($effective['stay_status']??'',array('active','reserved'),true))foreach($stays as $other){
                if((int)$other['room_id']!==(int)$room['id'] || ($stay && (string)$other['id']===(string)$stay['id']) || !in_array($other['stay_status'],array('active','reserved'),true))continue;
                $start=$effective['check_in_date']??null;$end=$effective['check_out_date']??null;
                if(($end===null || empty($other['check_in_date']) || $other['check_in_date']<=$end) && (empty($other['check_out_date']) || $start===null || $other['check_out_date']>=$start)){
                    $orphan=self::orphan_reservation($other,$data);
                    $protected=$other['stay_status']!=='active'&&!$orphan;foreach($data['gh_contracts'] as $c)if((int)$c['stay_id']===(int)$other['id']&&$c['status']!=='cancelled')$protected=true;
                    if(!empty($other['check_in_date']) && $start!==null && $start<$other['check_in_date'])$protected=true;
                    if(!$protected && !empty($row['verified_current_occupant']) && self::date($start) && !$stayErrors){$v=array('stay_status'=>'ended','notes'=>self::observation_note($other['notes']??null,$orphan?'superseded':'replacement'));$replacementOps[]=array('table'=>'gh_tenant_stays','action'=>'update','id'=>(string)$other['id'],'values'=>$v,'before'=>array_intersect_key($other,$v));}
                    else $stayErrors[]='room_has_other_overlapping_stay';
                }
            }
            if(!$stayErrors){
                foreach($replacementOps as $replacementOp)$rowOps[]=$replacementOp;
                if(!$stay)$rowOps[]=array('table'=>'gh_tenant_stays','action'=>'insert','id'=>'stay:'.$tenantId.':'.$room['id'],'values'=>$stayValues+array('tenant_id'=>$tenantId,'room_id'=>$room['id'],'source_period'=>'Tenant update 2026-10-15','source_unit_name'=>$room['room_label']),'before'=>null);
                elseif($stayValues)$rowOps[]=array('table'=>'gh_tenant_stays','action'=>'update','id'=>(string)$stay['id'],'values'=>$stayValues,'before'=>array_intersect_key($stay,$stayValues));
            }
            if(isset($row['emergency']) && is_array($row['emergency'])){
                $contactValues=array();$contactErrors=array();foreach(array('contact_name'=>120,'relationship'=>80,'phone'=>32) as $field=>$length)if(self::present($row['emergency'][$field]??null)){
                    $v=trim((string)$row['emergency'][$field]);if(mb_strlen($v,'UTF-8')>$length)$contactErrors[]='emergency_field_too_long_'.$field;
                    elseif($field==='phone' && !preg_match('/^0\d{8,14}$/D',self::phone($v)))$contactErrors[]='invalid_emergency_phone_requires_review';else $contactValues[$field]=$field==='phone'?self::phone($v):$v;
                }
                if($contactErrors)$issues[]=array('source'=>$ref,'reasons'=>$contactErrors,'valid_profile_operations_preserved'=>true);
                if($contactValues){
                    $contacts=array_values(array_filter($emergency,function($e)use($tenantId){return (string)$e['tenant_id']===$tenantId;}));
                    if(count($contacts)>1)$stayErrors[]='multiple_emergency_contacts_requires_review';
                    else {
                        $contact=$contacts?reset($contacts):null;$diff=array();foreach($contactValues as $k=>$v)if(!$contact||(string)$contact[$k]!==$v)$diff[$k]=$v;
                        if($diff)$rowOps[]=array('table'=>'gh_tenant_emergency_contacts','action'=>$contact?'update':'insert','id'=>$contact?(string)$contact['id']:'emergency:'.$tenantId,'values'=>$contact?$diff:$diff+array('tenant_id'=>$tenantId),'before'=>$contact?array_intersect_key($contact,$diff):null);
                    }
                }
            }
            if($errors){$issues[]=array('source'=>$ref,'reasons'=>array_values(array_unique($errors)));continue;}
            if($stayErrors)$issues[]=array('source'=>$ref,'reasons'=>array_values(array_unique($stayErrors)),'profile_contact_operations_preserved'=>true);
            // Inventory only follows an explicitly active/reserved source, never inferred vacancy.
            if(!$stayErrors && in_array($status,array('active','reserved'),true) && !in_array($room['status'],array('inactive','maintenance','mess'),true)){
                $newStatus=$status==='active'?'occupied':'reserved';
                if($room['status']!==$newStatus || (int)$room['is_public']!==0)$rowOps[]=array('table'=>'gh_rooms','action'=>'update','id'=>(string)$room['id'],'values'=>array('status'=>$newStatus,'is_public'=>0),'before'=>array('status'=>$room['status'],'is_public'=>$room['is_public']));
            }
            foreach($rowOps as $op){$op['source']=$ref;$operations[]=$op;}
            $rowResults[]=array('source'=>$ref,'tenant_id'=>$tenantId,'match'=>$match?:'new_identity','operation_count'=>count($rowOps));
            // Stage successful records so duplicate source identities cannot create duplicate people.
            $tenants[$tenantId]=array_merge($candidate?:array('id'=>$tenantId,'identity_number'=>null,'phone'=>null),$changes);
            foreach($rowOps as $op){
                if($op['table']==='gh_tenant_stays')$stays[$op['id']]=array_merge($stays[$op['id']]??array('id'=>$op['id'],'tenant_id'=>$tenantId,'room_id'=>$room['id'],'check_in_date'=>null,'check_out_date'=>null),$op['values']);
                if($op['table']==='gh_tenant_emergency_contacts'){
                    if($op['action']==='insert')$emergency[]=array_merge(array('id'=>$op['id'],'contact_name'=>null,'relationship'=>null,'phone'=>null),$op['values']);
                    else foreach($emergency as &$e)if((string)$e['id']===(string)$op['id'])$e=array_merge($e,$op['values']);unset($e);
                }
            }
        }
        $issueRows=array();foreach($issues as $issue)$issueRows[$issue['source']['index']]=true;
        return array('format_version'=>1,'database'=>$snapshot['database'],'source_sha256'=>self::fingerprint($input),'snapshot_sha256'=>self::fingerprint($snapshot['data']),'protected_sha256'=>$snapshot['protected_sha256']??array(),'operations'=>$operations,'issues'=>$issues,'rows'=>$rowResults,'summary'=>array('source_rows'=>count($input['rows']),'accepted_rows'=>count($rowResults),'issue_rows'=>count($issueRows),'issue_events'=>count($issues),'operations'=>count($operations)));
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME']??'') !== __FILE__) return;
try {
    $args=getopt('',array('input:','database:','output:','apply','approve-plan:'));
    $root=dirname(__DIR__);$private=$root.'/.deploy/tenant-update-20261015';
    $schema=$args['database']??null;
    if($schema!=='gulhouse_db' && !preg_match('/^gulhouse_test_[A-Za-z0-9_]+$/D',(string)$schema))throw new RuntimeException('Explicit local database required.');
    $inputPath=realpath($args['input']??'');
    if(!$inputPath||!is_file($inputPath))throw new RuntimeException('Normalized input JSON required.');
    $input=json_decode(file_get_contents($inputPath),true,512,JSON_THROW_ON_ERROR);
    if(!isset($input['rows'])||!is_array($input['rows']))throw new RuntimeException('Input must contain rows array.');
    foreach($input['source_files']??array() as $source)if(!isset($source['path'],$source['sha256'])||!is_file($source['path'])||!hash_equals($source['sha256'],hash_file('sha256',$source['path'])))throw new RuntimeException('Source file missing or changed since extraction.');
    $env=parse_ini_file($root.'/landing/.env');$host=$env['HOSTNAME']??'127.0.0.1';
    if(!in_array($host,array('127.0.0.1','localhost','::1'),true))throw new RuntimeException('Only local database host permitted.');
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    $db=new mysqli($host,$env['USERNAME']??'root',$env['PASSWORD']??'',$schema,(int)($env['DB_PORT']??3306));$db->set_charset('utf8mb4');
    $tables=array('gh_properties','gh_rooms','gh_tenants','gh_tenant_stays','gh_tenant_emergency_contacts','gh_contracts');
    $protectedTables=array('gh_payments','gh_room_bills','gh_contracts','gh_contract_documents','gh_deposit_transactions','gh_booking_requests');
    $protectedFingerprint=function()use($db,$protectedTables){$hashes=array();foreach($protectedTables as $t)$hashes[$t]=GulhouseTenantUpdate::fingerprint($db->query('SELECT * FROM `'.$t.'` ORDER BY id')->fetch_all(MYSQLI_ASSOC));return $hashes;};
    $snapshot=function()use($db,$schema,$tables,$protectedFingerprint){$s=array('database'=>$schema,'data'=>array());foreach($tables as $t)$s['data'][$t]=$db->query('SELECT * FROM `'.$t.'` ORDER BY id')->fetch_all(MYSQLI_ASSOC);foreach(array('gh_deposit_transactions','gh_room_bills') as $t)$s['data'][$t]=$db->query('SELECT id,stay_id FROM `'.$t.'` ORDER BY id')->fetch_all(MYSQLI_ASSOC);$s['data']['gh_booking_requests']=$db->query('SELECT id,status FROM gh_booking_requests ORDER BY id')->fetch_all(MYSQLI_ASSOC);$s['protected_sha256']=$protectedFingerprint();return $s;};
    $state=$snapshot();$plan=GulhouseTenantUpdate::plan($input,$state);$planHash=GulhouseTenantUpdate::fingerprint($plan);
    if(!is_dir($private)&&!mkdir($private,0700,true))throw new RuntimeException('Private report directory unavailable.');
    $output=$args['output']??$private.'/plan-'.$planHash.'.json';
    $outDirectory=str_replace('\\','/',realpath(dirname($output))?:'');$privateDirectory=str_replace('\\','/',realpath($private));
    if($outDirectory!==$privateDirectory && strpos($outDirectory,$privateDirectory.'/')!==0)throw new RuntimeException('Report must remain in private import directory.');
    file_put_contents($output,json_encode($plan,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX);
    if(!isset($args['apply'])){echo json_encode(array('mode'=>'dry_run','plan_sha256'=>$planHash,'summary'=>$plan['summary'],'report'=>basename($output))).PHP_EOL;exit;}
    if(($args['approve-plan']??'')!==$planHash)throw new RuntimeException('Apply requires exact reviewed --approve-plan SHA256.');
    // Root reviews the dry-run before enabling this branch. No deletes or finance writes exist.
    $lock='gh_tenant_update_'.substr(hash('sha256',$schema),0,40);
    $lockResult=$db->query("SELECT GET_LOCK('".$db->real_escape_string($lock)."',10)")->fetch_row();
    if((int)$lockResult[0]!==1)throw new RuntimeException('Another import is running.');
    try {
        $backup=$private.'/backup-'.$schema.'-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.sql';
        $dump='D:/XAMPP/mysql/bin/mysqldump.exe';
        if(!is_file($dump))throw new RuntimeException('Consistent full backup tool unavailable; apply refused.');
        $processEnv=getenv();$processEnv['MYSQL_PWD']=$env['PASSWORD']??'';
        $process=proc_open(array($dump,'--host='.$host,'--port='.(int)($env['DB_PORT']??3306),'--user='.($env['USERNAME']??'root'),'--single-transaction','--routines','--triggers','--events','--hex-blob','--default-character-set=utf8mb4',$schema),array(0=>array('pipe','r'),1=>array('file',$backup,'wb'),2=>array('pipe','w')),$pipes,$root,$processEnv);
        if(!is_resource($process))throw new RuntimeException('Backup could not start.');fclose($pipes[0]);$dumpError=stream_get_contents($pipes[2]);fclose($pipes[2]);$exit=proc_close($process);
        if($exit!==0||!is_file($backup)||filesize($backup)<1000)throw new RuntimeException('Full backup failed; apply refused.');
        $manifest=array('database'=>$schema,'backup_file'=>basename($backup),'backup_sha256'=>hash_file('sha256',$backup),'plan_sha256'=>$planHash,'snapshot_sha256'=>$plan['snapshot_sha256'],'protected_sha256'=>$plan['protected_sha256'],'created_at'=>date('c'));
        file_put_contents($backup.'.manifest.json',json_encode($manifest,JSON_PRETTY_PRINT),LOCK_EX);
        $db->begin_transaction();
        try {
            foreach(array_unique(array_merge($tables,$protectedTables)) as $table)$db->query('SELECT id FROM `'.$table.'` ORDER BY id FOR UPDATE')->fetch_all();
            $lockedState=$snapshot();
            if(GulhouseTenantUpdate::fingerprint($lockedState['data'])!==$plan['snapshot_sha256'] || $lockedState['protected_sha256']!==$plan['protected_sha256'])throw new RuntimeException('Database changed after review; rerun dry-run.');
            $ids=array();
            foreach($plan['operations'] as $op){
                $values=$op['values'];foreach(array('tenant_id') as $fk)if(isset($values[$fk])&&isset($ids[$values[$fk]]))$values[$fk]=$ids[$values[$fk]];
                $columns=array_keys($values);$escaped=array_map(function($v)use($db){return $v===null?'NULL':"'".$db->real_escape_string((string)$v)."'";},array_values($values));
                if($op['action']==='insert'){$db->query('INSERT INTO `'.$op['table'].'` (`'.implode('`,`',$columns).'`) VALUES ('.implode(',',$escaped).')');$ids[$op['id']]=$db->insert_id;}
                else{$pairs=array();foreach($columns as $i=>$c)$pairs[]='`'.$c.'`='.$escaped[$i];$id=$ids[$op['id']]??$op['id'];if(!ctype_digit((string)$id))throw new RuntimeException('Unresolved operation identity.');$db->query('UPDATE `'.$op['table'].'` SET '.implode(',',$pairs).' WHERE id='.(int)$id);}
            }
            if($protectedFingerprint()!==$plan['protected_sha256'])throw new RuntimeException('Protected financial/contract/booking records changed; rolling back import.');
            $db->commit();
        }catch(Throwable $error){$db->rollback();throw $error;}
        file_put_contents($private.'/applied-'.$planHash.'.json',json_encode($manifest+array('summary'=>$plan['summary'],'completed_at'=>date('c')),JSON_PRETTY_PRINT),LOCK_EX);
        echo json_encode(array('mode'=>'applied','plan_sha256'=>$planHash,'summary'=>$plan['summary'],'backup'=>basename($backup))).PHP_EOL;
    } finally {$db->query("SELECT RELEASE_LOCK('".$db->real_escape_string($lock)."')");}
} catch(Throwable $error) { fwrite(STDERR,'Tenant import refused: '.get_class($error).' (details stored privately if available).'.PHP_EOL); if(isset($private)&&is_dir($private))file_put_contents($private.'/last-error.json',json_encode(array('message'=>$error->getMessage(),'at'=>date('c')),JSON_PRETTY_PRINT),LOCK_EX);exit(1); }
