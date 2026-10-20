<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    $activeMenu = isset($active_menu) ? (string) $active_menu : '';
    $sidebarGroups = array(
        'Utama' => array(
            'dashboard' => array('label' => 'Dashboard', 'url' => 'dashboard'),
        ),
        'Master Data' => array(
            'properties' => array('label' => 'Properti', 'url' => 'properties'),
            'room-types' => array('label' => 'Tipe Kamar', 'url' => 'room-types'),
            'rooms' => array('label' => 'Kamar', 'url' => 'rooms'),
            'tenants' => array('label' => 'Penghuni', 'url' => 'tenants'),
            'photos' => array('label' => 'Foto', 'url' => 'photos'),
        ),
        'Sales & Dokumen' => array(
            'lifecycle' => array('label' => 'Siklus Penghuni', 'url' => 'lifecycle'),
            'booking' => array('label' => 'Booking', 'url' => 'booking'),
            'contracts' => array('label' => 'Kontrak', 'url' => 'contracts'),
            'documents' => array('label' => 'Dokumen', 'url' => 'documents'),
            'templates' => array('label' => 'Template Dokumen', 'url' => 'templates'),
        ),
        'Keuangan' => array(
            'billing' => array('label' => 'Billing', 'url' => 'billing'),
            'payments' => array('label' => 'Pembayaran', 'url' => 'payments'),
            'receivables' => array('label' => 'Piutang', 'url' => 'receivables'),
            'deposits' => array('label' => 'Deposit', 'url' => 'deposits'),
            'finance' => array('label' => 'Keuangan', 'url' => 'finance'),
            'reports' => array('label' => 'Laporan', 'url' => 'reports'),
        ),
        'Operasional' => array(
            'maintenance' => array('label' => 'Maintenance', 'url' => 'maintenance'),
            'inventory' => array('label' => 'Inventaris', 'url' => 'inventory'),
            'complaints' => array('label' => 'Komplain', 'url' => 'complaints'),
            'facilities' => array('label' => 'Fasilitas', 'url' => 'facilities'),
            'housekeeping' => array('label' => 'Housekeeping', 'url' => 'housekeeping'),
            'audits' => array('label' => 'Audit Bulanan', 'url' => 'audits'),
            'notifications' => array('label' => 'WhatsApp', 'url' => 'notifications'),
        ),
        'Sistem' => array(
            'users' => array('label' => 'Admin & Role', 'url' => 'users'),
        ),
    );
    $sidebarRole = $this->session->userdata('gh_admin_role');
    $sidebarAllowed = null;
    if ($sidebarRole === 'finance') $sidebarAllowed = array('dashboard','lifecycle','payments','billing','receivables','deposits','finance','reports');
    if ($sidebarRole === 'maintenance') $sidebarAllowed = array('dashboard','lifecycle','maintenance','inventory','complaints','facilities','housekeeping');
    foreach ($sidebarGroups as $groupLabel => $items) {
        foreach ($items as $key => $item) {
            if (($sidebarAllowed !== null && !in_array($key, $sidebarAllowed, TRUE)) ||
                ($key === 'users' && in_array($sidebarRole, array('manager','viewer'), TRUE))) unset($sidebarGroups[$groupLabel][$key]);
        }
        if (!$sidebarGroups[$groupLabel]) unset($sidebarGroups[$groupLabel]);
    }
?>
<button type="button" class="admin-nav-toggle" data-admin-nav-toggle aria-expanded="false" aria-controls="gh-admin-navigation"><span aria-hidden="true">☰</span><span data-admin-nav-label>Menu pengelola</span></button>
<aside class="sidebar" id="gh-admin-navigation">
    <a class="brand" href="<?= base_url('dashboard'); ?>"><span>GH</span><strong>GUL HOUSE</strong></a>
    <nav aria-label="Navigasi pengelola">
        <?php foreach ($sidebarGroups as $groupLabel => $items): ?>
            <div class="sidebar-group">
                <span class="sidebar-group-label"><?= html_escape($groupLabel); ?></span>
                <?php foreach ($items as $key => $item): ?>
                    <a class="<?= $activeMenu === $key ? 'is-active' : ''; ?>" href="<?= base_url($item['url']); ?>"><?= html_escape($item['label']); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>
</aside>
