<?php
if(PHP_SAPI!=='cli')exit(2);
$root=dirname(__DIR__);define('BASEPATH',$root.'/master/system/');
require $root.'/master/application/libraries/Landing_photos.php';
$upload=$root.'/uploads';$audit=$root.'/.e2e/landing-photo-manager-20261021';
if(!is_dir($audit))mkdir($audit,0700,true);
$metadata=$upload.'/'.Gulhouse_landing_photos::METADATA;
if(is_file($metadata)&&!copy($metadata,$audit.'/before-'.date('Ymd-His').'.json'))throw new RuntimeException('Backup failed');
$preview=array('GH2/VIP/kamar_06_vipbaru-20261020.webp','GH2/DELUXE/kamar_01_402_r2.webp',
 'GH2/VIP/UMUM_fasilitas_bersama_03_dapur_20261021.webp','GH2/VIP/UMUM_fasilitas_bersama_01_ruang_santai_20261021.webp');
if(!Gulhouse_landing_photos::save($upload,$preview,$preview[3]))throw new RuntimeException('Selection failed');
echo json_encode(Gulhouse_landing_photos::read($upload),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
