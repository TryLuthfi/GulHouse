<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'libraries/Mobile_command_bridge.php';
class Users extends CI_Controller
{
    private $roles = array('owner', 'super_admin', 'admin', 'manager', 'finance', 'maintenance', 'viewer');
    public function index()
    {
        $this->load->view('users/index', array('title' => 'Admin & Role | GUL HOUSE',
            'admin_name' => $this->session->userdata('gh_admin_name'), 'roles' => $this->roles,
            'rows' => $this->db->select('id,fullname,username,email,role,is_active,last_login_at')->order_by('id')->get('gh_admin_users')->result_array()));
    }
    public function save()
    {
        $id = (int) $this->input->post('id');
        $payload = array('fullname' => trim((string) $this->input->post('fullname', TRUE)),
            'username' => trim((string) $this->input->post('username', TRUE)),
            'email' => trim((string) $this->input->post('email', TRUE)) ?: NULL,
            'role' => (string) $this->input->post('role', TRUE), 'is_active' => $this->input->post('is_active') === '1' ? 1 : 0);
        $password = (string) $this->input->post('password', FALSE);
        if ($payload['fullname'] === '' || mb_strlen($payload['fullname']) > 120 || ! preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $payload['username'])
            || ! in_array($payload['role'], $this->roles, TRUE) || ($payload['email'] && (! filter_var($payload['email'], FILTER_VALIDATE_EMAIL) || strlen($payload['email']) > 150))
            || ((! $id || $password !== '') && (strlen($password) < 10 || strlen($password) > 72))) {
            $this->finish(FALSE, 'Lengkapi data valid. Username 3–60 karakter; password baru 10–72 karakter.'); return;
        }
        if ($password !== '') { $payload['password_hash'] = password_hash($password, PASSWORD_DEFAULT); }
        $this->persist($id, $payload);
    }
    public function delete($id)
    {
        // Disabling retains references in activity logs and financial records.
        $this->persist((int) $id, array('is_active' => 0));
    }
    private function persist($id, $payload)
    {
        $this->db->trans_begin();
        $rows = $this->db->query('SELECT id, role, is_active FROM gh_admin_users ORDER BY id FOR UPDATE')->result_array();
        $target = NULL; $leaders = 0;
        foreach ($rows as $row) {
            if ((int) $row['id'] === $id) { $target = $row; }
            if ($row['is_active'] && in_array($row['role'], array('owner', 'super_admin'), TRUE)) { $leaders++; }
        }
        $newRole = isset($payload['role']) ? $payload['role'] : ($target ? $target['role'] : '');
        $leader = in_array($newRole, array('owner', 'super_admin'), TRUE);
        $actorRole = $this->session->userdata('gh_admin_role');
        $error = '';
        if ($id && ! $target) { $error = 'Akun tidak ditemukan.'; }
        elseif ($actorRole === 'admin' && ($leader || ($target && in_array($target['role'], array('owner', 'super_admin'), TRUE)))) { $error = 'Hanya owner/super admin dapat mengelola akun pemimpin.'; }
        elseif ($id === (int) $this->session->userdata('gh_admin_id') && (! $payload['is_active'] || $newRole !== $target['role'])) { $error = 'Akun sendiri tidak boleh dinonaktifkan atau diubah perannya.'; }
        elseif ($target && $target['is_active'] && in_array($target['role'], array('owner', 'super_admin'), TRUE) && $leaders <= 1 && (! $payload['is_active'] || ! $leader)) { $error = 'Minimal satu owner/super admin aktif harus tersedia.'; }
        if (! $error && isset($payload['username'])) {
            $duplicate = $this->db->where('id !=', $id)->group_start()->where('username', $payload['username']);
            if ($payload['email']) { $duplicate->or_where('email', $payload['email']); }
            if ($duplicate->group_end()->count_all_results('gh_admin_users')) { $error = 'Username atau email sudah dipakai.'; }
        }
        if ($error) { $this->db->trans_rollback(); $this->finish(FALSE, $error); return; }
        $ok = $id ? $this->db->where('id', $id)->update('gh_admin_users', $payload) : $this->db->insert('gh_admin_users', $payload);
        if (! $id) { $id = $this->db->insert_id(); }
        if ($ok && $this->db->table_exists('gh_activity_logs')) {
            $this->db->insert('gh_activity_logs', array('admin_user_id' => (int) $this->session->userdata('gh_admin_id'),
                'action' => 'manage_user', 'description' => 'Mengelola akun #' . $id . ', role ' . $newRole . ', active ' . $payload['is_active'],
                'ip_address' => $this->input->ip_address(), 'user_agent' => substr((string) $this->input->user_agent(), 0, 255)));
        }
        if (! $ok || ! $this->db->trans_status()) { $this->db->trans_rollback(); $this->finish(FALSE, 'Akun belum tersimpan.'); return; }
        $this->db->trans_commit(); $this->finish(TRUE, 'Akun tersimpan. Riwayat aktivitas tetap dipertahankan.');
    }
    private function finish($ok, $message)
    {
        $this->session->set_flashdata($ok ? 'success' : 'error', $message);
        gh_mobile_redirect('users', 'location', 303);
    }
}
