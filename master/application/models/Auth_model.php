<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth_model extends CI_Model
{
    public function attempt_login($username, $password)
    {
        if ($username === '' || $password === '' || ! $this->db->table_exists('gh_admin_users')) {
            return FALSE;
        }

        $admin = $this->db
            ->where('username', $username)
            ->where('is_active', 1)
            ->limit(1)
            ->get('gh_admin_users')
            ->row_array();

        if ( ! $admin || ! password_verify($password, $admin['password_hash'])) {
            return FALSE;
        }

        return $admin;
    }

    public function record_login($adminId)
    {
        $this->db->where('id', $adminId)->update('gh_admin_users', array(
            'last_login_at' => date('Y-m-d H:i:s'),
        ));

        if ($this->db->table_exists('gh_activity_logs')) {
            $this->db->insert('gh_activity_logs', array(
                'admin_user_id' => $adminId,
                'action' => 'login',
                'description' => 'Admin masuk ke web master.',
                'ip_address' => $this->input->ip_address(),
                'user_agent' => substr((string) $this->input->user_agent(), 0, 255),
            ));
        }
    }
}
