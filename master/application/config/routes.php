<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'dashboard';
$route['login'] = 'auth/login';
$route['logout'] = 'auth/logout';
$route['dashboard'] = 'dashboard';
$route['properties'] = 'manage/properties';
$route['properties/save'] = 'manage/save_property';
$route['properties/delete/(:num)'] = 'manage/delete_property/$1';
$route['room-types'] = 'manage/room_types';
$route['room-types/save'] = 'manage/save_room_type';
$route['room-types/delete/(:num)'] = 'manage/delete_room_type/$1';
$route['rooms'] = 'manage/rooms';
$route['rooms/save'] = 'manage/save_room';
$route['rooms/delete/(:num)'] = 'manage/delete_room/$1';
$route['rooms/toggle-public/(:num)'] = 'manage/toggle_room_public/$1';
$route['tenants'] = 'manage/tenants';
$route['tenants/save'] = 'manage/save_tenant';
$route['tenants/end-stay/(:num)'] = 'manage/end_tenant_stay/$1';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
