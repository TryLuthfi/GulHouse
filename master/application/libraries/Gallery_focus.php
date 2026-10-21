<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once __DIR__.'/Gallery_cover.php';

class Gulhouse_gallery_focus
{
    const METADATA='.gallery-focus.json';

    private static function coordinate($value)
    {
        return (is_int($value)||is_string($value)) && preg_match('/^(?:0|[1-9][0-9]?|100)$/D',(string)$value);
    }

    private static function read($directory)
    {
        $path=$directory.'/'.self::METADATA;
        if(!is_file($path)||is_link($path)||filesize($path)>65536)return array();
        $data=json_decode((string)file_get_contents($path),true);
        return is_array($data)&&isset($data['photos'])&&is_array($data['photos'])?$data['photos']:array();
    }

    public static function get($directory,$file)
    {
        $default=array('x'=>50,'y'=>70);
        if(!Gulhouse_gallery_cover::valid_file($directory,$file))return $default;
        $photos=self::read($directory);$focus=$photos[$file]??null;
        if(!is_array($focus)||!self::coordinate($focus['x']??null)||!self::coordinate($focus['y']??null))return $default;
        return array('x'=>(int)$focus['x'],'y'=>(int)$focus['y']);
    }

    public static function save($directory,$file,$x,$y)
    {
        if(!self::coordinate($x)||!self::coordinate($y)||!Gulhouse_gallery_cover::valid_file($directory,$file))return false;
        $base=realpath($directory);$path=$base.'/'.self::METADATA;if(is_link($path))return false;
        $photos=self::read($base);$photos[$file]=array('x'=>(int)$x,'y'=>(int)$y);
        $data=json_encode(array('version'=>1,'photos'=>$photos),JSON_UNESCAPED_SLASHES);
        if(strlen($data)>65536)return false;
        $temp=tempnam($base,'.focus-');if(!$temp||dirname(realpath($temp))!==$base)return false;
        $ok=file_put_contents($temp,$data,LOCK_EX)===strlen($data);
        if($ok){chmod($temp,0664);$ok=rename($temp,$path);}
        if(!$ok&&is_file($temp))unlink($temp);
        return $ok;
    }
}
