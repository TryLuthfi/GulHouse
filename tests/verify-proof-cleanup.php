<?php
// Real filesystem failure/retry test; no database connection or production rows.
define('BASEPATH', __DIR__);
define('APPPATH', dirname(__DIR__) . '/master/application/');
class CI_Model { public $db; }
function log_message($level, $message) {}
require APPPATH . 'models/Payment_proof_model.php';
class CleanupProbe extends Payment_proof_model {
    public $failUnlink = TRUE;
    public $failJournal = FALSE;
    public $active = array();
    protected function metadata_exists($filename) { return in_array($filename, $this->active, TRUE); }
    protected function unlink_file($path) { return $this->failUnlink ? FALSE : parent::unlink_file($path); }
    protected function journal(array $proof, $state) { return $this->failJournal ? FALSE : parent::journal($proof, $state); }
    public function prepare($proof) { return $this->journal($proof, 'pending'); }
    public function journalFile() { return $this->journal_path(); }
}
$checks = 0;
function check_cleanup($condition, $label) { global $checks; if (! $condition) throw new RuntimeException($label); $checks++; }
$schema = 'gulhouse_test_cleanup_' . bin2hex(random_bytes(8));
$probe = new CleanupProbe(); $probe->db = (object) array('database'=>$schema);
$other = new CleanupProbe(); $other->db = (object) array('database'=>$schema . '_other'); $other->failUnlink = FALSE;
$filename = 'proof_' . substr(hash('sha256', $schema),0,8) . bin2hex(random_bytes(12)) . '.pdf';
$file = APPPATH . 'private_payment_proofs/' . $filename;
$proof = array('id'=>901,'payment_id'=>902,'stored_filename'=>$filename);
try {
    file_put_contents($file, 'synthetic cleanup fixture');
    $probe->failJournal = TRUE;
    check_cleanup($probe->prepare($proof) === FALSE && is_file($file), 'Queue failure must preserve file and reject preparation');
    $probe->failJournal = FALSE;
    check_cleanup($probe->prepare($proof), 'Pending event durably recorded');
    $before = file_get_contents($probe->journalFile());
    $preview = $probe->reconcile_cleanup(TRUE);
    check_cleanup($preview['pending'] === 1 && is_file($file) && file_get_contents($probe->journalFile()) === $before, 'Dry run cannot mutate file or journal');
    $foreign = $other->reconcile_cleanup(FALSE);
    check_cleanup($foreign['pending'] === 0 && is_file($file) && $other->path($filename) === FALSE, 'Other database cannot drain or access proof');
    $failed = $probe->cleanup_files(array($proof));
    check_cleanup($failed['cleanup_pending'] === TRUE && is_file($file), 'Unlink failure leaves explicit pending result');
    check_cleanup($probe->reconcile_cleanup(TRUE)['pending'] === 1, 'Failed unlink remains retryable');
    $probe->active = array($filename);
    $probe->failUnlink = FALSE;
    $active = $probe->reconcile_cleanup(FALSE);
    check_cleanup($active['retained_active'] === 1 && is_file($file), 'Rollback/live metadata prevents deletion');
    $probe->active = array();
    $retry = $probe->reconcile_cleanup(FALSE);
    check_cleanup(! $retry['cleanup_pending'] && ! is_file($file), 'Retry removes exact file');
    check_cleanup($probe->reconcile_cleanup(TRUE)['pending'] === 0, 'Completion recorded');
    check_cleanup(! $probe->prepare(array('stored_filename'=>'../../outside.pdf')), 'Traversal rejected');
    echo "PASS {$checks} proof cleanup assertions\n";
} finally {
    foreach (array($file, $probe->journalFile(), $other->journalFile()) as $owned) if (is_file($owned)) unlink($owned);
}
