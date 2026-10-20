<?php defined('BASEPATH') OR exit('No direct script access allowed');
    $canResetAccess = $this->session->userdata('gh_admin_role') === 'super_admin' && (!isset($can_reset_access) || $can_reset_access);
    if ($canResetAccess): ?>
<section class="lc-card" id="akses-penghuni">
    <p class="eyebrow">Khusus superadmin</p><h2>Akses portal penghuni</h2>
    <p class="muted">Gunakan reset hanya setelah memeriksa identitas penghuni yang sesuai. Penghuni masuk memakai nomor WhatsApp terdaftar dan kode akses pribadi.</p>
    <details><summary>Reset Kode Akses</summary>
        <div class="lc-note">Kode lama dan seluruh sesi penghuni yang menggunakan kode lama akan dibatalkan. Kode baru ditampilkan sekali untuk disampaikan secara pribadi. Riwayat, dokumen, tagihan dan pembayaran tetap tersimpan.</div>
        <form method="post" action="<?= base_url('lifecycle/reset-code/'.(int)$case['id']); ?>" data-lc-form data-confirm="Reset kode akses penghuni ini? Kode lama dan seluruh sesi lama akan dibatalkan. Simpan kode baru yang ditampilkan sekali dan sampaikan secara pribadi.">
            <label>Alasan reset (10–1.000 karakter)<textarea name="reason" required minlength="10" maxlength="1000"></textarea></label>
            <label class="lc-check"><input type="checkbox" name="identity_verified" value="1" required><span>Saya telah memeriksa identitas penghuni ini dan memastikan permintaan reset berasal dari pihak yang berhak.</span></label>
            <button type="submit">Reset Kode Akses</button>
        </form>
    </details>
</section>
<?php endif; ?>
