<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/** Internal-only transport adapter; never enabled from a request parameter. */
class Mobile_command_result extends RuntimeException {}
class Mobile_command_bridge { public static $active = FALSE; }
if (!function_exists('gh_mobile_redirect')) {
    function gh_mobile_redirect($uri = '', $method = 'auto', $code = NULL) {
        if (Mobile_command_bridge::$active) throw new Mobile_command_result('command_finished');
        return redirect($uri,$method,$code);
    }
}
