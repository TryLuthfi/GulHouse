<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$active_group = 'default';
$query_builder = TRUE;

if ( ! function_exists('gulhouse_master_load_env')) {
    function gulhouse_master_load_env()
    {
        $paths = array(
            FCPATH . '.env',
            dirname(FCPATH) . DIRECTORY_SEPARATOR . '.env',
            dirname(FCPATH) . DIRECTORY_SEPARATOR . 'landing' . DIRECTORY_SEPARATOR . '.env',
            dirname(dirname(FCPATH)) . DIRECTORY_SEPARATOR . 'DatabaseTKM' . DIRECTORY_SEPARATOR . '.env',
        );

        foreach ($paths as $path) {
            if (is_file($path)) {
                $env = parse_ini_file($path);
                return is_array($env) ? $env : array();
            }
        }

        return array();
    }
}

$env = gulhouse_master_load_env();
$host = isset($env['HOSTNAME']) ? trim((string) $env['HOSTNAME']) : '127.0.0.1';
$port = isset($env['DB_PORT']) && ctype_digit((string) $env['DB_PORT']) ? (int) $env['DB_PORT'] : 3306;

if (strpos($host, ':') === false && $port > 0) {
    $host .= ':' . $port;
}

$db['default'] = array(
    'dsn' => '',
    'hostname' => $host,
    'username' => isset($env['USERNAME']) ? $env['USERNAME'] : '',
    'password' => isset($env['PASSWORD']) ? $env['PASSWORD'] : '',
    'database' => isset($env['GULHOUSE_DATABASE']) ? $env['GULHOUSE_DATABASE'] : (isset($env['DATABASE']) ? $env['DATABASE'] : ''),
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8mb4',
    'dbcollat' => 'utf8mb4_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => (ENVIRONMENT !== 'production')
);
