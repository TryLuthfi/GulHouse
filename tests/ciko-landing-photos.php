<?php
define('BASEPATH',__DIR__);
require dirname(__DIR__).'/master/application/libraries/Landing_photos.php';
$base=dirname(__DIR__).'/.e2e/landing-photos-20261021/ciko-'.date('His').'-'.getmypid();
mkdir($base.'/GH2/VIP',0777,true);mkdir($base.'/SLIDER/OTHER',0777,true);
$png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jvX8AAAAASUVORK5CYII=');
foreach(array('a.png','b.png','c.png','d.png','e.png') as $f)file_put_contents($base.'/GH2/VIP/'.$f,$png);
file_put_contents($base.'/GH2/VIP/fake.jpg','not image');file_put_contents($base.'/SLIDER/OTHER/hidden.png',$png);
$checks=array();function ck($n,$v){global $checks;$checks[]=array('name'=>$n,'status'=>$v?'PASS':'FAIL');}
$keys=array('GH2/VIP/d.png','GH2/VIP/b.png','GH2/VIP/a.png','GH2/VIP/c.png');$daily='GH2/VIP/e.png';
ck('Catalog only decodable room images; slider excluded',count(Gulhouse_landing_photos::catalog($base))===5);
ck('Empty metadata safe',Gulhouse_landing_photos::read($base)===array('preview'=>array(),'daily'=>''));
ck('Four previews and independent daily saved',Gulhouse_landing_photos::save($base,$keys,$daily));
ck('Preview custom order survives round trip',Gulhouse_landing_photos::read($base)===array('preview'=>$keys,'daily'=>$daily));
$hash=hash_file('sha256',$base.'/.landing-photos.json');
$bad=array(array(),array_slice($keys,0,3),array_merge($keys,array($daily)),array($keys[0],$keys[0],$keys[2],$keys[3]),array(array('x'),$keys[1],$keys[2],$keys[3]),array('../outside.png',$keys[1],$keys[2],$keys[3]),array('GH2/VIP/../a.png',$keys[1],$keys[2],$keys[3]),array('GH2/VIP/fake.jpg',$keys[1],$keys[2],$keys[3]),array('https://example.com/a.png',$keys[1],$keys[2],$keys[3]));
foreach($bad as$i=>$v)ck('Invalid preview rejected and metadata preserved '.$i,!Gulhouse_landing_photos::save($base,$v,$daily)&&hash_file('sha256',$base.'/.landing-photos.json')===$hash);
foreach(array(null,array($daily),'missing','../e.png')as$i=>$v)ck('Invalid daily rejected and metadata preserved '.$i,!Gulhouse_landing_photos::save($base,$keys,$v)&&hash_file('sha256',$base.'/.landing-photos.json')===$hash);
ck('Daily may reuse preview intentionally',Gulhouse_landing_photos::save($base,$keys,$keys[0]));
unlink($base.'/GH2/VIP/d.png');$s=Gulhouse_landing_photos::read($base);
ck('Deleted photo omitted without foreign fallback',$s===array('preview'=>array_slice($keys,1),'daily'=>''));
file_put_contents($base.'/.landing-photos.json','{invalid');ck('Corrupt metadata safely empty',Gulhouse_landing_photos::read($base)===array('preview'=>array(),'daily'=>''));
file_put_contents($base.'/.landing-photos.json',str_repeat(' ',8193));ck('Oversized metadata safely empty',Gulhouse_landing_photos::read($base)===array('preview'=>array(),'daily'=>''));
file_put_contents($base.'/.landing-photos.json',json_encode(array('preview'=>array('../oops',array('bad'),'GH2/VIP/a.png','GH2/VIP/a.png'),'daily'=>array('x'))));clearstatcache();
ck('Tampered metadata arrays and duplicates filtered',Gulhouse_landing_photos::read($base)===array('preview'=>array('GH2/VIP/a.png'),'daily'=>''));
$result=array('checks'=>$checks,'passed'=>count(array_filter($checks,function($v){return$v['status']==='PASS';})),'failed'=>count(array_filter($checks,function($v){return$v['status']==='FAIL';})),'fixture'=>$base);
file_put_contents($base.'/results.json',json_encode($result,JSON_PRETTY_PRINT));echo json_encode($result,JSON_PRETTY_PRINT);exit($result['failed']?1:0);
