<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$schema = getenv('GULHOUSE_DATABASE');
$allowReal = in_array('--allow-real-database', array_slice($argv, 1), TRUE);
if (! is_string($schema) || (! preg_match('/^gulhouse_test_[A-Za-z0-9_]+$/D', $schema) && ! ($schema === 'gulhouse_db' && $allowReal))) {
    fwrite(STDERR, "Refusing billing: set an explicit synthetic GULHOUSE_DATABASE=gulhouse_test_*.\n"); exit(2);
}
$period = ''; $mode = 'preview'; $automatic = FALSE; $leadDays = 7;
foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--apply') { $mode = 'apply'; }
    elseif ($argument === '--automatic') { $automatic = TRUE; }
    elseif (strpos($argument, '--lead-days=') === 0 && preg_match('/^\d{1,2}$/D', substr($argument, 12))) { $leadDays = (int) substr($argument, 12); }
    elseif ($argument === '--allow-real-database') { /* explicit opt-in, validated above */ }
    elseif (strpos($argument, '--period=') === 0) { $period = substr($argument, 9); }
    else { fwrite(STDERR, "Unknown billing argument. Use --automatic or --period=YYYY-MM and optional --apply.\n"); exit(2); }
}
if ($leadDays < 2 || $leadDays > 31 || ($automatic && $period !== '')) {
    fwrite(STDERR, "Automatic billing uses today's date, no explicit period, and lead days 2..31.\n"); exit(2);
}
date_default_timezone_set('Asia/Jakarta');
putenv('GULHOUSE_BILLING_ALLOW_REAL=' . ($allowReal ? '1' : '0'));
if ($period === '') { $period = date('Y-m'); }
if (! preg_match('/^(\d{4})-(\d{2})$/D', $period, $parts) || ! checkdate((int) $parts[2], 1, (int) $parts[1])) {
    fwrite(STDERR, "Invalid billing month.\n"); exit(2);
}
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['CI_ENV'] = 'production'; // Keep task logs free of framework debug/connection details.
$_SERVER['SCRIPT_NAME'] = '/master/index.php';
$_SERVER['argv'] = $automatic ? array(__FILE__, 'jobs', 'automatic_billing', $mode, (string)$leadDays) : array(__FILE__, 'jobs', 'billing', $period, $mode);
$_SERVER['argc'] = count($_SERVER['argv']);
$argv = $_SERVER['argv']; $argc = $_SERVER['argc'];
require dirname(__DIR__) . '/master/index.php';
