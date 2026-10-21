<?php
define('BASEPATH', __DIR__);
require dirname(__DIR__).'/master/application/libraries/Gallery_focus.php';
$dir=sys_get_temp_dir().'/ciko-focus-'.bin2hex(random_bytes(6)); mkdir($dir);
$png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
file_put_contents($dir.'/a.png',$png);file_put_contents($dir.'/b.png',$png);file_put_contents($dir.'/not-image.jpg','text');
$n=0; function check($ok,$name){global $n;if(!$ok)throw new Exception($name);$n++;}
try {
check(Gulhouse_gallery_focus::get($dir,'a.png')===array('x'=>50,'y'=>70),'default');
check(Gulhouse_gallery_focus::save($dir,'a.png',0,100),'boundary save');
check(Gulhouse_gallery_focus::get($dir,'a.png')===array('x'=>0,'y'=>100),'boundary readback');
check(Gulhouse_gallery_focus::save($dir,'b.png','31','82'),'second photo');
check(Gulhouse_gallery_focus::get($dir,'a.png')===array('x'=>0,'y'=>100),'preserve other photo');
check(Gulhouse_gallery_focus::get($dir,'b.png')===array('x'=>31,'y'=>82),'second readback');
foreach(array(-1,101,1.5,'1.5','01','+1','1e1','',null,array(2),true) as $v){$old=file_get_contents($dir.'/.gallery-focus.json');check(!Gulhouse_gallery_focus::save($dir,'a.png',$v,20),'invalid coordinate rejected');check($old===file_get_contents($dir.'/.gallery-focus.json'),'invalid write unchanged');}
foreach(array('../a.png','a/../a.png','a\\b.png','not-image.jpg','absent.png',array('a.png')) as $name)check(!Gulhouse_gallery_focus::save($dir,$name,50,70),'invalid file rejected');
check(Gulhouse_gallery_focus::save($dir,'a.png',12,34),'update existing');
check(count(json_decode(file_get_contents($dir.'/.gallery-focus.json'),true)['photos'])===2,'one entry per file');
check(Gulhouse_gallery_focus::get($dir,'b.png')===array('x'=>31,'y'=>82),'update preserves other');
file_put_contents($dir.'/.gallery-focus.json','{bad');check(Gulhouse_gallery_focus::get($dir,'a.png')===array('x'=>50,'y'=>70),'corrupt metadata safe default');
file_put_contents($dir.'/.gallery-focus.json',json_encode(array('photos'=>array('a.png'=>array('x'=>101,'y'=>50)))));check(Gulhouse_gallery_focus::get($dir,'a.png')===array('x'=>50,'y'=>70),'out of range metadata default');
check(count(glob($dir.'/.focus-*'))===0,'no leftover temporary files');
echo json_encode(array('pass'=>$n,'scope'=>'isolated helper filesystem; no business DB or real gallery writes')).PHP_EOL;
} finally { foreach(new DirectoryIterator($dir) as $file)if(!$file->isDot())unlink($file->getPathname());rmdir($dir);}
