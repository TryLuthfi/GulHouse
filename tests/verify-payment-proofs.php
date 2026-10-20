<?php
require __DIR__ . '/http-helpers.php';
function proof_upload($paymentId, $path, $filename, $mime) {
    global $base, $cookie;
    list($code,$html)=request('/master/index.php/dashboard');
    if (! preg_match('/name="(csrf_[^"]+)" value="([a-f0-9]+)"/', $html, $token)) throw new RuntimeException('CSRF token missing');
    $fields=array($token[1]=>$token[2], 'description'=>'Synthetic linked bank evidence', 'proof_file'=>new CURLFile($path,$mime,$filename));
    $curl=curl_init($base.'/master/index.php/paymentproofs/upload/'.$paymentId);
    curl_setopt_array($curl,array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$fields,CURLOPT_TIMEOUT=>20));
    $html=curl_exec($curl); $status=curl_getinfo($curl,CURLINFO_HTTP_CODE); curl_close($curl);
    return array($status,$html);
}
function proof_login($username) {
    foreach (array('/master/index.php/dashboard','/master/index.php/maintenance','/master/index.php/rooms') as $page) {
        list($code,$html)=request($page);
        if (strpos($html,'data-logout-form')!==false) { post('/master/index.php/logout',array(),$page); break; }
    }
    post('/master/index.php/login',array('username'=>$username,'password'=>getenv('GULHOUSE_E2E_PASS') ?: 'LocalDemo2026!'),'/master/index.php/login');
}
try {
    $stamp=bin2hex(random_bytes(4));
    proof_login('codex_trial');
    $billId=(int)row("SELECT id FROM gh_room_bills ORDER BY id LIMIT 1")['id'];
    payment($billId,1000,'other','PROOF-'.$stamp);
    $paymentId=(int)row("SELECT id FROM gh_payments WHERE reference_no='PROOF-$stamp'")['id'];
    $valid=__DIR__.'/fixtures/payment-proof-valid.pdf';
    $invalid=__DIR__.'/fixtures/payment-proof-invalid.txt';
    proof_upload($paymentId,$invalid,'forged.pdf','application/pdf');
    check(count_rows("SELECT COUNT(*) n FROM gh_payment_proofs WHERE payment_id=$paymentId")===0,'proof rejects forged PDF MIME');
    proof_upload($paymentId,$valid,'payment-proof.pdf','application/pdf');
    $proof=row("SELECT * FROM gh_payment_proofs WHERE payment_id=$paymentId ORDER BY id DESC LIMIT 1");
    check($proof && (int)$proof['payment_id']===$paymentId,'proof linked to exact payment');
    $file=$root.'/master/application/private_payment_proofs/'.$proof['stored_filename'];
    check(is_file($file),'proof stored in private application folder');
    list($code,$bytes)=request('/master/index.php/paymentproofs/download/'.$proof['id']);
    check($code===200 && hash('sha256',$bytes)===hash_file('sha256',$valid),'authenticated proof download matches uploaded bytes');
    list($code)=request('/master/application/private_payment_proofs/'.$proof['stored_filename']);
    check(in_array($code,array(403,404),true),'direct private proof path denied');
    proof_login('demo_viewer');
    list($code,$html)=request('/master/index.php/paymentproofs/index/'.$paymentId);
    check($code===200 && strpos($html,'name="proof_file"')===false,'viewer proof page is read only');
    list($code)=request('/master/index.php/paymentproofs/download/'.$proof['id']);
    check($code===200,'viewer can download invoice evidence');
    list($code)=proof_upload($paymentId,$valid,'forbidden.pdf','application/pdf');
    check($code===403,'viewer upload denied');
    list($code)=post('/master/index.php/paymentproofs/delete/'.$proof['id'],array(),'/master/index.php/dashboard');
    check($code===403,'viewer deletion denied');
    proof_login('demo_maintenance');
    list($code)=request('/master/index.php/paymentproofs/download/'.$proof['id']);
    check($code===403,'maintenance evidence access denied');
    proof_login('codex_trial');
    $db->query("INSERT INTO gh_payment_proofs(payment_id,stored_filename,original_filename,mime_type,file_size) VALUES($paymentId,'../../outside.pdf','synthetic-invalid-path','application/pdf',1)");
    $badId=(int)$db->insert_id;
    list($code)=request('/master/index.php/paymentproofs/download/'.$badId);
    check($code===404,'forged traversal metadata cannot download');
    $db->query('DELETE FROM gh_payment_proofs WHERE id='.$badId);
    post('/master/index.php/paymentproofs/delete/'.$proof['id'],array(),'/master/index.php/dashboard');
    clearstatcache(TRUE, $file);
    check(!is_file($file) && count_rows('SELECT COUNT(*) n FROM gh_payment_proofs WHERE id='.(int)$proof['id'])===0,'proof deletion removes exact private file and metadata');
    proof_upload($paymentId,$valid,'payment-delete-proof.pdf','application/pdf');
    $proof=row("SELECT * FROM gh_payment_proofs WHERE payment_id=$paymentId ORDER BY id DESC LIMIT 1");
    $file=$root.'/master/application/private_payment_proofs/'.$proof['stored_filename'];
    post('/master/index.php/payments/delete-payment/'.$paymentId,array(),'/master/index.php/payments');
    clearstatcache(TRUE, $file);
    check(count_rows("SELECT COUNT(*) n FROM gh_payment_proofs WHERE payment_id=$paymentId")===0 && !is_file($file),'payment deletion cascades proof metadata and private file cleanup');
    echo json_encode(array('passed'=>$checks,'complete'=>true,'schema'=>$schema)).PHP_EOL;
} finally { if(is_file($cookie)) unlink($cookie); }
