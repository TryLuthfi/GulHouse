<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_proof_model extends CI_Model
{
    public function payment($id)
    {
        return $this->db->select('pay.*, rb.tenant_name_snapshot, bp.period_label, r.room_label, p.code AS property_code')->from('gh_payments pay')
            ->join('gh_room_bills rb', 'rb.id=pay.room_bill_id')->join('gh_billing_periods bp', 'bp.id=rb.period_id')->join('gh_rooms r', 'r.id=rb.room_id')->join('gh_properties p', 'p.id=r.property_id', 'left')->where('pay.id', (int) $id)->get()->row_array();
    }

    public function rows($paymentId)
    {
        if (! $this->db->table_exists('gh_payment_proofs')) return array();
        return $this->db->where('payment_id', (int) $paymentId)->order_by('id', 'DESC')->get('gh_payment_proofs')->result_array();
    }

    public function proof($id)
    {
        if (! $this->db->table_exists('gh_payment_proofs')) return null;
        return $this->db->where('id', (int) $id)->get('gh_payment_proofs')->row_array();
    }

    public function path($filename)
    {
        if (! $this->valid_filename($filename)) return FALSE;
        $root = realpath(APPPATH . 'private_payment_proofs');
        $path = $root ? realpath($root . DIRECTORY_SEPARATOR . $filename) : FALSE;
        return $path && strpos($path, $root . DIRECTORY_SEPARATOR) === 0 && is_file($path) ? $path : FALSE;
    }

    public function save_file($paymentId, array $file, $description)
    {
        if (! $this->db->table_exists('gh_payment_proofs') || ! $this->payment($paymentId) || ! isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file($file['tmp_name'])) return FALSE;
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = array('pdf'=>'application/pdf', 'jpg'=>'image/jpeg', 'jpeg'=>'image/jpeg', 'png'=>'image/png');
        $size = filesize($file['tmp_name']);
        if (! isset($allowed[$extension]) || ! $size || $size > 10 * 1024 * 1024) return FALSE;
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if ($mime !== $allowed[$extension] || ($extension !== 'pdf' && ! @getimagesize($file['tmp_name']))) return FALSE;
        $directory = APPPATH . 'private_payment_proofs';
        if (! is_dir($directory) && ! mkdir($directory, 0775, TRUE)) return FALSE;
        $filename = 'proof_' . substr(hash('sha256', $this->db->database), 0, 8) . bin2hex(random_bytes(12)) . '.' . $extension;
        $target = $directory . DIRECTORY_SEPARATOR . $filename;
        if (! move_uploaded_file($file['tmp_name'], $target)) return FALSE;
        $this->db->trans_begin();
        $payment = $this->db->query('SELECT id FROM gh_payments WHERE id=? FOR UPDATE', array((int) $paymentId))->row_array();
        if (! $payment) { $this->db->trans_rollback(); $this->cleanup_failed_upload($filename, $paymentId); return FALSE; }
        $this->db->insert('gh_payment_proofs', array('payment_id'=>(int) $paymentId, 'stored_filename'=>$filename, 'original_filename'=>mb_substr(basename(str_replace('\\', '/', $file['name'])),0,255,'UTF-8'), 'mime_type'=>$mime, 'file_size'=>$size, 'description'=>mb_substr(trim((string) $description),0,500,'UTF-8'), 'uploaded_by'=>(int) $this->session->userdata('gh_admin_id')));
        $id = (int) $this->db->insert_id();
        if (! $this->db->trans_status()) { $this->db->trans_rollback(); $this->cleanup_failed_upload($filename, $paymentId); return FALSE; }
        $this->db->trans_commit();
        return $id;
    }

    public function delete_proof($id)
    {
        $this->db->trans_begin();
        $this->db->query('SELECT id FROM gh_payment_proofs WHERE id=? FOR UPDATE', array((int) $id));
        $proof = $this->proof($id);
        if (! $proof || ! $this->journal($proof, 'pending')) { $this->db->trans_rollback(); return array('deleted'=>FALSE, 'cleanup_pending'=>FALSE); }
        $this->db->where('id', (int) $id)->delete('gh_payment_proofs');
        if (! $this->db->trans_status()) { $this->db->trans_rollback(); return array('deleted'=>FALSE, 'cleanup_pending'=>FALSE); }
        $this->db->trans_commit();
        $result = $this->cleanup_files(array($proof));
        return array('deleted'=>TRUE, 'cleanup_pending'=>$result['cleanup_pending']);
    }

    // Caller collects rows before deleting a payment and invokes this only after its transaction commits.
    public function lock_and_collect($paymentId)
    {
        $this->db->query('SELECT id FROM gh_payments WHERE id=? FOR UPDATE', array((int) $paymentId));
        $proofs = $this->rows($paymentId);
        foreach ($proofs as $proof) if (! $this->journal($proof, 'pending')) return FALSE;
        return $proofs;
    }

    public function cleanup_files(array $proofs)
    {
        $pending = FALSE;
        foreach ($proofs as $proof) {
            if (! $this->valid_filename($proof['stored_filename'])) { $pending = TRUE; continue; }
            if ($this->metadata_exists($proof['stored_filename'])) { $pending = TRUE; continue; }
            $path = $this->path($proof['stored_filename']);
            if ($path && ! $this->unlink_file($path)) { $pending = TRUE; log_message('error', 'Payment proof cleanup pending: database=' . $this->db->database . ' filename=' . $proof['stored_filename']); continue; }
            if (! $this->journal($proof, 'done')) $pending = TRUE;
        }
        return array('cleanup_pending'=>$pending);
    }

    // Dry-run never writes journal entries or changes the filesystem.
    public function reconcile_cleanup($dryRun = TRUE)
    {
        $pending = array();
        $file = $this->journal_path();
        if (is_file($file)) {
            $stream = fopen($file, 'rb');
            if (! $stream || ! flock($stream, LOCK_SH)) return array('error'=>'journal_unavailable');
            while (($line = fgets($stream)) !== FALSE) {
                $event = json_decode($line, TRUE);
                if (! is_array($event) || ($event['database'] ?? '') !== $this->db->database || ! $this->valid_filename($event['stored_filename'] ?? '')) continue;
                if ($event['state'] === 'pending') $pending[$event['stored_filename']] = $event;
                elseif ($event['state'] === 'done') unset($pending[$event['stored_filename']]);
            }
            flock($stream, LOCK_UN); fclose($stream);
        }
        $eligible = array(); $retained = 0;
        foreach ($pending as $proof) {
            if ($this->metadata_exists($proof['stored_filename'])) { $retained++; continue; }
            $eligible[] = $proof;
        }
        $result = array('database'=>$this->db->database, 'pending'=>count($eligible), 'retained_active'=>$retained, 'dry_run'=>(bool) $dryRun);
        if (! $dryRun) $result += $this->cleanup_files($eligible);
        return $result;
    }

    protected function metadata_exists($filename)
    {
        return $this->db->table_exists('gh_payment_proofs') && $this->db->where('stored_filename', $filename)->count_all_results('gh_payment_proofs') > 0;
    }

    protected function unlink_file($path) { return @unlink($path); }

    private function cleanup_failed_upload($filename, $paymentId)
    {
        $proof = array('stored_filename'=>$filename, 'payment_id'=>(int) $paymentId);
        if ($this->journal($proof, 'pending')) $this->cleanup_files(array($proof));
        else {
            $path = $this->path($filename);
            if ($path && ! $this->unlink_file($path)) log_message('error', 'Orphan payment proof requires manual cleanup: database=' . $this->db->database . ' filename=' . $filename);
        }
    }

    protected function valid_filename($filename)
    {
        return preg_match('/^proof_' . substr(hash('sha256', $this->db->database), 0, 8) . '[a-f0-9]{24}\.(pdf|jpg|jpeg|png)$/D', (string) $filename) === 1;
    }

    protected function journal_path()
    {
        return APPPATH . 'private_payment_proofs/cleanup-' . hash('sha256', $this->db->database) . '.jsonl';
    }

    protected function journal(array $proof, $state)
    {
        if (! $this->valid_filename($proof['stored_filename']) || ! in_array($state, array('pending','done'), TRUE)) return FALSE;
        $event = array('database'=>$this->db->database, 'stored_filename'=>$proof['stored_filename'], 'id'=>(int) ($proof['id'] ?? 0), 'payment_id'=>(int) ($proof['payment_id'] ?? 0), 'state'=>$state, 'recorded_at'=>date('c'));
        $stream = @fopen($this->journal_path(), 'c+b');
        if (! $stream) { log_message('error', 'Payment proof cleanup journal unavailable: ' . $this->db->database); return FALSE; }
        if (! flock($stream, LOCK_EX)) { fclose($stream); return FALSE; }
        fseek($stream, 0, SEEK_END); $offset = ftell($stream);
        $line = json_encode($event, JSON_UNESCAPED_SLASHES) . "\n";
        $ok = fwrite($stream, $line) === strlen($line) && fflush($stream);
        if ($ok && function_exists('fsync')) $ok = fsync($stream);
        if (! $ok) { ftruncate($stream, $offset); fflush($stream); log_message('error', 'Payment proof cleanup journal write failed: ' . $this->db->database); }
        flock($stream, LOCK_UN); fclose($stream);
        return $ok;
    }
}
