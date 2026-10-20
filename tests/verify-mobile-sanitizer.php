<?php
// Pure serializer checks; no database, secrets or runtime sessions required.
define('BASEPATH',dirname(__DIR__).'/master/system/');
define('APPPATH',dirname(__DIR__).'/master/application/');
class CI_Model {}
require APPPATH.'models/Mobile_service.php';
$service=(new ReflectionClass('Mobile_service'))->newInstanceWithoutConstructor();
$result=$service->sanitize(array(
    'schema'=>array('fields'=>array('password'=>array('type'=>'password','value'=>'must-not-leak','default'=>'must-not-leak'))),
    'record'=>array('password'=>'must-not-leak','password_hash'=>'must-not-leak'),
    'inspection'=>array('ocr_json'=>'must-not-leak','ocr'=>array('ok'=>TRUE,'text'=>'AC: perlu diperiksa','items'=>array(array('item_name'=>'AC','condition_status'=>'uninspected','requires_review'=>TRUE)),'file_path'=>'must-not-leak')),
));
if(($result['schema']['fields']['password']['type']??'')!=='password')throw new RuntimeException('Password input metadata missing.');
if(strpos(json_encode($result),'must-not-leak')!==FALSE)throw new RuntimeException('Sensitive payload leaked.');
if(empty($result['inspection']['ocr']['ok'])||$result['inspection']['ocr']['items'][0]['condition_status']!=='uninspected')throw new RuntimeException('OCR review suggestions missing.');
if($result['inspection']['ocr']['text']!=='AC: perlu diperiksa')throw new RuntimeException('OCR review text missing.');
echo "4/4 serializer checks PASS\n";
