<?php defined('BASEPATH') OR exit('No direct script access allowed'); $this->load->view('resident/header');
    $submittedPhone = $this->input->post('phone', TRUE);
    $previousPhone = is_scalar($submittedPhone) ? substr(trim((string)$submittedPhone), 0, 30) : '';
?>
<section class="lc-hero"><p class="eyebrow">Layanan penghuni</p><h1>Satu portal untuk masa tinggal dan pembayaran Anda.</h1><p>Lihat tagihan, lengkapi biodata, pantau fasilitas, ajukan keluar dan ikuti pengembalian deposit.</p></section>
<div class="lc-split">
    <section class="lc-card"><h2>Masuk portal penghuni</h2><p class="muted">Gunakan nomor WhatsApp terdaftar dan kode akses pribadi dari pendaftaran atau pengelola. Riwayat tetap tersedia setelah checkout.</p>
        <form method="post" action="<?= base_url('resident/access'); ?>" data-lc-form>
            <div class="lc-fields">
                <label>Nomor WhatsApp terdaftar<input type="tel" name="phone" required maxlength="30" inputmode="tel" autocomplete="tel" value="<?= html_escape($previousPhone); ?>"></label>
                <label>Kode akses pribadi<input type="password" name="access_code" required maxlength="160" autocomplete="current-password" spellcheck="false"></label>
            </div>
            <div class="lc-form-footer"><button type="submit">Masuk portal</button><a href="<?= base_url('resident/start'); ?>">Daftar calon penghuni</a></div>
        </form>
    </section>
    <aside class="lc-card"><h3>Kode akses bersifat pribadi</h3><p>Jangan kirim kode akses ke grup atau membagikannya melalui tautan publik. Hubungi pengelola bila kode hilang; identitas Anda akan diperiksa sebelum kode diganti.</p><div class="lc-note">Pembayaran dan layanan penghuni menggunakan portal yang sama. Tidak perlu memasukkan NIK untuk login.</div></aside>
</div>
<?php $this->load->view('resident/footer'); ?>
