<?php
$runtime=json_decode(file_get_contents(dirname(__DIR__).'/.e2e/lifecycle/runtime.json'),TRUE);
if(!preg_match('/^gulhouse_test_[a-zA-Z0-9_]+$/D',$runtime['schema']??''))throw new RuntimeException('Synthetic schema required.');
putenv('GULHOUSE_DATABASE='.$runtime['schema']);putenv('GULHOUSE_TEST_URL='.$runtime['url']);require __DIR__.'/http-helpers.php';
$own=json_decode(file_get_contents($root.'/.e2e/lifecycle/ciko/state-private.json'),TRUE);
$c=row("SELECT * FROM gh_resident_cases WHERE phone='".$db->real_escape_string($own['phone'])."'");
$other=row('SELECT f.*,c.profile_json FROM gh_resident_files f JOIN gh_resident_cases c ON c.id=f.case_id WHERE f.kind="ktp" AND f.case_id!='.(int)$c['id'].' AND c.profile_completed_at IS NOT NULL ORDER BY f.id DESC LIMIT 1');
if(!$other)throw new RuntimeException('Other completed synthetic case required.');
$cookieFiles=array($cookie);$created=array();
try{
 post('/landing/index.php/resident/access',array('access_code'=>$own['code']),'/landing/index.php/resident/access');
 list($code,$html)=request('/landing/index.php/resident/file/'.$other['id']);check($code===404,'resident cannot download another case KTP');
 list($code,$html)=request('/master/application/private_lifecycle/'.substr(hash('sha256',$runtime['schema']),0,16).'/'.$other['storage_name']);check($code===403,'private lifecycle static path denied without controller');
 foreach(array('maintenance','finance','viewer') as $role){
  $name='ciko_privacy_'.$role.'_'.time();$password=bin2hex(random_bytes(20));$hash=password_hash($password,PASSWORD_DEFAULT);
  $s=$db->prepare('INSERT INTO gh_admin_users(username,password_hash,fullname,role,is_active) VALUES(?,?,?,?,1)');$full='CIKO synthetic '.$role;$s->bind_param('ssss',$name,$hash,$full,$role);$s->execute();$created[]=(int)$db->insert_id;
  $cookie=tempnam(sys_get_temp_dir(),'gh_ciko_privacy_');$cookieFiles[]=$cookie;
  post('/master/index.php/login',array('username'=>$name,'password'=>$password),'/master/index.php/login');
  list($code,$html)=request('/master/index.php/lifecycle/detail/'.$other['case_id']);check($code===200,$role.' case workspace accessible');
  if($role!=='viewer'){$profile=json_decode($other['profile_json'],TRUE);check(!empty($profile['identity_number'])&&strpos($html,$profile['identity_number'])===FALSE,$role.' full NIK redacted from detail HTML');check(strpos($html,'lifecycle/file/'.$other['id'].'"')===FALSE,$role.' forbidden KTP link omitted');}
  list($code,$html)=request('/master/index.php/lifecycle/file/'.$other['id']);check($code===($role==='viewer'?200:403),$role.' identity document policy enforced');
  $action=$role==='finance'?'inspection_finalize':'refund';list($code,$html)=post('/master/index.php/lifecycle/action/'.$other['case_id'],array('action'=>$action),'/master/index.php/lifecycle/detail/'.$other['case_id']);check($code===403,$role.' cannot perform out-of-scope write');
 }
 echo json_encode(array('passed'=>$checks,'scope'=>'cross-tenant and role/file/POST policies')).PHP_EOL;
}finally{foreach($created as $id)$db->query('DELETE FROM gh_admin_users WHERE id='.(int)$id);foreach($cookieFiles as $f)if(is_file($f))unlink($f);}
