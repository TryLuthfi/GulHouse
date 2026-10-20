<?php
/** Run only against a synthetic local fixture: php tests/verify-access.php */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$base = rtrim(getenv('GULHOUSE_TEST_URL') ?: 'http://127.0.0.1:8032', '/');
$database = getenv('GULHOUSE_DATABASE') ?: '';
$host = parse_url($base, PHP_URL_HOST);
if (strpos($database, 'gulhouse_test_') !== 0 || ! in_array($host, array('127.0.0.1', 'localhost', '::1'), TRUE)) {
    fwrite(STDERR, "Set GULHOUSE_DATABASE=gulhouse_test_* and a loopback GULHOUSE_TEST_URL.\n"); exit(2);
}
if (! extension_loaded('curl')) { fwrite(STDERR, "PHP curl extension required.\n"); exit(2); }
$password = getenv('GULHOUSE_E2E_PASS') ?: 'LocalDemo2026!';
$adminUser = getenv('GULHOUSE_E2E_USER') ?: 'codex_trial';
$cookies = array(); $checks = 0;
function verify($condition, $label) {
    global $checks;
    $checks++;
    if (! $condition) { throw new RuntimeException('FAIL: ' . $label); }
    echo 'PASS: ' . $label . PHP_EOL;
}
function request($path, $jar, $post = NULL) {
    global $base;
    $curl = curl_init($base . $path);
    curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_HEADER => TRUE,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_FOLLOWLOCATION => FALSE,
        CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 30));
    if ($post !== NULL) { curl_setopt($curl, CURLOPT_POST, TRUE); curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $raw = curl_exec($curl);
    if ($raw === FALSE) { $error = curl_error($curl); curl_close($curl); throw new RuntimeException('HTTP connection failed: ' . $error); }
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE); $length = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    curl_close($curl);
    return array('status' => $status, 'body' => substr($raw, $length));
}
function login($user, $password) {
    global $cookies;
    $jar = tempnam(sys_get_temp_dir(), 'gh_access_'); $cookies[] = $jar;
    $page = request('/master/index.php/login', $jar);
    verify($page['status'] === 200, 'Login page available');
    preg_match('/name="csrf_gh_master" value="([^"]+)"/', $page['body'], $match);
    verify(! empty($match[1]), 'Login form includes CSRF');
    $token = $match[1];
    $result = request('/master/index.php/login', $jar, array('username' => $user, 'password' => $password, 'csrf_gh_master' => $token));
    verify($result['status'] === 303 && request('/master/index.php/dashboard', $jar)['status'] === 200, 'Synthetic account authenticated');
    return array('jar' => $jar, 'token' => $token);
}
function form_post($path, $session, $data) {
    return request($path, $session['jar'], array_merge($data, array('csrf_gh_master' => $session['token'])));
}
$exitCode = 0;
try {
    $guest = tempnam(sys_get_temp_dir(), 'gh_access_'); $cookies[] = $guest;
    verify(request('/master/index.php/operations/index/users', $guest)['status'] === 303, 'Anonymous direct users route blocked');
    verify(request('/master/index.php/operations/print_receipt/99999999', $guest)['status'] === 303, 'Anonymous receipt blocked');
    verify(request('/master/index.php/login', $guest, array('username' => 'invalid', 'password' => 'invalid'))['status'] === 403, 'Missing CSRF rejected before authentication');
    verify(request('/landing/index.php/booking', $guest)['status'] === 405, 'Public booking rejects GET');
    verify(request('/landing/index.php/payment/check', $guest, array('phone' => '000', 'nik_last4' => '0000'))['status'] === 403, 'Resident lookup requires CSRF');
    foreach (array('/master/application/config/config.php', '/landing/application/config/config.php',
        '/master/application/private_documents/.htaccess', '/master/.env', '/master/system/core/Controller.php', '/uploads/DOCUMENTS/probe.pdf',
        '/master%5capplication%5cprivate_documents%5c.htaccess', '/master%5capplication%5cconfig%5cconfig.php',
        '/landing%5csystem%5ccore%5cController.php', '/uploads%5cDOCUMENTS%5cprobe.pdf',
        '/master//application/hooks/Security_hook.php', '/master/%5capplication/hooks/Security_hook.php',
        '/master%5c%5capplication%5chooks%5cSecurity_hook.php', '/master/application./hooks/Security_hook.php',
        '/master./application/hooks/Security_hook.php', '/master/application%2e/hooks/Security_hook.php') as $path) {
        verify(request($path, $guest)['status'] === 403, 'Private static path denied: ' . $path);
    }
    $sessions = array('super_admin' => login($adminUser, $password));
    $suffix = bin2hex(random_bytes(4));
    foreach (array('owner', 'admin') as $role) {
        $user = 'access_' . $role . '_' . $suffix;
        $created = form_post('/master/index.php/users/save', $sessions['super_admin'], array('fullname' => 'Access Regression Demo',
            'username' => $user, 'email' => '', 'password' => $password, 'role' => $role, 'is_active' => '1'));
        verify($created['status'] === 303, 'Synthetic ' . $role . ' create redirects');
        $sessions[$role] = login($user, $password);
    }
    foreach (array('manager', 'finance', 'maintenance', 'viewer') as $role) { $sessions[$role] = login('demo_' . $role, $password); }
    $matrix = array(
        'owner' => array(200,200,200,200,200,200,200),
        'super_admin' => array(200,200,200,200,200,200,200),
        'admin' => array(200,200,200,200,200,200,200),
        'manager' => array(200,200,200,200,200,403,403),
        'finance' => array(403,403,403,200,403,403,403),
        'maintenance' => array(403,403,403,403,200,403,403),
        'viewer' => array(200,200,200,200,200,403,403));
    $routes = array('rooms', 'contracts', 'documents', 'payments', 'maintenance', 'users', 'operations/index/users');
    foreach ($matrix as $role => $expected) {
        foreach ($routes as $index => $route) {
            verify(request('/master/index.php/' . $route, $sessions[$role]['jar'])['status'] === $expected[$index], $role . ' access ' . $route);
        }
        verify(request('/master/index.php/properties/delete/99999999', $sessions[$role]['jar'])['status'] === 405, $role . ' GET mutation blocked');
    }
    foreach (array('viewer', 'finance', 'maintenance') as $role) {
        verify(form_post('/master/index.php/manage/save_property', $sessions[$role], array())['status'] === 403, $role . ' direct property write denied');
        verify(form_post('/master/index.php/users/save', $sessions[$role], array())['status'] === 403, $role . ' direct users write denied');
    }
    verify(form_post('/master/index.php/operations/contract_action/99999999/activate', $sessions['viewer'], array())['status'] === 403, 'Viewer contract action denied');
    verify(form_post('/master/index.php/operations/update_booking_status/99999999', $sessions['finance'], array())['status'] === 403, 'Finance booking update denied');
    verify(form_post('/master/index.php/operations/update_booking_status/99999999', $sessions['maintenance'], array())['status'] === 403, 'Maintenance booking update denied');
    verify(form_post('/master/index.php/operations/escalate_complaint/99999999', $sessions['viewer'], array())['status'] === 403, 'Viewer complaint escalation denied');
    foreach (array('super_admin', 'finance', 'viewer') as $role) {
        verify(request('/master/index.php/operations/print_receipt/99999999', $sessions[$role]['jar'])['status'] === 404, $role . ' receipt read allowed; missing receipt returns 404');
    }
    verify(request('/master/index.php/operations/print_receipt/99999999', $sessions['maintenance']['jar'])['status'] === 403, 'Maintenance receipt denied');
    verify(form_post('/master/index.php/users/save', $sessions['admin'], array('fullname' => 'Blocked privilege regression',
        'username' => 'blocked_' . $suffix, 'password' => $password, 'role' => 'owner', 'is_active' => '1'))['status'] === 303, 'Admin elevation attempt returns validation redirect');
    $page = request('/master/index.php/users', $sessions['admin']['jar']);
    verify(strpos($page['body'], 'Hanya owner/super admin') !== FALSE, 'Admin privilege escalation rejected');
    $page = request('/master/index.php/users', $sessions['super_admin']['jar']);
    preg_match('/name="id" value="(\d+)"(?:(?!<\/form>).)*?name="username" value="' . preg_quote($adminUser, '/') . '"/s', $page['body'], $match);
    verify(! empty($match[1]), 'Current synthetic admin found');
    form_post('/master/index.php/users/delete/' . $match[1], $sessions['super_admin'], array());
    verify(strpos(request('/master/index.php/users', $sessions['super_admin']['jar'])['body'], 'Akun sendiri tidak boleh') !== FALSE, 'Self-disable prevented');
    echo 'Security regression passed: ' . $checks . " assertions. Synthetic users retained in fixture only.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL . 'Completed assertions: ' . $checks . PHP_EOL); $exitCode = 1;
} finally {
    foreach ($cookies as $jar) { if (is_file($jar)) { unlink($jar); } }
}
exit($exitCode);
