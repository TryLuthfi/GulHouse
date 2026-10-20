<?php
/** Explicit operator-only registration/GC. No mass delete command and no web demo toggle. */
require __DIR__.'/booking-retention-bootstrap.php';
$opts=getopt('',array('schema:','command:','booking:','operator:','attestation:','tenant:','stay:','contract:','fingerprint:','apply','allow-real-registration'));
try {
    $schema=$opts['schema']??'';$command=$opts['command']??'';$id=$opts['booking']??'';
    if($command!=='init-key' && !preg_match('/^[1-9][0-9]*$/D',(string)$id))throw new RuntimeException('Satu booking ID positif wajib ditentukan.');
    $test=preg_match('/^gulhouse_test_[A-Za-z0-9_]+$/D',$schema);
    if(!$test && !isset($opts['allow-real-registration']))throw new RuntimeException('Schema nyata memerlukan --allow-real-registration eksplisit.');
    list($model,$db)=booking_retention_cli($schema);
    $legacy=array();foreach(array('tenant'=>'tenant_id','stay'=>'stay_id','contract'=>'contract_id') as $arg=>$field) if(isset($opts[$arg])) {if(!preg_match('/^[1-9][0-9]*$/D',$opts[$arg]))throw new RuntimeException('ID manifest tidak valid.');$legacy[$field]=(int)$opts[$arg];}
    if($command==='init-key') {
        if(!isset($opts['apply']))throw new RuntimeException('Inisialisasi kunci memerlukan --apply.');
        $out=array('initialized'=>$model->init_key(),'key_value_disclosed'=>FALSE);
    } elseif($command==='plan-legacy')$out=$model->legacy_plan((int)$id,$legacy);
    elseif($command==='register') {
        if(!isset($opts['apply']))throw new RuntimeException('Registrasi hanya dijalankan dengan --apply. Tidak ada perubahan.');
        if(isset($opts['fingerprint']))$legacy['expected_hash']=$opts['fingerprint'];
        $model->register_demo((int)$id,$opts['operator']??'',$opts['attestation']??'',$legacy);
        $out=array('registered'=>TRUE,'booking_id'=>(int)$id);
    } elseif($command==='gc') {
        if(!isset($opts['apply']))throw new RuntimeException('GC hanya dijalankan dengan --apply.');
        $out=$model->collect_files((int)$id);
    } else throw new RuntimeException('Command wajib plan-legacy, register, atau gc. Tidak ada CLI purge.');
    echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
} catch(Throwable $e) {fwrite(STDERR,'Ditolak: '.(get_class($e)==='RuntimeException'?$e->getMessage():'Operasi gagal; periksa log privat operator.').PHP_EOL);exit(1);}
