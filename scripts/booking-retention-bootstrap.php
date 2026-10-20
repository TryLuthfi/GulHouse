<?php
// Shared CLI adapter: uses existing credentials opaquely, never prints configuration.
if(PHP_SAPI!=='cli')exit(2);
$root=dirname(__DIR__);
define('BASEPATH',$root.'/master/system/');define('APPPATH',$root.'/master/application/');define('FCPATH',$root.'/master/');
function log_message($level,$message){}
function is_php($version){return version_compare(PHP_VERSION,$version,'>=');}
function show_error($message){throw new RuntimeException((string)$message);}
class CI_Model {public $db;public $config;}
require BASEPATH.'database/DB.php';require APPPATH.'models/Booking_retention_model.php';
function booking_retention_cli($schema)
{
    global $root;
    $env=parse_ini_file($root.'/master/.env');
    $real=$env['GULHOUSE_DATABASE']??($env['DATABASE']??'gulhouse_db');
    if(!preg_match('/^gulhouse_test_[A-Za-z0-9_]+$/D',$schema) && $schema!==$real)throw new RuntimeException('Schema bukan runtime yang dikenal.');
    $db=DB(array('hostname'=>$env['HOSTNAME']??'127.0.0.1','username'=>$env['USERNAME']??'root','password'=>$env['PASSWORD']??'','database'=>$schema,'dbdriver'=>'mysqli','port'=>(int)($env['DB_PORT']??3306),'db_debug'=>FALSE,'pconnect'=>FALSE,'char_set'=>'utf8mb4','dbcollat'=>'utf8mb4_general_ci'),TRUE);
    $key=$env['GULHOUSE_ENCRYPTION_KEY']??getenv('GULHOUSE_ENCRYPTION_KEY');
    // Match the web application's existing configured fallback exactly.
    if(!$key)$key='gulhouse_landing_change_this_key_on_production';
    $config=new class($key){private $key;function __construct($k){$this->key=$k;}function item($k){return $k==='encryption_key'?$this->key:NULL;}};
    $model=new Booking_retention_model();$model->db=$db;$model->config=$config;
    return array($model,$db);
}
