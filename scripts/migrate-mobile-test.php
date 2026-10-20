<?php
// Explicitly restricted to synthetic schemas. Never migrates the live GulHouse database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$root = dirname(__DIR__);
$schema = $argv[1] ?? '';
if (!preg_match('/^gulhouse_test_[a-zA-Z0-9_]+$/D', $schema)) throw new RuntimeException('Synthetic schema required.');
$env = parse_ini_file($root . '/landing/.env');
$db = new mysqli($env['HOSTNAME'] ?? '127.0.0.1', $env['USERNAME'] ?? 'root', $env['PASSWORD'] ?? '', $schema, (int)($env['DB_PORT'] ?? 3306));
$db->multi_query(file_get_contents($root . '/database/patch_gulhouse_mobile_api_20261019.sql'));
do { if ($result = $db->store_result()) $result->free(); } while ($db->more_results() && $db->next_result());
echo "Mobile token migration complete on synthetic schema only.\n";
