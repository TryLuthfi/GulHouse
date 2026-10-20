(function () {
    'use strict';

    function flag(value) { return value === true || value === 1 || value === '1'; }

    function update(form) {
        var type = form.querySelector('[data-settlement-type]');
        var amount = form.querySelector('[data-settlement-amount]');
        var save = form.querySelector('[data-settlement-save]');
        var status = form.querySelector('[data-settlement-status]');
        var quote;
        try { quote = JSON.parse(form.getAttribute('data-settlement-quote') || '{}'); } catch (error) { quote = {}; }
        var kind = type.value;
        var validType = kind === 'rent' || kind === 'deposit';
        var remaining = Number(quote[kind + '_remaining']);
        var known = validType && Object.prototype.hasOwnProperty.call(quote, kind + '_remaining') && Number.isSafeInteger(remaining) && remaining >= 0;
        var available = flag(quote[kind + '_available']);
        var allowed = known && available && remaining > 0 && flag(quote['can_pay_' + kind]);
        var message;
        amount.value = known ? 'Rp ' + remaining.toLocaleString('id-ID') : '';
        if (!known) { message = 'Rincian pembayaran belum tersedia. Muat ulang halaman.'; }
        else if (!available) { message = quote[kind + '_reason'] || 'Pembayaran belum tersedia untuk invoice ini.'; }
        else if (remaining === 0) { message = 'Sudah lunas. Tidak ada sisa pembayaran untuk jenis ini.'; }
        else if (!allowed) { message = quote[kind + '_reason'] || 'Pembayaran belum dapat dicatat untuk jenis ini.'; }
        else { message = 'Nominal otomatis sesuai sisa ' + (kind === 'rent' ? 'sewa' : 'deposit') + '. Catat hanya transfer yang sudah diterima.'; }
        status.textContent = message;
        type.setCustomValidity(allowed ? '' : message);
        save.disabled = !allowed;
        return allowed;
    }

    document.querySelectorAll('form[data-payment-settlement]').forEach(update);
    document.addEventListener('change', function (event) {
        if (event.target.matches('[data-settlement-type]')) { update(event.target.closest('form[data-payment-settlement]')); }
    });
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.matches('form[data-payment-settlement]')) { return; }
        if (!update(form)) {
            event.preventDefault();
            event.stopImmediatePropagation();
            form.reportValidity();
        }
    }, true);
}());
