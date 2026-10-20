<?php
// Real model/filesystem regression: no database or network required.
define('BASEPATH', __DIR__);
define('FCPATH', dirname(__DIR__).'/landing/');
class CI_Model {}
function base_url($path = '') { return 'http://localhost/GulHouse/landing/'.$path; }
require dirname(__DIR__).'/landing/application/models/Landing_model.php';
$model = (new ReflectionClass('Landing_model'))->newInstanceWithoutConstructor();
$passed = 0;
function expectGallery($condition, $message) { global $passed; if (!$condition) throw new RuntimeException($message); $passed++; }
foreach (array(
    array('code'=>'GH 3', 'type_name'=>'Standart', 'name'=>'GH 3 - Standart'),
    array('code'=>'GH 2', 'type_name'=>'MISSING_TYPE_QA', 'name'=>'Missing type'),
    array('code'=>'MISSING_PROPERTY_QA', 'type_name'=>'VIP', 'name'=>'Missing property'),
    array('code'=>'', 'type_name'=>'VIP', 'name'=>'Missing code'),
    array('code'=>'GH 2', 'type_name'=>'', 'name'=>'Missing type'),
) as $room) {
    expectGallery($model->get_room_gallery($room) === array(), 'Empty gallery borrowed photos: '.$room['name']);
}
$method = new ReflectionMethod('Landing_model', 'get_uploaded_room_gallery'); $method->setAccessible(true);
foreach (array(array('GH 2','VIP','GH2','VIP'),array('GH 1','Deluxe','GH1','DELUXE'),array('GH 2','Standart','GH2','STANDART')) as $case) {
    list($code,$type,$folder,$typeFolder) = $case;
    $room = array('code'=>$code,'type_name'=>$type,'name'=>$code.' - '.$type);
    $own = $method->invoke($model, $room);
    expectGallery(count($own)>0, 'Existing own photo fixtures missing');
    $gallery = $model->get_room_gallery($room);
    expectGallery($gallery === $own, 'Own uploaded gallery changed');
    foreach ($gallery as $photo) expectGallery(strpos($photo['src'], '/media/gallery/'.$folder.'/'.$typeFolder.'/') !== false, 'Wrong photo scope');
}
echo json_encode(array('passed'=>$passed,'database_connections'=>0,'writes'=>0)).PHP_EOL;
