<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/user_guide/general/hooks.html
|
*/

$hook['post_controller_constructor'][] = array(
	'class'    => 'Maintenance_hook',
	'function' => 'enforce',
	'filename' => 'Maintenance_hook.php',
	'filepath' => 'hooks',
	'params'   => array()
);

$hook['post_controller_constructor'][] = array(
	'class'    => 'First_login_guard_hook',
	'function' => 'enforce',
	'filename' => 'First_login_guard_hook.php',
	'filepath' => 'hooks',
	'params'   => array()
);

$hook['post_controller_constructor'][] = array(
	'class'    => 'ModuleAccess_hook',
	'function' => 'enforce',
	'filename' => 'ModuleAccess_hook.php',
	'filepath' => 'hooks',
	'params'   => array()
);

$hook['post_controller_constructor'][] = array(
	'class'    => 'Login_activity_hook',
	'function' => 'track',
	'filename' => 'Login_activity_hook.php',
	'filepath' => 'hooks',
	'params'   => array()
);
