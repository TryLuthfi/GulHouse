<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Shared resident web authentication; access codes are never recoverable. */
class Resident_access_model extends CI_Model
{
    public static function phone($value)
    {
        if (!is_scalar($value)) return '';
        $value=trim((string)$value);
        if (!preg_match('/^\+?[0-9 ()\-.]{8,32}$/D',$value)) return '';
        $digits=preg_replace('/\D/','',$value);
        if (strpos($digits,'6208')===0) $digits='62'.substr($digits,3);
        elseif (strpos($digits,'08')===0) $digits='62'.substr($digits,1);
        return preg_match('/^[0-9]{9,15}$/D',$digits)?$digits:'';
    }

    public function credentials($phone,$code)
    {
        if (!is_string($code) || !preg_match('/^[a-f0-9]{64}$/D',$code)) return NULL;
        $number=self::phone($phone);if ($number==='') return NULL;
        $case=$this->db->where('access_hash',hash('sha256',$code))->get('gh_resident_cases')->row_array();
        return $case && hash_equals(self::phone($case['phone']),$number)?$case:NULL;
    }

    public function establish($case)
    {
        $this->session->sess_regenerate(TRUE);
        $this->session->unset_userdata('gh_payment_access');
        $this->session->set_userdata('gh_resident_case',array(
            'id'=>(int)$case['id'],'verified_at'=>time(),'credential_proof'=>$case['access_hash'],
        ));
    }

    public function clear()
    {
        $this->session->unset_userdata(array('gh_resident_case','gh_payment_access','resident_access_code'));
    }

    public function current()
    {
        $auth=$this->session->userdata('gh_resident_case');
        if (!is_array($auth) || empty($auth['id']) || empty($auth['verified_at']) ||
            $auth['verified_at']<time()-3600 || !is_string($auth['credential_proof']??NULL)) {
            $this->clear();return NULL;
        }
        $case=$this->db->where('id',(int)$auth['id'])->get('gh_resident_cases')->row_array();
        if (!$case || !hash_equals((string)$case['access_hash'],$auth['credential_proof'])) {
            $this->clear();return NULL;
        }
        return $case;
    }

    public function reset($id,$actorId,$reason,$verified)
    {
        $reason=is_string($reason)?trim($reason):'';
        if (mb_strlen($reason)<10 || mb_strlen($reason)>1000 || !is_scalar($verified) || (string)$verified!=='1')
            throw new RuntimeException('Verifikasi identitas dan isi alasan reset 10–1.000 karakter.');
        $this->db->trans_begin();
        try {
            $actor=$this->db->query('SELECT id,role,is_active FROM gh_admin_users WHERE id=? FOR UPDATE',array((int)$actorId))->row_array();
            if (!$actor || !(int)$actor['is_active'] || $actor['role']!=='super_admin')
                throw new RuntimeException('Reset kode akses hanya tersedia untuk super admin aktif.');
            $case=$this->db->query('SELECT * FROM gh_resident_cases WHERE id=? FOR UPDATE',array((int)$id))->row_array();
            if (!$case) throw new RuntimeException('Pendaftaran penghuni tidak ditemukan.');
            $code=bin2hex(random_bytes(32));
            $this->db->where('id',$case['id'])->update('gh_resident_cases',array('access_hash'=>hash('sha256',$code)));
            if ($this->db->table_exists('gh_mobile_tokens')) {
                $this->db->where('subject_kind','resident')->where('subject_id',$case['id'])->where('revoked_at',NULL)
                    ->update('gh_mobile_tokens',array('revoked_at'=>date('Y-m-d H:i:s')));
                if (!empty($case['stay_id']) && !empty($case['tenant_id'])) {
                    $stay=$this->db->where('id',$case['stay_id'])->where('tenant_id',$case['tenant_id'])->get('gh_tenant_stays')->row_array();
                    if ($stay) $this->db->where('subject_kind','payment')->where('subject_id',$stay['id'])->where('revoked_at',NULL)
                        ->update('gh_mobile_tokens',array('revoked_at'=>date('Y-m-d H:i:s')));
                }
            }
            $this->db->insert('gh_resident_events',array('case_id'=>$case['id'],'event_type'=>'access_reset',
                'description'=>'Kode akses diganti setelah verifikasi identitas. Alasan: '.$reason,
                'actor'=>'admin:'.(int)$actorId));
            if (!$this->db->trans_status()) throw new RuntimeException('Reset belum tersimpan. Coba kembali.');
            $this->db->trans_commit();return $code;
        } catch (Throwable $e) { $this->db->trans_rollback();throw $e; }
    }
}
