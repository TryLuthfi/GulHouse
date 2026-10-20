(function () {
    'use strict';
    var toggle = document.querySelector('[data-admin-nav-toggle]');
    var navigation = document.getElementById('gh-admin-navigation');
    if (toggle && navigation) {
        var smallScreen = window.matchMedia('(max-width: 980px)');
        var expanded = false;
        function syncNavigation() {
            document.body.dataset.adminNavigation = expanded ? 'open' : 'closed';
            toggle.setAttribute('aria-expanded', smallScreen.matches && !expanded ? 'false' : 'true');
            toggle.querySelector('[data-admin-nav-label]').textContent = expanded && smallScreen.matches ? 'Tutup menu pengelola' : 'Menu pengelola';
        }
        document.body.dataset.adminNavReady = 'true';
        toggle.addEventListener('click', function () { expanded = !expanded; syncNavigation(); });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && expanded && smallScreen.matches) {
                expanded = false;
                syncNavigation();
                toggle.focus();
            }
        });
        if (smallScreen.addEventListener) { smallScreen.addEventListener('change', syncNavigation); }
        else { smallScreen.addListener(syncNavigation); }
        syncNavigation();
    }

    var returnFocus = new WeakMap();
    var dialogTitleId = 0;
    function activeModal() {
        var modals = document.querySelectorAll('.modal.is-open');
        return modals.length ? modals[modals.length - 1] : null;
    }
    function focusable(modal) {
        return Array.prototype.slice.call(modal.querySelectorAll('a[href],button,input:not([type="hidden"]),select,textarea,[tabindex]:not([tabindex="-1"])')).filter(function (element) {
            return !element.disabled && element.getClientRects().length > 0;
        });
    }
    document.addEventListener('click', function (event) {
        var opener = event.target.closest('[data-open-modal]');
        if (opener) {
            var modal = document.querySelector(opener.getAttribute('data-open-modal'));
            if (modal) {
                returnFocus.set(modal, opener);
                var dialog = modal.querySelector('[role="dialog"]') || modal.querySelector('.modal-card') || modal;
                if (dialog !== modal && modal.getAttribute('role') === 'dialog') {
                    modal.removeAttribute('role');
                    modal.removeAttribute('aria-modal');
                }
                dialog.setAttribute('role', 'dialog');
                dialog.setAttribute('aria-modal', 'true');
                if (!dialog.hasAttribute('aria-labelledby') && !dialog.hasAttribute('aria-label')) {
                    var heading = dialog.querySelector('h2');
                    if (heading) {
                        if (!heading.id) {
                            do { dialogTitleId += 1; heading.id = 'admin-dialog-title-' + dialogTitleId; } while (document.getElementById(heading.id) !== heading);
                        }
                        dialog.setAttribute('aria-labelledby', heading.id);
                    }
                }
            }
        }
        var closer = event.target.closest('[data-close-modal]');
        if (closer) {
            var closedModal = closer.closest('.modal');
            window.requestAnimationFrame(function () {
                var trigger = closedModal && returnFocus.get(closedModal);
                if (trigger && !closedModal.classList.contains('is-open')) { trigger.focus(); }
            });
        }
    });
    document.addEventListener('keydown', function (event) {
        var modal = activeModal();
        if (!modal) { return; }
        if (event.key === 'Tab') {
            var items = focusable(modal);
            if (!items.length) { return; }
            var first = items[0], last = items[items.length - 1];
            if (event.shiftKey && (document.activeElement === first || !modal.contains(document.activeElement))) {
                event.preventDefault(); last.focus();
            } else if (!event.shiftKey && (document.activeElement === last || !modal.contains(document.activeElement))) {
                event.preventDefault(); first.focus();
            }
        }
        if (event.key === 'Escape') {
            window.requestAnimationFrame(function () {
                var trigger = returnFocus.get(modal);
                if (trigger && !modal.classList.contains('is-open')) { trigger.focus(); }
            });
        }
    }, true);
}());
