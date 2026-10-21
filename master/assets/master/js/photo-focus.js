(function () {
    'use strict';
    document.querySelectorAll('[data-photo-focus-form]').forEach(function (form) {
        var card = form.closest('.photo-card');
        var preview = card && card.querySelector('[data-photo-focus-preview]');
        var horizontal = form.querySelector('[name="focus_x"]');
        var vertical = form.querySelector('[name="focus_y"]');
        if (!preview || !horizontal || !vertical) return;
        function updatePreview() {
            var x = Math.max(0, Math.min(100, Number(horizontal.value) || 0));
            var y = Math.max(0, Math.min(100, Number(vertical.value) || 0));
            preview.style.objectPosition = x + '% ' + y + '%';
            form.querySelector('[data-focus-output="x"]').textContent = x + '%';
            form.querySelector('[data-focus-output="y"]').textContent = y + '%';
        }
        horizontal.addEventListener('input', updatePreview);
        vertical.addEventListener('input', updatePreview);
        updatePreview();
    });
}());
