<?php
// PHP's development server ignores .htaccess; enforce private paths explicitly.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
// Windows resolves backslashes as directory separators, including encoded ones.
$path = str_replace('\\', '/', $path);
$path = preg_replace('#/+#', '/', $path);
if (preg_match('#(^|/)[^/]*[. ](/|$)#', $path) ||
    preg_match('#(^|/)(\.|\.\.|\.env|\.git|\.e2e|\.deploy|\.tools)(/|$)#i', $path) ||
    preg_match('#/(master|landing)/(application|system)(/|$)#i', $path) ||
    preg_match('#/uploads/DOCUMENTS(/|$)#i', $path) ||
    preg_match('#(^|/)(database|tests|scripts|docs|node_modules)(/|$)#i', $path) ||
    preg_match('#^/mobile(/|$)#i', $path) ||
    preg_match('#(^|/)\.[^/]+#', $path)) {
    http_response_code(403);
    echo 'Private application resource.';
    return true;
}
return false;
