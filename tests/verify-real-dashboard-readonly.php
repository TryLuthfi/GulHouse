<?php
// Execute actual dashboard model/query builder against local data in a READ ONLY transaction.
if (PHP_SAPI !== 'cli' || !in_array('--real-readonly', $argv, true)) exit(2);
$root = dirname(__DIR__);
define('BASEPATH', $root . '/master/system/');
define('APPPATH', $root . '/master/application/');
function log_message($level, $message) {}
function is_php($version) { return version_compare(PHP_VERSION, $version, '>='); }
function show_error($message) { throw new RuntimeException((string)$message); }
class CI_Model { public $db; }
require BASEPATH . 'database/DB.php';
require APPPATH . 'models/Dashboard_model.php';
$cfg = parse_ini_file($root . '/master/.env');
$schema = $cfg['GULHOUSE_DATABASE'] ?? ($cfg['DATABASE'] ?? 'gulhouse_db');
if ($schema !== 'gulhouse_db' || !in_array($cfg['HOSTNAME'] ?? '127.0.0.1', array('127.0.0.1','localhost'), true)) exit(2);
$db = DB(array('hostname'=>$cfg['HOSTNAME'] ?? '127.0.0.1','username'=>$cfg['USERNAME'] ?? 'root','password'=>$cfg['PASSWORD'] ?? '', 'database'=>$schema,'dbdriver'=>'mysqli','port'=>(int)($cfg['DB_PORT'] ?? 3306),'db_debug'=>false,'pconnect'=>false,'char_set'=>'utf8mb4','dbcollat'=>'utf8mb4_general_ci'), true);
$db->query('SET TRANSACTION READ ONLY');
$db->query('START TRANSACTION');
try {
    $model = new Dashboard_model(); $model->db = $db;
    $periods = $db->select('period_key')->order_by('period_key')->get('gh_billing_periods')->result_array();
    $checks = 0;
    foreach ($periods as $period) {
        $model->set_period($period['period_key']);
        foreach (array('get_summary','get_charts','get_property_rows','get_room_type_rows','get_recent_bookings','get_latest_payment_rows','get_payment_issue_rows') as $method) {
            $result = $model->$method();
            if (!is_array($result) || $db->error()['code']) throw new RuntimeException('Dashboard read failed: '.$method);
            $checks++;
        }
    }
    echo json_encode(array('passed'=>$checks,'periods'=>count($periods),'real_dashboard_model'=>true,'transaction_read_only'=>true,'no_real_rows_modified'=>true)).PHP_EOL;
} finally { $db->query('ROLLBACK'); }
