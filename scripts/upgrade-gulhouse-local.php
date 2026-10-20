<?php
// Local, additive migrations. A private full backup precedes any schema change.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$root=dirname(__DIR__); $env=array();
foreach(array($root.'/master/.env',$root.'/.env',$root.'/landing/.env') as $path) if(is_file($path)) { $env=parse_ini_file($path); break; }
$schema=$env['GULHOUSE_DATABASE'] ?? ($env['DATABASE'] ?? '');
$host=$env['HOSTNAME'] ?? '127.0.0.1';
if($schema!=='gulhouse_db' || !in_array($host,array('127.0.0.1','localhost'),true)) throw new RuntimeException('Only local gulhouse_db is supported.');
$db=new mysqli($host,$env['USERNAME']??'root',$env['PASSWORD']??'',$schema,(int)($env['DB_PORT']??3306)); $db->set_charset('utf8mb4');
function counts($db) {
    $result=array();
    foreach($db->query('SHOW TABLES')->fetch_all(MYSQLI_NUM) as $item) if(preg_match('/^gh_[a-zA-Z0-9_]+$/D',$item[0])) $result[$item[0]]=(int)$db->query('SELECT COUNT(*) n FROM `'.$item[0].'`')->fetch_assoc()['n'];
    return $result;
}
$before=counts($db);
if(!in_array('--apply',$argv,true)) { echo json_encode(array('schema'=>$schema,'counts'=>$before,'schema_only'=>true)).PHP_EOL; exit; }
$backupDir=$root.'/.deploy/backups'; if(!is_dir($backupDir)) mkdir($backupDir,0700,true);
$backup=$backupDir.'/gulhouse-before-flow-'.date('Ymd-His').'.sql';
$handle=fopen($backup,'xb'); if(!$handle) throw new RuntimeException('Backup creation failed.');
fwrite($handle,"SET FOREIGN_KEY_CHECKS=0;\n");
foreach(array_keys($before) as $table) {
    $create=$db->query('SHOW CREATE TABLE `'.$table.'`')->fetch_assoc(); fwrite($handle,$create['Create Table'].";\n");
    $rows=$db->query('SELECT * FROM `'.$table.'`');
    while($row=$rows->fetch_assoc()) {
        $columns=array_map(function($key){return '`'.$key.'`';},array_keys($row));
        $values=array_map(function($value)use($db){return $value===null?'NULL':"'".$db->real_escape_string($value)."'";},array_values($row));
        fwrite($handle,'INSERT INTO `'.$table.'` ('.implode(',',$columns).') VALUES ('.implode(',',$values).");\n");
    }
}
fwrite($handle,"SET FOREIGN_KEY_CHECKS=1;\n"); fclose($handle);
foreach(array('patch_gulhouse_flow_20261015.sql','patch_gulhouse_roles_20261015.sql','patch_gulhouse_payment_proofs_20261015.sql') as $patch) {
    $db->multi_query(file_get_contents($root.'/database/'.$patch));
    do { if($result=$db->store_result()) $result->free(); } while($db->more_results() && $db->next_result());
}
$after=counts($db);
foreach($before as $table=>$count) if(!isset($after[$table]) || $after[$table]!==$count) throw new RuntimeException('Row-count reconciliation failed; inspect local backup.');
foreach(array_diff_key($after,$before) as $table=>$count) if($count!==0) throw new RuntimeException('New migration table must start empty.');
echo json_encode(array('schema'=>$schema,'backup_created'=>true,'all_row_counts_preserved'=>true,'tables'=>count($after),'migrations_applied'=>3)).PHP_EOL;
