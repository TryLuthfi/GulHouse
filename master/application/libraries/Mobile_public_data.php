<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/** Reuses public website facts; never evaluates the view or exposes environment contents. */
class Mobile_public_data
{
    public static function info()
    {
        $root=dirname(dirname(dirname(__DIR__))); $source=file_get_contents($root.'/landing/application/views/landing_home.php'); $locations=array();
        if(preg_match('/\$propertyMaps\s*=\s*array\((.*?)\n\s*\);/s',$source,$mapBlock)) {
            preg_match_all('/array\((.*?)\)/s',$mapBlock[1],$blocks);
            foreach($blocks[1] as $block){$row=array();foreach(array('code','name','address','maps_url','embed_url') as $key)if(preg_match("/'".$key."'\\s*=>\\s*'([^']*)'/",$block,$m))$row[$key]=$m[1];if(isset($row['maps_url']))$locations[]=$row;}
        }
        $faq=array();if(preg_match('/<section class="section faq-section">(.*?)<\/section>/s',$source,$section)){preg_match_all('/class="accordion-trigger"[^>]*>(.*?)<\/button>\s*<div class="accordion-panel">(.*?)<\/div>/s',$section[1],$matches,PREG_SET_ORDER);foreach($matches as $m)$faq[]=array('question'=>trim(strip_tags($m[1])),'answer'=>trim(strip_tags($m[2])));}
        $env=array();foreach(array($root.'/landing/.env',$root.'/master/.env',$root.'/.env') as $file)if(is_file($file)){$settings=parse_ini_file($file);foreach(array('GULHOUSE_WHATSAPP','GULHOUSE_INSTAGRAM') as $key)if(!isset($env[$key])&&isset($settings[$key]))$env[$key]=$settings[$key];}
        $number=preg_replace('/\D/','',(string)(getenv('GULHOUSE_WHATSAPP')!==FALSE?getenv('GULHOUSE_WHATSAPP'):($env['GULHOUSE_WHATSAPP']??'')));if(strpos($number,'0')===0)$number='62'.substr($number,1);if(!preg_match('/^62\d{8,13}$/D',$number)||$number==='6280000000000')$number='';
        $instagram=ltrim(trim((string)(getenv('GULHOUSE_INSTAGRAM')!==FALSE?getenv('GULHOUSE_INSTAGRAM'):($env['GULHOUSE_INSTAGRAM']??''))),'@');if(!preg_match('/^[A-Za-z0-9_](?:[A-Za-z0-9_.]{0,28}[A-Za-z0-9_])?$/D',$instagram)||strpos($instagram,'..')!==FALSE)$instagram='';
        return array('contacts'=>array('whatsapp_url'=>$number?'https://wa.me/'.$number:'','instagram_url'=>$instagram?'https://www.instagram.com/'.$instagram.'/':''),'locations'=>$locations,'faq'=>$faq);
    }
    public static function media($value)
    {
        if(!is_string($value))return $value;
        if(!preg_match('#^https?://#i',$value))return $value;
        if(strpos($value,'images.unsplash.com')!==FALSE)return NULL;
        $source=parse_url($value);
        if(is_array($source)&&preg_match('#/(?:master/index\.php|landing/index\.php|index\.php)/media/(.+)$#',$source['path']??'',$media)){
            $configured=self::public_base_setting();
            if($configured!==''){
                $public=parse_url($configured);
                if(!is_array($public)||!in_array($public['scheme']??'',array('http','https'),TRUE)||empty($public['host'])
                    ||isset($public['user'])||isset($public['pass'])||isset($public['query'])||isset($public['fragment'])
                    ||!preg_match('#^/(?:[A-Za-z0-9_-]+/)*index\.php$#D',$public['path']??'')
                    ||isset($source['user'])||isset($source['pass'])
                    ||strtolower($public['host'])!==strtolower($source['host']??'')
                    ||$public['scheme']!==($source['scheme']??'')
                    ||(int)($public['port']??($public['scheme']==='https'?443:80))!==(int)($source['port']??(($source['scheme']??'')==='https'?443:80)))return NULL;
                $value=rtrim($configured,'/').'/media/'.$media[1].(isset($source['query'])?'?'.$source['query']:'');
            }else $value=str_replace('/master/index.php/media/','/landing/index.php/media/',$value);
        }
        if(preg_match('#/media/gallery/([^/]+)/([^/]+)/([^/?]+)#',$value,$m)){$root=dirname(dirname(dirname(__DIR__))).'/uploads';$path=$root.'/'.rawurldecode($m[1]).'/'.rawurldecode($m[2]).'/'.rawurldecode($m[3]);if(!is_file($path))return NULL;}
        return $value;
    }
    private static function public_base_setting()
    {
        $value=getenv('GULHOUSE_PUBLIC_BASE_URL');
        if($value!==FALSE)return trim((string)$value);
        $root=dirname(dirname(dirname(__DIR__)));
        // Read only this public setting; never parse or return database credentials.
        foreach(array($root.'/landing/.env',$root.'/master/.env',$root.'/.env') as $file){
            if(!is_file($file)||!($handle=@fopen($file,'r')))continue;
            while(($line=fgets($handle,8192))!==FALSE){
                if(!preg_match('/^\s*GULHOUSE_PUBLIC_BASE_URL\s*=/',$line))continue;
                $parsed=@parse_ini_string($line,FALSE,INI_SCANNER_RAW);fclose($handle);
                return is_array($parsed)?trim((string)($parsed['GULHOUSE_PUBLIC_BASE_URL']??'')):'';
            }
            fclose($handle);
        }
        return '';
    }
}
