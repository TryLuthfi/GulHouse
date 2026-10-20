<?php
/** HTTP CSV regression: synthetic database and loopback server only. Never opens a spreadsheet. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
$schema = getenv('GULHOUSE_DATABASE');
$base = rtrim(getenv('GULHOUSE_TEST_URL') ?: 'http://127.0.0.1:8032', '/');
if (! is_string($schema) || ! preg_match('/^gulhouse_test_[A-Za-z0-9_]+$/D', $schema)
    || ! in_array(parse_url($base, PHP_URL_HOST), array('127.0.0.1','localhost','::1'), TRUE)) {
    fwrite(STDERR, "Synthetic database and loopback URL required.\n"); exit(2);
}
$settings = array();
foreach (array($root.'/master/.env',$root.'/.env',$root.'/landing/.env') as $path) {
    if (is_file($path)) { $settings = parse_ini_file($path); break; }
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli(getenv('GH_DB_HOSTNAME') ?: ($settings['HOSTNAME'] ?? '127.0.0.1'),
    getenv('GH_DB_USERNAME') ?: ($settings['USERNAME'] ?? 'root'),
    getenv('GH_DB_PASSWORD') !== FALSE ? getenv('GH_DB_PASSWORD') : ($settings['PASSWORD'] ?? ''),
    $schema,(int)(getenv('GH_DB_DB_PORT') ?: ($settings['DB_PORT'] ?? 3306)));
$db->set_charset('utf8mb4');
$jar = tempnam(sys_get_temp_dir(),'gh_csv_'); $ids = array(); $checks = 0; $exitCode = 0;
function csv_check($condition,$label) {
    global $checks; $checks++;
    if (! $condition) throw new RuntimeException('FAIL: '.$label);
    echo 'PASS: '.$label.PHP_EOL;
}
function csv_request($path,$post=NULL) {
    global $base,$jar;
    $curl=curl_init($base.$path);
    curl_setopt_array($curl,array(CURLOPT_RETURNTRANSFER=>TRUE,CURLOPT_HEADER=>TRUE,
        CURLOPT_COOKIEFILE=>$jar,CURLOPT_COOKIEJAR=>$jar,CURLOPT_FOLLOWLOCATION=>FALSE,
        CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>30));
    if ($post!==NULL) { curl_setopt($curl,CURLOPT_POST,TRUE); curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($post)); }
    $raw=curl_exec($curl); if ($raw===FALSE) throw new RuntimeException('Local HTTP request failed.');
    $status=curl_getinfo($curl,CURLINFO_HTTP_CODE); $length=curl_getinfo($curl,CURLINFO_HEADER_SIZE); curl_close($curl);
    return array('status'=>$status,'headers'=>substr($raw,0,$length),'body'=>substr($raw,$length));
}
try {
    $login=csv_request('/master/index.php/login');
    csv_check($login['status']===200,'login available');
    preg_match('/name="csrf_gh_master" value="([^"]+)"/',$login['body'],$token);
    csv_check(!empty($token[1]),'login has CSRF');
    $auth=csv_request('/master/index.php/login',array('username'=>getenv('GULHOUSE_E2E_USER') ?: 'codex_trial',
        'password'=>getenv('GULHOUSE_E2E_PASS') ?: 'LocalDemo2026!','csrf_gh_master'=>$token[1]));
    csv_check($auth['status']===303,'synthetic administrator authenticated');
    $prefix='CSV-SAFE-'.bin2hex(random_bytes(5));
    $values=array('=HYPERLINK("http://localhost/")','+SUM(1,2)','-SUM(1,2)','@SUM(1,2)',
        " \t=1+1","\r\n@SUM(1,2)","\x01+1+1","\xC2\xA0-1+1","\xEF\xBB\xBF=1+1",'Normal complaint','12345','Text, "quoted"');
    $statement=$db->prepare("INSERT INTO gh_complaints(ticket_no,category,complaint_title,status) VALUES(?,'other',?,'open')");
    foreach($values as $index=>$value) {
        $ticket=$prefix.'-'.$index; $statement->bind_param('ss',$ticket,$value); $statement->execute(); $ids[]=(int)$db->insert_id;
    }
    $csv=csv_request('/master/index.php/operations/export/complaints?q='.rawurlencode($prefix));
    csv_check($csv['status']===200 && stripos($csv['headers'],'text/csv')!==FALSE,'authorized export returns CSV');
    $stream=fopen('php://temp','r+'); fwrite($stream,$csv['body']); rewind($stream);
    $header=fgetcsv($stream); $export=array();
    while(($line=fgetcsv($stream))!==FALSE) { if(count($line)===count($header)) $export[$line[0]]=$line; }
    fclose($stream); csv_check(count($export)===count($values),'filtered export contains exactly the synthetic fixtures');
    $titleColumn=array_search('Keluhan',$header,TRUE); csv_check($titleColumn!==FALSE,'complaint CSV title column identified');
    foreach($values as $index=>$value) {
        $ticket=$prefix.'-'.$index; $expected=$index<9 ? "'".$value : $value;
        csv_check(isset($export[$ticket]) && $export[$ticket][$titleColumn]===$expected,'CSV cell '.$index.' is safely escaped or unchanged');
        $read=$db->prepare('SELECT complaint_title FROM gh_complaints WHERE id=?'); $id=$ids[$index];
        $read->bind_param('i',$id); $read->execute(); $stored=$read->get_result()->fetch_assoc();
        csv_check($stored['complaint_title']===$value,'stored source '.$index.' remains unchanged');
    }
    echo 'CSV HTTP regression passed: '.$checks.' assertions.'.PHP_EOL;
} catch(Throwable $error) { fwrite(STDERR,$error->getMessage().PHP_EOL); $exitCode=1; }
finally {
    foreach($ids as $id) $db->query('DELETE FROM gh_complaints WHERE id='.(int)$id);
    if(is_file($jar)) unlink($jar);
}
exit($exitCode);
