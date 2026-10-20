<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Security_hook
{
    public function enforce()
    {
        $ci =& get_instance();
        $method = strtolower($ci->router->fetch_method());
        if (in_array($method, array('booking', 'payment_check', 'payment_logout', 'complaint_submit'), TRUE) && $ci->input->method(TRUE) !== 'POST') {
            header('Allow: POST');
            show_error('Gunakan formulir pada halaman untuk mengirim permintaan.', 405, 'Metode tidak diizinkan');
        }
        $access = $ci->session->userdata('gh_payment_access');
        if (is_array($access) && (! isset($access['verified_at']) || strtotime($access['verified_at']) < time() - 1800)) {
            $ci->session->unset_userdata('gh_payment_access');
        }
        if ($method === 'payment_check') {
            $path = rtrim($ci->config->item('sess_save_path'), '/\\') . DIRECTORY_SEPARATOR . 'gh_lookup_' . hash('sha256', $ci->input->ip_address()) . '.json';
            $file = @fopen($path, 'c+');
            if (! $file || ! flock($file, LOCK_EX)) {
                if ($file) { fclose($file); }
                show_error('Pemeriksaan sementara tidak tersedia. Coba lagi nanti.', 503); return;
            }
            $record = json_decode(stream_get_contents($file), TRUE);
            if (! is_array($record) || empty($record['until']) || $record['until'] <= time()) { $record = array('until' => time() + 300, 'count' => 0); }
            $record['count']++;
            rewind($file); ftruncate($file, 0); fwrite($file, json_encode($record)); fflush($file); flock($file, LOCK_UN); fclose($file);
            if ($record['count'] > 10) { header('Retry-After: 300'); show_error('Terlalu banyak pemeriksaan. Tunggu 5 menit.', 429); }
        }
    }

    public function forms()
    {
        $ci =& get_instance();
        $html = $ci->output->get_output();
        if (stripos($html, '<html') === FALSE || ! $ci->config->item('csrf_protection')) { return; }
        $name = $ci->security->get_csrf_token_name();
        $hidden = '<input type="hidden" name="' . html_escape($name) . '" value="' . html_escape($ci->security->get_csrf_hash()) . '">';
        $html = preg_replace_callback('/<form\b[^>]*>.*?<\/form\s*>/is', function ($match) use ($name, $hidden) {
            if (! preg_match('/\bmethod\s*=\s*[\x22\x27]?post\b/i', $match[0]) || strpos($match[0], 'name="' . $name . '"') !== FALSE) { return $match[0]; }
            return preg_replace('/(<form\b[^>]*>)/i', '$1' . $hidden, $match[0], 1);
        }, $html);
        $html = preg_replace_callback('/<a\b([^>]*?)href="([^"]*payment\/logout)"([^>]*)>(.*?)<\/a>/is', function ($match) use ($hidden) {
            return '<form method="post" action="' . $match[2] . '" style="display:inline">' . $hidden . '<button type="submit">' . $match[4] . '</button></form>';
        }, $html);
        $ci->output->set_header('X-Content-Type-Options: nosniff')->set_header('X-Frame-Options: SAMEORIGIN')
            ->set_header('Referrer-Policy: same-origin')->set_output($html);
        if (strpos($ci->router->fetch_method(), 'payment') === 0) {
            $ci->output->set_header('Cache-Control: no-store');
        }
    }
}
