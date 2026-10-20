<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once __DIR__.'/Gallery_cover.php';

/** Shared selection of existing gallery assets, never arbitrary paths or remote URLs. */
class Gulhouse_landing_photos
{
    const METADATA = '.landing-photos.json';

    public static function catalog($root)
    {
        $root=realpath($root); $out=array();
        if(!$root)return $out;
        foreach(new DirectoryIterator($root) as $p) {
            if($p->isDot()||!$p->isDir()||$p->isLink()||$p->getFilename()==='SLIDER')continue;
            foreach(new DirectoryIterator($p->getPathname()) as $t) {
                if($t->isDot()||!$t->isDir()||$t->isLink())continue;
                foreach(new DirectoryIterator($t->getPathname()) as $f) {
                    if(!Gulhouse_gallery_cover::valid_file($t->getPathname(),$f->getFilename()))continue;
                    $key=$p->getFilename().'/'.$t->getFilename().'/'.$f->getFilename();
                    $out[$key]=array('key'=>$key,'property'=>$p->getFilename(),'type'=>$t->getFilename(),'file'=>$f->getFilename(),
                        'label'=>$p->getFilename().' · '.$t->getFilename().' · '.$f->getFilename());
                }
            }
        }
        ksort($out,SORT_NATURAL|SORT_FLAG_CASE); return $out;
    }

    public static function read($root)
    {
        $empty=array('preview'=>array(),'daily'=>''); $path=$root.'/'.self::METADATA;
        if(!is_file($path)||is_link($path)||filesize($path)>8192)return $empty;
        $data=json_decode((string)file_get_contents($path),true); $catalog=self::catalog($root);
        if(!is_array($data))return $empty;
        foreach((array)($data['preview']??array()) as $key)if(is_string($key)&&isset($catalog[$key])&&count($empty['preview'])<4&&!in_array($key,$empty['preview'],true))$empty['preview'][]=$key;
        if(is_string($data['daily']??null)&&isset($catalog[$data['daily']]))$empty['daily']=$data['daily'];
        return $empty;
    }

    public static function save($root,$preview,$daily)
    {
        $base=realpath($root);
        if(!$base||!is_array($preview)||count($preview)!==4||!is_string($daily))return false;
        $catalog=self::catalog($base); $seen=array();
        foreach($preview as $key){if(!is_string($key)||!isset($catalog[$key])||isset($seen[$key]))return false;$seen[$key]=true;}
        if(!isset($catalog[$daily]))return false;
        $path=$base.'/'.self::METADATA; if(is_link($path))return false;
        $temp=tempnam($base,'.landing-'); if(!$temp||dirname(realpath($temp))!==$base)return false;
        $data=json_encode(array('version'=>1,'preview'=>array_values($preview),'daily'=>$daily),JSON_UNESCAPED_SLASHES);
        $ok=file_put_contents($temp,$data,LOCK_EX)===strlen($data);
        if($ok){chmod($temp,0664);$ok=rename($temp,$path);}
        if(!$ok&&is_file($temp))unlink($temp);
        return $ok;
    }
}
