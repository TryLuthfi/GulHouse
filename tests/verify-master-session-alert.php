<?php
// Only synthetic HTTP sessions: never read a real user's browser cookie.
$root=dirname(__DIR__);
$rt=json_decode(file_get_contents($root.'/.e2e/mobile/bookingretain26/runtime.json'),TRUE);
if(!preg_match('/^gulhouse_test_mobile_bookingretain26_[0-9_]+$/D',$rt['schema']??'')||($rt['fixture']['contains_real_occupants']??TRUE)!==FALSE)throw new RuntimeException('Owned synthetic runtime required.');
putenv('GULHOUSE_DATABASE='.$rt['schema']);putenv('GULHOUSE_TEST_URL='.$rt['url']);
require __DIR__.'/http-helpers.php';
$message='Sesi belum aktif. Silakan login terlebih dahulu.';
try {
    list($code)=request('/master/index.php/dashboard',NULL,FALSE);
    check($code===303,'unauthenticated dashboard remains protected');
    list($code,$html)=request('/master/index.php/login');
    check($code===200&&strpos($html,$message)!==FALSE,'login explains the unauthenticated redirect');
    if(!preg_match('/name="(csrf_[^"]+)" value="([a-f0-9]+)"/',$html,$csrf))throw new RuntimeException('Login CSRF unavailable.');
    list($code,$html)=request('/master/index.php/login',array('username'=>$rt['username'],'password'=>$rt['password'],$csrf[1]=>$csrf[2]));
    check($code===200&&strpos($html,'name="password"')===FALSE&&strpos($html,$message)===FALSE,'fresh login reaches dashboard without stale session alert');
    $case=row('SELECT id FROM gh_resident_cases ORDER BY id LIMIT 1');
    $paths=array('/master/index.php/properties','/master/index.php/booking','/master/index.php/dashboard','/master/index.php/lifecycle');
    if($case)$paths[]='/master/index.php/lifecycle/detail/'.(int)$case['id'];
    foreach($paths as $path)for($round=0;$round<2;$round++){
        list($code,$html)=request($path);
        if($code!==200||strpos($html,'name="password"')!==FALSE||strpos($html,$message)!==FALSE)echo json_encode(array('route'=>$path,'http'=>$code,'login_form'=>strpos($html,'name="password"')!==FALSE,'session_alert'=>strpos($html,$message)!==FALSE,'php_error'=>preg_match('/Severity:|Fatal error|An uncaught Exception/',$html)===1)).PHP_EOL;
        check($code===200&&strpos($html,'name="password"')===FALSE&&strpos($html,$message)===FALSE,'authenticated module navigation stays logged in '.basename($path).' pass'.($round+1));
    }
    list($code,$html)=post('/master/index.php/logout',array(),'/master/index.php/dashboard');
    check($code===200&&strpos($html,'name="password"')!==FALSE,'explicit logout returns to login');
    list($code)=request('/master/index.php/lifecycle',NULL,FALSE);
    check($code===303,'logged-out lifecycle remains protected');
    echo json_encode(array('passed'=>$checks,'scope'=>'synthetic_fresh_session','actual_data_writes'=>0)).PHP_EOL;
} finally { @unlink($cookie); }
