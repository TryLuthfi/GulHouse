<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'home';
$route['booking'] = 'home/booking';
$route['media/logo'] = 'home/logo';
$route['media/gallery/(:any)/(:any)/(:any)'] = 'home/gallery/$1/$2/$3';
$route['slider/(:any)'] = 'home/slider/$1';
$route['rooms/(:any)'] = 'home/room/$1';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
