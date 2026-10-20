<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<form method="post" action="<?= base_url('logout'); ?>" data-logout-form>
    <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()); ?>" value="<?= html_escape($this->security->get_csrf_hash()); ?>">
    <button type="submit" data-logout>Logout</button>
</form>
