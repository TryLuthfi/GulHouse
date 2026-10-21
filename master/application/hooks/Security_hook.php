<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Central policy applies to routed URLs and direct controller URLs alike. */
class Security_hook
{
    public function enforce()
    {
        $ci =& get_instance();
        $controller = strtolower($ci->router->fetch_class());
        $method = strtolower($ci->router->fetch_method());
        if ($controller === 'mobile') return; // Mobile independently enforces bearer auth and action roles.
        if ($controller === 'jobs' && in_array($method, array('billing', 'automatic_billing'), TRUE) && $ci->input->is_cli_request()) {
            return; // Jobs independently enforces target schema and explicit real-schema opt-in.
        }
        if ($controller === 'auth' && $method === 'login') {
            return;
        }
        if (! $ci->session->userdata('gh_admin_logged_in')) {
            redirect('login', 'location', 303);
            return;
        }
        // Re-read permissions: disabling an account or changing its role takes effect immediately.
        $account = $ci->db->where('id', (int) $ci->session->userdata('gh_admin_id'))
            ->where('is_active', 1)->get('gh_admin_users')->row_array();
        if (! $account) {
            $ci->session->sess_destroy();
            redirect('login', 'location', 303);
            return;
        }
        $role = strtolower((string) $account['role']);
        $ci->session->set_userdata('gh_admin_role', $role);
        if (in_array($controller,array('lifecycle','access'),TRUE)) {
            $ci->output->set_header('Cache-Control: no-store, no-cache, must-revalidate')
                ->set_header('Pragma: no-cache')->set_header('Referrer-Policy: no-referrer');
        }
        $write = $controller === 'auth' && $method === 'logout';
        $write = $write || (bool) preg_match('/^(save|delete|upload|rotate|toggle|end|convert|generate|refund|cancel|renew|checkout|approve|reject)(_|$)/', $method);
        if ($controller === 'manage') {
            $write = ! in_array($method, array('properties', 'room_types', 'rooms', 'photos', 'view_photo', 'tenants', 'payments', 'master_form'), TRUE);
        }
        if ($controller === 'operations') {
            $write = ! in_array($method, array('index', 'export', 'print_contract', 'print_template', 'print_receipt', 'download_document','retention_preview'), TRUE);
        }
        if ($controller === 'users') { $write = $method !== 'index'; }
        if ($controller === 'access') { $write = TRUE; }
        if ($controller === 'lifecycle') { $write = ! in_array($method, array('index','detail','file','print_form'), TRUE); }
        if ($controller === 'paymentproofs') {
            $write = ! in_array($method, array('index', 'download'), TRUE);
        }
        if ($write && $ci->input->method(TRUE) !== 'POST') {
            header('Allow: POST');
            show_error('Aksi ini memerlukan POST dengan token keamanan. Muat ulang halaman lalu gunakan tombol aksi.', 405, 'Metode tidak diizinkan');
            return;
        }
        if ($controller === 'auth' && $method === 'logout') {
            return;
        }
        $module = $controller;
        if ($controller === 'lifecycle') {
            $module = 'tenants';
            if ($method === 'action') {
                $lifecycleAction = (string) $ci->input->post('action', TRUE);
                if (in_array($lifecycleAction, array('verify_payment','refund','resolve_dispute','penalty_payment'), TRUE)) $module = 'payments';
                elseif (in_array($lifecycleAction, array('inspection_save','inspection_finalize','inspection_extract','maintenance'), TRUE)) $module = 'inventory';
            }
            // Finance and maintenance need a case workspace to perform their assigned actions.
            if (in_array($role, array('finance','maintenance'), TRUE) && ! $write) $module = $role === 'finance' ? 'payments' : 'inventory';
        }
        if ($controller === 'paymentproofs') { $module = 'payments'; }
        if ($controller === 'operations') {
            $module = (string) $ci->uri->rsegment(3);
            $special = array('convert_booking' => 'bookings', 'generate_billing' => 'billing',
                'retention_preview'=>'bookings','archive_booking'=>'bookings','restore_booking'=>'bookings','purge_booking'=>'bookings','verify_booking_demo'=>'bookings',
                'update_booking_status' => 'bookings', 'escalate_complaint' => 'complaints',
                'draft_contract_from_stay' => 'contracts', 'print_receipt' => 'payments',
                'print_contract' => 'contracts', 'contract_action' => 'contracts', 'print_template' => 'templates', 'download_document' => 'documents');
            if (isset($special[$method])) { $module = $special[$method]; }
        } elseif ($controller === 'manage') {
            if ($method==='master_form') {
                $module=str_replace('-','_',(string)$ci->uri->rsegment(3));
                if($role==='viewer') { show_error('Akun hanya-baca tidak dapat membuka form perubahan.',403,'Akses ditolak');return; }
            }
            elseif (strpos($method, 'payment') !== FALSE) { $module = 'payments'; }
            elseif (strpos($method, 'tenant') !== FALSE) { $module = 'tenants'; }
            elseif (strpos($method, 'photo') !== FALSE) { $module = 'photos'; }
            elseif (strpos($method, 'room_type') !== FALSE) { $module = 'room_types'; }
            elseif (strpos($method, 'room') !== FALSE) { $module = 'rooms'; }
            elseif (strpos($method, 'propert') !== FALSE) { $module = 'properties'; }
        }
        $full = array('owner', 'super_admin', 'admin');
        $allowed = in_array($role, $full, TRUE);
        if ($role === 'manager') { $allowed = $module !== 'users'; }
        if ($role === 'viewer') { $allowed = ! $write && $module !== 'users'; }
        if ($role === 'finance') {
            $allowed = in_array($module, array('dashboard', 'payments', 'billing', 'receivables', 'deposits', 'finance', 'reports'), TRUE);
        }
        if ($role === 'maintenance') {
            $allowed = in_array($module, array('dashboard', 'maintenance', 'inventory', 'complaints', 'facilities', 'housekeeping'), TRUE);
        }
        if (! $allowed) {
            show_error('Peran akun tidak memiliki akses ke modul atau aksi ini.', 403, 'Akses ditolak');
        }
    }

    public function forms()
    {
        $ci =& get_instance();
        $html = $ci->output->get_output();
        $fragment=strtolower($ci->router->fetch_class())==='manage' && strtolower($ci->router->fetch_method())==='master_form';
        if ((stripos($html, '<html') === FALSE && !$fragment) || ! $ci->config->item('csrf_protection')) { return; }
        $name = $ci->security->get_csrf_token_name();
        $hash = $ci->security->get_csrf_hash();
        $hidden = '<input type="hidden" name="' . html_escape($name) . '" value="' . html_escape($hash) . '">';
        $html = preg_replace_callback('/<form\b[^>]*>.*?<\/form\s*>/is', function ($match) use ($name, $hidden) {
            if (! preg_match('/\bmethod\s*=\s*[\x22\x27]?post\b/i', $match[0]) || strpos($match[0], 'name="' . $name . '"') !== FALSE) { return $match[0]; }
            return preg_replace('/(<form\b[^>]*>)/i', '$1' . $hidden, $match[0], 1);
        }, $html);
        if($fragment) {
            $ci->output->set_header('X-Content-Type-Options: nosniff')->set_header('X-Frame-Options: SAMEORIGIN')->set_header('Cache-Control: private, no-store')->set_output($html);
            return;
        }
        $settings = json_encode(array('name' => $name, 'value' => $hash), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $script = '<script>window.GH_CSRF=' . $settings . ';window.GHSubmitPost=function(url){var u=new URL(url,location.href);if(u.origin!==location.origin)return;var f=document.createElement("form");f.method="post";f.action=u.href;var t=document.createElement("input");t.type="hidden";t.name=GH_CSRF.name;t.value=GH_CSRF.value;f.appendChild(t);document.body.appendChild(f);f.submit();};document.addEventListener("click",function(e){if(e.defaultPrevented)return;var a=e.target.closest("a[href]");if(!a)return;var u=new URL(a.href,location.href);if(u.origin===location.origin&&/\/(delete(?:-payment)?|toggle-public|end-stay)(\/|$)/.test(u.pathname)){e.preventDefault();GHSubmitPost(u.href);}},false);</script>';
        $html = str_ireplace('</body>', $script . '</body>', $html);
        if ($ci->session->userdata('gh_admin_role') === 'viewer') {
            // Keep filters, photo preview and downloads available; hide write controls.
            $readonly = '<style>[data-open-modal],[data-edit-form-url],[data-delete-url],[data-master-photo-save],form[method="post"]:not([data-logout-form]) button[type="submit"]{display:none!important}</style>';
            $html = str_ireplace('</head>', $readonly . '</head>', $html);
        }
        $ci->output->set_header('X-Content-Type-Options: nosniff')
            ->set_header('X-Frame-Options: SAMEORIGIN')->set_header('Referrer-Policy: same-origin')
            ->set_header('Cache-Control: no-store')->set_output($html);
    }
}
