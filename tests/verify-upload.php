<?php
require __DIR__.'/http-helpers.php';
$createdFiles=array(); $temporary=array();
function upload_document($fields,$path,$filename,$mime) {
    global $cookie,$base;
    list($code,$html)=request('/master/index.php/documents');
    preg_match('/name="(csrf_[^"]+)" value="([a-f0-9]+)"/',$html,$token);
    $fields[$token[1]]=$token[2]; $fields['document_file']=new CURLFile($path,$mime,$filename);
    $curl=curl_init($base.'/master/index.php/ops/documents/save');
    curl_setopt_array($curl,array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$fields,CURLOPT_TIMEOUT=>20));
    $result=curl_exec($curl); curl_close($curl); return $result;
}
try {
    post('/master/index.php/login',array('username'=>'codex_trial','password'=>getenv('GULHOUSE_E2E_PASS') ?: 'LocalDemo2026!'),'/master/index.php/login');
    $contract=row('SELECT * FROM gh_contracts ORDER BY id LIMIT 1');
    if(!$contract) throw new RuntimeException('Run flow tests before upload tests.');
    $stamp=bin2hex(random_bytes(4));
    $fields=array('contract_id'=>$contract['id'],'tenant_id'=>$contract['tenant_id'],'document_type'=>'other','document_title'=>'DEMO Upload '.$stamp,'status'=>'received','received_at'=>'2026-11-01','notes'=>'Synthetic test only');
    $png=tempnam(sys_get_temp_dir(),'gh_png_'); $temporary[]=$png;
    file_put_contents($png,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lS8AAAAASUVORK5CYII='));
    upload_document($fields,$png,'demo.png','image/png');
    $document=row("SELECT * FROM gh_contract_documents WHERE document_title='DEMO Upload $stamp'");
    check($document && strpos($document['file_path'],'private/document_')===0,'valid image document stored privately');
    $privatePath=$root.'/master/application/private_documents/'.basename($document['file_path']); $createdFiles[]=$privatePath;
    check(is_file($privatePath),'uploaded document exists in private storage');
    list($code,$download)=request('/master/index.php/documents/download/'.$document['id']);
    check($code===200 && hash('sha256',$download)===hash_file('sha256',$png),'authenticated download returns exact file');
    list($code)=request('/master/application/private_documents/'.basename($document['file_path']),null,false); check($code===403,'direct document URL blocked');
    $bad=tempnam(sys_get_temp_dir(),'gh_bad_'); $temporary[]=$bad; file_put_contents($bad,'<?php echo "invalid PDF";');
    $fields['document_title']='DEMO Bad Upload '.$stamp;
    $html=upload_document($fields,$bad,'fake.pdf','application/pdf');
    $badDocument=row("SELECT * FROM gh_contract_documents WHERE document_title='DEMO Bad Upload $stamp'");
    check($badDocument && !$badDocument['file_path'],'spoofed PDF content rejected');
    check(strpos($html,'file ditolak')!==false,'rejected upload displays clear error');
    echo json_encode(array('passed'=>$checks,'complete'=>true)).PHP_EOL;
} finally {
    foreach($createdFiles as $path) if(is_file($path)) unlink($path);
    foreach($temporary as $path) if(is_file($path)) unlink($path);
    if(is_file($cookie)) unlink($cookie);
}
