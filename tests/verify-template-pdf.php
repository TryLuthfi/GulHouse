<?php
require __DIR__.'/http-helpers.php';
$pdfPath='C:/Users/TKM/Pictures/GUL HOUSE/Peraturan Kost Gulhouse (Paraf).pdf';
$stored=''; $bad='';
function template_upload($fields,$file,$filename) {
    global $cookie,$base;
    list($code,$html)=request('/master/index.php/templates');
    preg_match('/name="(csrf_[^"]+)" value="([a-f0-9]+)"/',$html,$token);
    $fields[$token[1]]=$token[2];
    $fields['template_file']=new CURLFile($file,'application/pdf',$filename);
    $curl=curl_init($base.'/master/index.php/ops/templates/save');
    curl_setopt_array($curl,array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$fields,CURLOPT_TIMEOUT=>20));
    $html=curl_exec($curl); curl_close($curl); return $html;
}
try {
    post('/master/index.php/login',array('username'=>getenv('GULHOUSE_E2E_USER'),'password'=>getenv('GULHOUSE_E2E_PASS')),'/master/index.php/login');
    $template=row("SELECT * FROM gh_document_templates WHERE template_key='house_rules'");
    check((bool)$template,'house rules template available');
    $fields=array_intersect_key($template,array_flip(array('id','template_key','title','document_type','body_template','is_active')));
    $html=template_upload($fields,$pdfPath,'rules.pdf');
    check(strpos($html,'Template Dokumen tersimpan.')!==false,'PDF upload through authenticated save route');
    $stored=$root.'/master/application/private_documents/template_'.substr(hash('sha256',$schema),0,16).'_'.$template['id'].'.pdf';
    check(is_file($stored) && hash_file('sha256',$stored)===hash_file('sha256',$pdfPath),'stored attachment matches original bytes');
    list($code,$pdf)=request('/master/index.php/templates/print/house_rules');
    check($code===200 && hash('sha256',$pdf)===hash_file('sha256',$pdfPath),'Print serves original PDF');
    list($code,$landingPdf)=request('/landing/index.php/documents/house_rules');
    check($code===200 && hash('sha256',$landingPdf)===hash('sha256',$pdf),'landing and master serve identical PDF bytes');
    list($code)=request('/master/application/private_documents/'.basename($stored),null,false);
    check($code===403,'direct private PDF URL blocked');
    $bad=tempnam(sys_get_temp_dir(),'gh_template_'); file_put_contents($bad,'<?php echo "fake PDF";');
    $html=template_upload($fields,$bad,'fake.pdf');
    check(strpos($html,'lampiran ditolak')!==false,'spoofed PDF rejected');
    check(hash_file('sha256',$stored)===hash_file('sha256',$pdfPath),'rejected replacement preserves existing PDF');
    $db->query("UPDATE gh_document_templates SET is_active=0 WHERE id=".(int)$template['id']);
    list($code)=request('/landing/index.php/documents/house_rules',null,false);
    check($code===404,'inactive house rules not publicly served');
    $db->query("UPDATE gh_document_templates SET is_active=1 WHERE id=".(int)$template['id']);
    list($code,$html)=request('/master/index.php/templates/print/tenant_statement');
    check($code===200 && strpos($html,'Surat Pernyataan')!==false,'text-only template still printable');
    $curl=curl_init($base.'/master/index.php/templates/print/house_rules');
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true); curl_exec($curl); $code=curl_getinfo($curl,CURLINFO_HTTP_CODE); curl_close($curl);
    check($code===303 || $code===302,'anonymous PDF access requires login');
    $curl=curl_init($base.'/landing/index.php/documents/house_rules');
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true); $publicPdf=curl_exec($curl); $code=curl_getinfo($curl,CURLINFO_HTTP_CODE); curl_close($curl);
    check($code===200 && hash('sha256',$publicPdf)===hash_file('sha256',$pdfPath),'public house rules serves same PDF without admin login');
    unlink($stored);
    $db->query("UPDATE gh_document_templates SET body_template='Unique synthetic house rules fallback' WHERE id=".(int)$template['id']);
    list($code,$fallback)=request('/landing/index.php/documents/house_rules');
    check($code===200 && strpos($fallback,'Unique synthetic house rules fallback')!==false,'landing text fallback follows master template content');
    echo json_encode(array('passed'=>$checks,'complete'=>true)).PHP_EOL;
} finally {
    if ($stored && is_file($stored)) unlink($stored);
    if ($bad && is_file($bad)) unlink($bad);
    if (is_file($cookie)) unlink($cookie);
}
