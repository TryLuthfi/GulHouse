<?php
defined('BASEPATH') OR exit('No direct script access allowed');
function gh_booking_whatsapp_number($value)
{
    if(!is_scalar($value))return '';
    $value=trim((string)$value);
    if($value==='' || !preg_match('/^\+?[0-9\s().-]+$/D',$value))return '';
    $digits=preg_replace('/\D/','',$value);
    if(strpos($digits,'6208')===0)$digits='62'.substr($digits,3);
    elseif(strpos($digits,'08')===0)$digits='62'.substr($digits,1);
    elseif(strpos($digits,'8')===0)$digits='62'.$digits;
    return preg_match('/^628[0-9]{7,12}$/D',$digits)?$digits:'';
}
function gh_booking_whatsapp_url($value)
{
    $number=gh_booking_whatsapp_number($value);return $number!==''?'https://wa.me/'.$number:'';
}
