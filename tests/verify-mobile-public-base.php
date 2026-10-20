<?php
// Read-only URL normalization tests; no credentials or database access.
define('BASEPATH',dirname(__DIR__).'/master/system/');
require dirname(__DIR__).'/master/application/libraries/Mobile_public_data.php';
$root=dirname(__DIR__);$sample=NULL;
foreach(glob($root.'/uploads/*/*/*.jpg') as $file){$relative=str_replace('\\','/',substr($file,strlen($root.'/uploads/')));if(count(explode('/',$relative))===3){$sample=$relative;break;}}
if(!$sample)throw new RuntimeException('An existing public gallery JPEG is required.');
$suffix='/media/gallery/'.implode('/',array_map('rawurlencode',explode('/',$sample)));
$saved=getenv('GULHOUSE_PUBLIC_BASE_URL');$passed=0;
function check_url($actual,$expected){global $passed;if($actual!==$expected)throw new RuntimeException('Public media URL assertion failed.');$passed++;}
try{
    putenv('GULHOUSE_PUBLIC_BASE_URL=https://gulhouse.co.id/index.php');
    check_url(Mobile_public_data::media('https://gulhouse.co.id/master/index.php'.$suffix),'https://gulhouse.co.id/index.php'.$suffix);
    putenv('GULHOUSE_PUBLIC_BASE_URL=https://gulhouse.co.id/qa-native/landing/index.php');
    check_url(Mobile_public_data::media('https://gulhouse.co.id/qa-native/master/index.php'.$suffix),'https://gulhouse.co.id/qa-native/landing/index.php'.$suffix);
    putenv('GULHOUSE_PUBLIC_BASE_URL=');
    check_url(Mobile_public_data::media('http://127.0.0.1:61490/master/index.php'.$suffix),'http://127.0.0.1:61490/landing/index.php'.$suffix);
    check_url(Mobile_public_data::media('http://127.0.0.1:61490/qa-native/master/index.php'.$suffix),'http://127.0.0.1:61490/qa-native/landing/index.php'.$suffix);
    foreach(array('https://unrelated.invalid/index.php','https://user:pass@gulhouse.co.id/index.php','https://gulhouse.co.id/index.php?x=1','https://gulhouse.co.id/index.php#fragment','https://gulhouse.co.id/../index.php','https://gulhouse.co.id/landing','http://gulhouse.co.id/index.php','https://gulhouse.co.id:444/index.php') as $invalid){
        putenv('GULHOUSE_PUBLIC_BASE_URL='.$invalid);
        check_url(Mobile_public_data::media('https://gulhouse.co.id/master/index.php'.$suffix),NULL);
    }
    check_url(Mobile_public_data::media('https://www.google.com/maps?q=GulHouse'),'https://www.google.com/maps?q=GulHouse');
    check_url(Mobile_public_data::media('https://images.unsplash.com/example.jpg'),NULL);
    putenv('GULHOUSE_PUBLIC_BASE_URL=https://gulhouse.co.id/index.php');
    check_url(Mobile_public_data::media('https://gulhouse.co.id/master/index.php/media/gallery/NO_PROPERTY/NO_TYPE/missing.jpg'),NULL);
    echo json_encode(array('passed'=>$passed,'database_connections'=>0,'production_mutations'=>0)).PHP_EOL;
}finally{putenv($saved===FALSE?'GULHOUSE_PUBLIC_BASE_URL':'GULHOUSE_PUBLIC_BASE_URL='.$saved);}
