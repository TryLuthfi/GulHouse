<?php
// HTTP + database assertions against a synthetic gulhouse_test_* database only.
date_default_timezone_set('Asia/Jakarta');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$root = dirname(__DIR__);
$schema = getenv('GULHOUSE_DATABASE');
if (!$schema || !preg_match('/^gulhouse_test_[a-zA-Z0-9_]+$/D',$schema)) throw new RuntimeException('Isolated schema required.');
$env = parse_ini_file($root . '/landing/.env');
$db = new mysqli($env['HOSTNAME'] ?? '127.0.0.1',$env['USERNAME'] ?? 'root',$env['PASSWORD'] ?? '',$schema,(int)($env['DB_PORT'] ?? 3306));
$db->set_charset('utf8mb4');
$base = getenv('GULHOUSE_TEST_URL') ?: 'http://127.0.0.1:8032';
if (!preg_match('#^http://127\.0\.0\.1:\d+$#D',$base)) throw new RuntimeException('Loopback test URL required.');
$cookie = tempnam(sys_get_temp_dir(),'gh_test_');
$checks = 0;
function check($ok,$label) { global $checks; if (!$ok) throw new RuntimeException('FAIL: '.$label); $checks++; echo 'PASS '.$label.PHP_EOL; }
function request($path,$fields=null,$follow=true) {
    global $base,$cookie;
    $curl=curl_init($base.$path);
    curl_setopt_array($curl,array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>$follow,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_TIMEOUT=>20));
    if ($fields!==null) curl_setopt_array($curl,array(CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($fields)));
    $html=curl_exec($curl); $code=curl_getinfo($curl,CURLINFO_HTTP_CODE); $error=curl_error($curl); curl_close($curl);
    if ($html===false) throw new RuntimeException('HTTP error '.$error);
    return array($code,$html);
}
function post($path,$fields,$page='/master/index.php/contracts') {
    list($code,$html)=request($page);
    if (!preg_match('/name="(csrf_[^"]+)" value="([a-f0-9]+)"/',$html,$token)) throw new RuntimeException('CSRF token missing on '.$page);
    $fields[$token[1]]=$token[2];
    return request($path,$fields);
}
function row($sql) { global $db; return $db->query($sql)->fetch_assoc(); }
function count_rows($sql) { return (int)row($sql)['n']; }
function bill($id) { return row('SELECT * FROM gh_room_bills WHERE id='.(int)$id); }
function payment($billId,$amount,$type,$ref,$paymentId=0) {
    return post('/master/index.php/payments/save-payment',array('payment_id'=>$paymentId,'room_bill_id'=>$billId,'payment_date'=>'2026-11-02','payment_date_text'=>'','amount'=>$amount,'payment_type'=>$type,'method'=>'Transfer demo','reference_no'=>$ref,'notes'=>'Synthetic flow test'),'/master/index.php/payments');
}
