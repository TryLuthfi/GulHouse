<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function gh_contact_number()
{
    $value = getenv('GULHOUSE_WHATSAPP');
    if ($value === FALSE && is_file(FCPATH . '.env')) {
        $settings = parse_ini_file(FCPATH . '.env');
        $value = $settings['GULHOUSE_WHATSAPP'] ?? '';
    }
    $number = preg_replace('/\D/', '', (string)$value);
    if (strpos($number, '0') === 0) $number = '62' . substr($number, 1);
    if (!preg_match('/^62\d{8,13}$/D', $number) || $number === '6280000000000') return '';
    return $number;
}
function gh_contact_url()
{
    $number = gh_contact_number();
    return $number ? 'https://wa.me/' . $number : base_url('#booking');
}
function gh_contact_label()
{
    return gh_contact_number() ? 'WhatsApp' : 'Hubungi pengelola';
}

function gh_instagram_username()
{
    $value = getenv('GULHOUSE_INSTAGRAM');
    if ($value === FALSE && is_file(FCPATH . '.env')) {
        $settings = parse_ini_file(FCPATH . '.env');
        $value = $settings['GULHOUSE_INSTAGRAM'] ?? '';
    }
    $username = ltrim(trim((string) $value), '@');
    return preg_match('/^[A-Za-z0-9_](?:[A-Za-z0-9_.]{0,28}[A-Za-z0-9_])?$/D', $username)
        && strpos($username, '..') === FALSE ? $username : '';
}

function gh_instagram_url()
{
    $username = gh_instagram_username();
    return $username ? 'https://www.instagram.com/' . $username . '/' : '';
}
