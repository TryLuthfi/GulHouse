<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Monthly scheduler; real-schema access requires explicit CLI opt-in. */
class Jobs extends CI_Controller
{
    public function __construct()
    {
        if (PHP_SAPI !== 'cli') { show_404(); exit; }
        $schema = getenv('GULHOUSE_DATABASE');
        $synthetic = is_string($schema) && preg_match('/^gulhouse_test_[A-Za-z0-9_]+$/D', $schema);
        $realOptIn = $schema === 'gulhouse_db' && getenv('GULHOUSE_BILLING_ALLOW_REAL') === '1';
        if (! $synthetic && ! $realOptIn) {
            $this->finish(array('ok' => FALSE, 'error' => 'An explicit synthetic gulhouse_test_* database is required.'), 2);
        }
        parent::__construct();
        $this->db->db_debug = FALSE;
        $connection = $this->db->query('SELECT DATABASE() AS selected_database');
        if ($this->db->database !== $schema || ! $connection || $connection->row_array()['selected_database'] !== $schema) {
            $this->finish(array('ok' => FALSE, 'error' => 'The connected database does not match the explicit billing target.'), 2);
        }
        $this->load->model('Operations_model');
    }

    public function billing($period = '', $mode = 'preview')
    {
        date_default_timezone_set('Asia/Jakarta');
        $period = $period === '' ? date('Y-m') : $period;
        if (! $this->Operations_model->valid_period($period) || ! in_array($mode, array('preview', 'apply'), TRUE)) {
            $this->finish(array('ok' => FALSE, 'error' => 'Use a valid YYYY-MM period and preview or apply mode.'), 2);
        }
        $schema = $this->db->database;
        $selected = $this->db->where('period_key', $period)->get('gh_billing_periods')->row_array();
        $existing = $selected ? $this->db->where('period_id', $selected['id'])->count_all_results('gh_room_bills') : 0;
        $result = array('ok' => TRUE, 'database' => $schema, 'period' => $period, 'timezone' => 'Asia/Jakarta',
            'mode' => $mode, 'existing_bills' => $existing, 'generated_or_updated_rows' => 0, 'new_bill_rows_since_start' => 0);
        if ($mode === 'preview') {
            $result['proof_cleanup'] = $this->proof_cleanup(TRUE);
            $this->finish($result, 0);
        }
        $lock = 'gh_billing_' . substr(hash('sha256', $schema . '|' . $period), 0, 48);
        $acquired = $this->db->query('SELECT GET_LOCK(?, 10) AS acquired', array($lock));
        if (! $acquired || (int) $acquired->row_array()['acquired'] !== 1) {
            $this->finish(array('ok' => FALSE, 'error' => 'Another billing job is running; retry later.'), 1);
        }
        try {
            $created = $this->Operations_model->generate_billing($period);
            $result['ok'] = $created !== FALSE;
            $result['generated_or_updated_rows'] = $created === FALSE ? 0 : (int) $created;
            if ($created !== FALSE) {
                $afterPeriod = $this->db->where('period_key', $period)->get('gh_billing_periods')->row_array();
                $afterCount = $afterPeriod ? $this->db->where('period_id', $afterPeriod['id'])->count_all_results('gh_room_bills') : 0;
                $result['new_bill_rows_since_start'] = $afterCount - $existing;
            }
            if ($created === FALSE) { $result['error'] = 'Billing generator failed; inspect the private application log.'; }
            if ($created !== FALSE) { $result['proof_cleanup'] = $this->proof_cleanup(FALSE); }
        } catch (Throwable $error) {
            // Do not print exception/database details into task scheduler logs.
            $result['ok'] = FALSE; $result['error'] = 'Billing job failed; inspect the private application log.';
        } finally {
            $this->db->query('SELECT RELEASE_LOCK(?)', array($lock));
        }
        $this->finish($result, $result['ok'] ? 0 : 1);
    }

    public function automatic_billing($mode = 'preview', $leadDays = '7')
    {
        date_default_timezone_set('Asia/Jakarta');
        if(!in_array($mode,array('preview','apply'),TRUE)||!is_scalar($leadDays)||!preg_match('/^[0-9]{1,2}$/D',(string)$leadDays)||(int)$leadDays<2||(int)$leadDays>31)$this->finish(array('ok'=>FALSE,'error'=>'Use preview/apply and a lead window from 2 through 31 days.'),2);
        try{$result=$this->Operations_model->automatic_billing(NULL,(int)$leadDays,$mode==='apply');$result['database']=$this->db->database;$this->finish($result,$result['ok']?0:1);}
        catch(Throwable $e){$this->finish(array('ok'=>FALSE,'error'=>'Automatic billing could not complete; inspect the private application log.'),1);}
    }

    private function finish($result, $code)
    {
        echo json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL;
        exit($code);
    }

    private function proof_cleanup($dryRun)
    {
        if (! is_file(APPPATH . 'models/Payment_proof_model.php') || ! $this->db->table_exists('gh_payment_proofs')) {
            return array('status' => 'not_initialized', 'dry_run' => (bool) $dryRun);
        }
        try {
            $this->load->model('Payment_proof_model');
            $cleanup = $this->Payment_proof_model->reconcile_cleanup($dryRun);
            $cleanup['dry_run'] = (bool) $dryRun;
            if (! $dryRun && ! isset($cleanup['error'])) {
                $after = $this->Payment_proof_model->reconcile_cleanup(TRUE);
                $cleanup['remaining_pending'] = isset($after['pending']) ? (int) $after['pending'] : NULL;
            }
            // The queue/model is scoped to this exact database and does not change payment amounts.
            return $cleanup;
        } catch (Throwable $error) {
            return array('status' => 'cleanup_pending', 'dry_run' => (bool) $dryRun,
                'error' => 'Proof cleanup requires retry; any completed billing remains committed.');
        }
    }
}
