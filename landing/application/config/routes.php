<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'home';
$route['booking'] = 'home/booking';
$route['rooms/(:any)'] = 'home/room/$1';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
