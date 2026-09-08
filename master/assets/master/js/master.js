(function () {
    function openModal(modal) {
        if (!modal) {
            return;
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        var focusTarget = modal.querySelector('input, select, textarea, button');
        if (focusTarget) {
            focusTarget.focus();
        }
    }

    function closeModal(modal) {
        if (!modal) {
            return;
        }

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function initModals() {
        document.querySelectorAll('[data-open-modal]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                openModal(document.querySelector(trigger.getAttribute('data-open-modal')));
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                closeModal(trigger.closest('.modal'));
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('.modal.is-open').forEach(closeModal);
        });
    }

    function initConfirmSave() {
        var pendingForm = null;
        var confirmModal = document.querySelector('[data-save-confirm-modal]');
        var confirmButton = document.querySelector('[data-confirm-save]');

        document.querySelectorAll('form[data-confirm-submit]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.getAttribute('data-confirmed') === 'true') {
                    form.removeAttribute('data-confirmed');
                    return;
                }

                event.preventDefault();
                pendingForm = form;
                openModal(confirmModal);
            });
        });

        if (confirmButton) {
            confirmButton.addEventListener('click', function () {
                if (!pendingForm) {
                    return;
                }

                pendingForm.setAttribute('data-confirmed', 'true');
                pendingForm.submit();
            });
        }
    }

    function initConfirmDelete() {
        var deleteUrl = '';
        var modal = document.querySelector('[data-delete-confirm-modal]');
        var link = document.querySelector('[data-confirm-delete]');
        var label = document.querySelector('[data-delete-label]');

        document.querySelectorAll('[data-delete-url]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                deleteUrl = trigger.getAttribute('data-delete-url');
                if (label) {
                    label.textContent = trigger.getAttribute('data-delete-label') || 'data ini';
                }
                openModal(modal);
            });
        });

        if (link) {
            link.addEventListener('click', function () {
                if (deleteUrl) {
                    window.location.href = deleteUrl;
                }
            });
        }
    }

    function rupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    }

    function initCharts() {
        if (typeof Chart === 'undefined' || !window.GH_DASHBOARD_CHARTS) {
            return;
        }

        var charts = window.GH_DASHBOARD_CHARTS;
        var colors = ['#173f35', '#c9974c', '#6f8f80', '#d7b26d', '#8d9c93', '#314f46'];

        function makeBar(id, data, horizontal) {
            var el = document.getElementById(id);
            if (!el || !data) {
                return;
            }

            new Chart(el, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.values,
                        backgroundColor: colors,
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    indexAxis: horizontal ? 'y' : 'x',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return rupiah(ctx.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: horizontal ? undefined : rupiah } },
                        x: { beginAtZero: true, ticks: { callback: horizontal ? rupiah : undefined } }
                    }
                }
            });
        }

        function makeDoughnut(id, data) {
            var el = document.getElementById(id);
            if (!el || !data) {
                return;
            }

            new Chart(el, {
                type: 'doughnut',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.values,
                        backgroundColor: colors,
                        borderWidth: 0
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'bottom' }
                    },
                    cutout: '62%'
                }
            });
        }

        makeBar('revenueByPropertyChart', charts.revenue_by_property, false);
        makeBar('revenueByTypeChart', charts.revenue_by_type, true);
        makeDoughnut('roomStatusChart', charts.rooms_by_status);
    }

    initModals();
    initConfirmSave();
    initConfirmDelete();
    initCharts();
}());
