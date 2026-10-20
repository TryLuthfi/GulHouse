(function () {
    'use strict';
    var form = document.querySelector('[data-landing-photo-form]');
    if (!form) return;
    var previews = Array.prototype.slice.call(form.querySelectorAll('[data-landing-preview-select]'));
    function validateOrder() {
        previews.forEach(function (select) {
            var duplicate = select.value && previews.some(function (other) {
                return other !== select && other.value === select.value;
            });
            select.setCustomValidity(duplicate ? 'Pilih empat foto Preview yang berbeda.' : '');
        });
    }
    form.querySelectorAll('[data-landing-photo-select]').forEach(function (select) {
        select.addEventListener('change', function () {
            var slot = select.closest('[data-landing-photo-slot]');
            var image = slot.querySelector('[data-landing-photo-image]');
            var empty = slot.querySelector('[data-landing-photo-empty]');
            var option = select.options[select.selectedIndex];
            var src = option ? option.getAttribute('data-src') : '';
            image.hidden = !src;
            empty.hidden = !!src;
            if (src) image.src = src;
            else image.removeAttribute('src');
            validateOrder();
        });
    });
    validateOrder();
}());
