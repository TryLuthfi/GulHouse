<?php
// Exercise the real session aging method without initializing HTTP sessions.
define('BASEPATH',dirname(__DIR__).'/master/system/');
require BASEPATH.'libraries/Session/Session.php';
class SessionAgingProbe extends CI_Session {
    public function __construct() {}
    public function age(){ $this->_ci_init_vars(); }
}
$m=new SessionAgingProbe();$passed=0;
function agecheck($ok,$label){global $passed;if(!$ok)throw new RuntimeException('FAIL '.$label);$passed++;echo 'PASS '.$label.PHP_EOL;}
$_SESSION=array('gh_admin_logged_in'=>TRUE,'gh_admin_id'=>91,'regular'=>'unchanged','error'=>'old login error','__ci_vars'=>array('error'=>'new'));
$m->age();agecheck($_SESSION['__ci_vars']['error']==='old'&&$_SESSION['error']==='old login error','new flash remains available for next request');
$m->age();agecheck(!isset($_SESSION['error'])&&!isset($_SESSION['__ci_vars']),'old flash is removed on PHP8');
agecheck($_SESSION['gh_admin_logged_in']===TRUE&&$_SESSION['gh_admin_id']===91&&$_SESSION['regular']==='unchanged','unrelated authentication and regular data remain untouched');
$_SESSION['past']='expired';$_SESSION['future']='available';$_SESSION['__ci_vars']=array('past'=>time()-30,'future'=>time()+300);
$m->age();agecheck(!isset($_SESSION['past'])&&!isset($_SESSION['__ci_vars']['past']),'past integer tempdata expires');
agecheck($_SESSION['future']==='available'&&is_int($_SESSION['__ci_vars']['future']),'future integer tempdata remains');
$root=dirname(__DIR__);$master=file_get_contents($root.'/master/system/libraries/Session/Session.php');$landing=file_get_contents($root.'/landing/system/libraries/Session/Session.php');
$method=function($s){preg_match('/protected function _ci_init_vars\(\).*?(?=\n\t\/\/ ------------------------------------------------------------------------)/s',$s,$match);return $match[0]??'';};
agecheck($method($master)!==''&&$method($master)===$method($landing),'both applications use identical corrected aging logic');
echo json_encode(array('passed'=>$passed,'database_connections'=>0,'real_sessions_read'=>0)).PHP_EOL;
