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
            if (trigger.getAttribute('data-modal-open-ready') === 'true') {
                return;
            }

            trigger.addEventListener('click', function () {
                openModal(document.querySelector(trigger.getAttribute('data-open-modal')));
            });
            trigger.setAttribute('data-modal-open-ready', 'true');
        });

        document.querySelectorAll('[data-close-modal]').forEach(function (trigger) {
            if (trigger.getAttribute('data-modal-close-ready') === 'true') {
                return;
            }

            trigger.addEventListener('click', function () {
                closeModal(trigger.closest('.modal'));
            });
            trigger.setAttribute('data-modal-close-ready', 'true');
        });

        if (document.documentElement.getAttribute('data-modal-escape-ready') === 'true') {
            return;
        }

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('.modal.is-open').forEach(closeModal);
        });
        document.documentElement.setAttribute('data-modal-escape-ready', 'true');
    }

    function initConfirmSave() {
        var pendingForm = null;
        var confirmModal = document.querySelector('[data-save-confirm-modal]');
        var confirmButton = document.querySelector('[data-confirm-save]');

        document.querySelectorAll('form[data-confirm-submit]').forEach(function (form) {
            if (form.getAttribute('data-confirm-submit-ready') === 'true') {
                return;
            }

            form.addEventListener('submit', function (event) {
                if (form.getAttribute('data-confirmed') === 'true') {
                    form.removeAttribute('data-confirmed');
                    return;
                }

                event.preventDefault();
                pendingForm = form;
                openModal(confirmModal);
            });
            form.setAttribute('data-confirm-submit-ready', 'true');
        });

        if (confirmButton && confirmButton.getAttribute('data-confirm-save-ready') !== 'true') {
            confirmButton.addEventListener('click', function () {
                if (!pendingForm) {
                    return;
                }

                pendingForm.setAttribute('data-confirmed', 'true');
                pendingForm.submit();
            });
            confirmButton.setAttribute('data-confirm-save-ready', 'true');
        }
    }

    function initConfirmDelete() {
        var deleteUrl = '';
        var modal = document.querySelector('[data-delete-confirm-modal]');
        var link = document.querySelector('[data-confirm-delete]');
        var label = document.querySelector('[data-delete-label]');

        document.querySelectorAll('[data-delete-url]').forEach(function (trigger) {
            if (trigger.getAttribute('data-delete-ready') === 'true') {
                return;
            }

            trigger.addEventListener('click', function () {
                deleteUrl = trigger.getAttribute('data-delete-url');
                if (label) {
                    label.textContent = trigger.getAttribute('data-delete-label') || 'data ini';
                }
                openModal(modal);
            });
            trigger.setAttribute('data-delete-ready', 'true');
        });

        if (link && link.getAttribute('data-confirm-delete-ready') !== 'true') {
            link.addEventListener('click', function () {
                if (deleteUrl) {
                    window.location.href = deleteUrl;
                }
            });
            link.setAttribute('data-confirm-delete-ready', 'true');
        }
    }

    function rupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    }

    function formatRupiahInput(value) {
        var digits = String(value || '').replace(/\D/g, '');
        if (!digits) {
            return '';
        }

        return 'Rp ' + digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function initMoneyInputs() {
        document.querySelectorAll('[data-money-input]').forEach(function (input) {
            if (input.getAttribute('data-money-ready') === 'true') {
                return;
            }

            function sync() {
                input.value = formatRupiahInput(input.value);
            }

            input.addEventListener('input', sync);
            input.addEventListener('blur', sync);
            sync();
            input.setAttribute('data-money-ready', 'true');
        });
    }

    function initDynamicControls() {
        initModals();
        initConfirmSave();
        initConfirmDelete();
        initMoneyInputs();
        initRoomUploadRows();
        initRoomCascadingFilters();
        initPaymentCascadingFilters();
        initPhotoCascadingFilters();
        initTenantCascadingFilters();
        initPhotoCategoryTabs();
        initPhotoPreview();
    }

    function replaceMasterContent(html) {
        var parser = new DOMParser();
        var nextDoc = parser.parseFromString(html, 'text/html');
        var currentMain = document.querySelector('.main-shell');
        var nextMain = nextDoc.querySelector('.main-shell');

        if (!currentMain || !nextMain) {
            window.location.reload();
            return;
        }

        currentMain.innerHTML = nextMain.innerHTML;
        document.querySelectorAll('body > .modal').forEach(function (modal) {
            modal.remove();
        });
        nextDoc.querySelectorAll('body > .modal').forEach(function (modal) {
            document.body.appendChild(modal);
        });
        document.title = nextDoc.title || document.title;
        initDynamicControls();
    }

    function loadMasterPartial(url, pushState) {
        document.body.classList.add('is-partial-loading');
        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Request failed');
                }
                return response.text();
            })
            .then(function (html) {
                replaceMasterContent(html);
                if (pushState && window.history && window.history.pushState) {
                    window.history.pushState({ ghPartial: true }, '', url);
                }
            })
            .catch(function () {
                window.location.href = url;
            })
            .finally(function () {
                document.body.classList.remove('is-partial-loading');
            });
    }

    function initMetricCardFilters() {
        if (document.documentElement.getAttribute('data-metric-card-ready') === 'true') {
            return;
        }

        document.addEventListener('click', function (event) {
            var card = event.target.closest('.metric-card[href]');
            if (!card) {
                return;
            }

            var url = new URL(card.href, window.location.href);
            if (url.origin !== window.location.origin || url.pathname !== window.location.pathname) {
                return;
            }

            event.preventDefault();
            loadMasterPartial(url.toString(), true);
        });

        window.addEventListener('popstate', function () {
            loadMasterPartial(window.location.href, false);
        });

        document.documentElement.setAttribute('data-metric-card-ready', 'true');
    }

    function initCharts() {
        if (typeof Chart === 'undefined' || !window.GH_DASHBOARD_CHARTS) {
            return;
        }

        var charts = window.GH_DASHBOARD_CHARTS;
        var colors = ['#487fff', '#ff9f29', '#45b369', '#8b5cf6', '#ef4a00', '#00b8d9', '#f866b9', '#7c3aed'];

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

        function makeDatasetBar(id, data, horizontal, stacked, moneyAxis) {
            var el = document.getElementById(id);
            if (!el || !data || !data.datasets) {
                return;
            }

            new Chart(el, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: data.datasets.map(function (dataset, index) {
                        return {
                            label: dataset.label,
                            data: dataset.values,
                            backgroundColor: colors[index % colors.length],
                            borderRadius: 7,
                            borderSkipped: false
                        };
                    })
                },
                options: {
                    indexAxis: horizontal ? 'y' : 'x',
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    var value = moneyAxis ? rupiah(ctx.raw) : Number(ctx.raw || 0).toLocaleString('id-ID');
                                    return ctx.dataset.label + ': ' + value;
                                }
                            }
                        }
                    },
                    responsive: true,
                    scales: {
                        x: {
                            stacked: stacked,
                            beginAtZero: true,
                            ticks: { callback: horizontal && moneyAxis ? rupiah : undefined }
                        },
                        y: {
                            stacked: stacked,
                            beginAtZero: true,
                            ticks: { callback: !horizontal && moneyAxis ? rupiah : undefined }
                        }
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
        makeBar('paymentByPeriodChart', charts.payment_by_period, false);
        makeDatasetBar('collectionByPeriodChart', charts.collection_by_period, false, false, true);
        makeDatasetBar('occupancyByPropertyChart', charts.occupancy_by_property, false, true, false);
        makeDoughnut('roomStatusChart', charts.rooms_by_status);
        makeDoughnut('billStatusChart', charts.bill_status_by_latest_period);
        makeDoughnut('roomMixByTypeChart', charts.room_mix_by_type);
        makeDoughnut('tenantStatusChart', charts.tenant_status);
        makeDoughnut('bookingStatusChart', charts.booking_status);
        makeDoughnut('paymentIssueChart', charts.payment_import_issues);
    }

    var cascadeOptionTemplates = {};

    function getCascadeOptions(select) {
        var key = select.getAttribute('data-cascade-template') || '';
        if (key && cascadeOptionTemplates[key]) {
            return cascadeOptionTemplates[key];
        }

        var options = Array.prototype.slice.call(select.options).map(function (option) {
            return option.cloneNode(true);
        });

        if (key) {
            cascadeOptionTemplates[key] = options;
        }

        return options;
    }

    function hasOptionValue(select, value) {
        return Array.prototype.some.call(select.options, function (option) {
            return option.value === value;
        });
    }

    function setCascadeOptions(select, options, predicate, preferredValue) {
        select.innerHTML = '';
        options.forEach(function (option) {
            if (predicate(option)) {
                select.appendChild(option.cloneNode(true));
            }
        });

        select.value = preferredValue && hasOptionValue(select, preferredValue) ? preferredValue : '';
    }

    function csvIncludes(value, needle) {
        if (!needle) {
            return true;
        }

        return (value || '').split(',').indexOf(needle) !== -1;
    }

    function initPhotoCascadeScope(scope) {
        var propertySelect = scope.querySelector('[data-photo-property]');
        var typeSelect = scope.querySelector('[data-photo-type]');

        if (!propertySelect || !typeSelect || scope.getAttribute('data-photo-cascade-ready') === 'true') {
            return;
        }

        var propertyOptions = getCascadeOptions(propertySelect);
        var typeOptions = getCascadeOptions(typeSelect);

        function sync(keepProperty, keepType) {
            var propertyCode = keepProperty ? propertySelect.value : '';
            var typeValue = keepType ? typeSelect.value : '';

            setCascadeOptions(propertySelect, propertyOptions, function (option) {
                return !option.value || csvIncludes(option.getAttribute('data-type-values'), typeValue);
            }, propertyCode);

            setCascadeOptions(typeSelect, typeOptions, function (option) {
                return !option.value || csvIncludes(option.getAttribute('data-property-codes'), propertySelect.value);
            }, typeValue);
        }

        propertySelect.addEventListener('change', function () {
            sync(true, false);
        });

        typeSelect.addEventListener('change', function () {
            sync(true, true);
        });

        scope.setAttribute('data-photo-cascade-ready', 'true');
        sync(true, true);
    }

    function initPhotoCascadingFilters() {
        document.querySelectorAll('[data-photo-cascade-form], [data-room-upload-row]').forEach(initPhotoCascadeScope);
    }

    function initTenantCascadingFilters() {
        document.querySelectorAll('[data-tenant-cascade-form]').forEach(function (form) {
            var propertySelect = form.querySelector('[data-tenant-property-filter]');
            var typeSelect = form.querySelector('[data-tenant-type-filter]');
            var roomSelect = form.querySelector('[data-tenant-room-select]');

            if (!propertySelect || !typeSelect || !roomSelect || form.getAttribute('data-tenant-cascade-ready') === 'true') {
                return;
            }

            var propertyOptions = getCascadeOptions(propertySelect);
            var typeOptions = getCascadeOptions(typeSelect);
            var roomOptions = getCascadeOptions(roomSelect);

            function propertyMatches(option, typeId) {
                return !option.value || csvIncludes(option.getAttribute('data-type-ids'), typeId);
            }

            function typeMatches(option, propertyId) {
                return !option.value || csvIncludes(option.getAttribute('data-property-ids'), propertyId);
            }

            function roomMatches(option, propertyId, typeId) {
                if (!option.value) {
                    return true;
                }

                return (!propertyId || option.getAttribute('data-property-id') === propertyId)
                    && (!typeId || option.getAttribute('data-type-id') === typeId);
            }

            function sync(keepProperty, keepType, keepRoom) {
                var propertyId = keepProperty ? propertySelect.value : '';
                var typeValue = keepType ? typeSelect.value : '';
                var roomValue = keepRoom ? roomSelect.value : '';

                setCascadeOptions(propertySelect, propertyOptions, function (option) {
                    return propertyMatches(option, typeValue);
                }, propertyId);

                setCascadeOptions(typeSelect, typeOptions, function (option) {
                    return typeMatches(option, propertySelect.value);
                }, typeValue);

                setCascadeOptions(roomSelect, roomOptions, function (option) {
                    return roomMatches(option, propertySelect.value, typeSelect.value);
                }, roomValue);
            }

            propertySelect.addEventListener('change', function () {
                sync(true, false, false);
            });

            typeSelect.addEventListener('change', function () {
                sync(true, true, false);
            });

            roomSelect.addEventListener('change', function () {
                var selected = roomSelect.options[roomSelect.selectedIndex];
                if (!selected || !selected.value) {
                    return;
                }

                typeSelect.value = selected.getAttribute('data-type-id') || '';
                sync(false, true, true);
                propertySelect.value = selected.getAttribute('data-property-id') || '';
                sync(true, true, true);
            });

            form.setAttribute('data-tenant-cascade-ready', 'true');
            sync(true, true, true);
        });
    }

    function initRoomCascadingFilters() {
        document.querySelectorAll('[data-room-cascade-form]').forEach(function (form) {
            var propertySelect = form.querySelector('[data-room-property-filter]');
            var typeSelect = form.querySelector('[data-room-type-filter]');

            if (!propertySelect || !typeSelect || form.getAttribute('data-room-cascade-ready') === 'true') {
                return;
            }

            var propertyOptions = getCascadeOptions(propertySelect);
            var typeOptions = getCascadeOptions(typeSelect);

            function sync(keepProperty, keepType) {
                var propertyId = keepProperty ? propertySelect.value : '';
                var typeValue = keepType ? typeSelect.value : '';

                setCascadeOptions(propertySelect, propertyOptions, function (option) {
                    return !option.value || csvIncludes(option.getAttribute('data-type-ids'), typeValue);
                }, propertyId);

                setCascadeOptions(typeSelect, typeOptions, function (option) {
                    return !option.value || csvIncludes(option.getAttribute('data-property-ids'), propertySelect.value);
                }, typeValue);
            }

            propertySelect.addEventListener('change', function () {
                sync(true, false);
            });

            typeSelect.addEventListener('change', function () {
                sync(true, true);
            });

            form.setAttribute('data-room-cascade-ready', 'true');
            sync(true, true);
        });
    }

    function initRoomUploadRows() {
        var form = document.querySelector('[data-room-upload-form]');
        var rows = form ? form.querySelector('[data-room-upload-rows]') : null;
        var addButton = form ? form.querySelector('[data-room-upload-add]') : null;

        if (!form || !rows || !addButton) {
            return;
        }

        function refreshRow(row, index) {
            var field = 'photos_' + index;
            var hidden = row.querySelector('[data-room-upload-field]');
            var files = row.querySelector('[data-room-upload-files]');

            if (hidden) {
                hidden.name = 'upload_rows[' + index + '][field]';
                hidden.value = field;
            }

            row.querySelectorAll('[data-room-upload-name]').forEach(function (input) {
                input.name = 'upload_rows[' + index + '][' + input.getAttribute('data-room-upload-name') + ']';
            });

            if (files) {
                files.name = field + '[]';
            }
        }

        function refreshRows() {
            rows.querySelectorAll('[data-room-upload-row]').forEach(refreshRow);
        }

        addButton.addEventListener('click', function () {
            var firstRow = rows.querySelector('[data-room-upload-row]');
            var nextRow = firstRow.cloneNode(true);
            nextRow.removeAttribute('data-photo-cascade-ready');

            nextRow.querySelectorAll('select').forEach(function (select) {
                select.selectedIndex = 0;
            });

            nextRow.querySelectorAll('input[type="file"]').forEach(function (input) {
                input.value = '';
            });

            rows.appendChild(nextRow);
            refreshRows();
            initPhotoCascadeScope(nextRow);
        });

        rows.addEventListener('click', function (event) {
            var remove = event.target.closest('[data-room-upload-remove]');
            var activeRows = rows.querySelectorAll('[data-room-upload-row]');

            if (!remove || activeRows.length <= 1) {
                return;
            }

            remove.closest('[data-room-upload-row]').remove();
            refreshRows();
        });

        refreshRows();
    }

    function initPaymentCascadingFilters() {
        var form = document.querySelector('[data-payment-cascade-form]');
        var propertySelect = form ? form.querySelector('[data-cascade-property]') : null;
        var typeSelect = form ? form.querySelector('[data-cascade-type]') : null;
        var roomSelect = form ? form.querySelector('[data-cascade-room]') : null;

        if (!form || !propertySelect || !typeSelect || !roomSelect) {
            return;
        }

        var propertyOptions = getCascadeOptions(propertySelect);
        var typeOptions = getCascadeOptions(typeSelect);
        var roomOptions = getCascadeOptions(roomSelect);

        function optionHasType(option, typeId) {
            if (!option.value || !typeId) {
                return true;
            }

            var ids = (option.getAttribute('data-type-ids') || '').split(',');
            return ids.indexOf(typeId) !== -1;
        }

        function optionHasProperty(option, propertyId) {
            if (!option.value || !propertyId) {
                return true;
            }

            var ids = (option.getAttribute('data-property-ids') || '').split(',');
            return ids.indexOf(propertyId) !== -1;
        }

        function roomMatches(option, propertyId, typeId) {
            if (!option.value) {
                return true;
            }

            return (!propertyId || option.getAttribute('data-property-id') === propertyId)
                && (!typeId || option.getAttribute('data-type-id') === typeId);
        }

        function syncFilters(keepProperty, keepType, keepRoom) {
            var propertyId = keepProperty ? propertySelect.value : '';
            var typeValue = keepType ? typeSelect.value : '';
            var roomValue = keepRoom ? roomSelect.value : '';

            setCascadeOptions(propertySelect, propertyOptions, function (option) {
                return optionHasType(option, typeValue);
            }, propertyId);

            setCascadeOptions(typeSelect, typeOptions, function (option) {
                return optionHasProperty(option, propertySelect.value);
            }, typeValue);

            setCascadeOptions(roomSelect, roomOptions, function (option) {
                return roomMatches(option, propertySelect.value, typeSelect.value);
            }, roomValue);
        }

        propertySelect.addEventListener('change', function () {
            syncFilters(true, false, false);
        });

        typeSelect.addEventListener('change', function () {
            syncFilters(true, true, false);
        });

        roomSelect.addEventListener('change', function () {
            var selected = roomSelect.options[roomSelect.selectedIndex];
            if (!selected || !selected.value) {
                return;
            }

            typeSelect.value = selected.getAttribute('data-type-id') || '';
            syncFilters(false, true, true);
            propertySelect.value = selected.getAttribute('data-property-id') || '';
            syncFilters(true, true, true);
        });

        syncFilters(true, true, true);
    }

    function initPhotoCategoryTabs() {
        document.querySelectorAll('[data-photo-category-tabs]').forEach(function (tabs) {
            var panel = tabs.closest('.panel') || document;
            var buttons = Array.prototype.slice.call(tabs.querySelectorAll('[data-photo-category-filter]'));
            var cards = Array.prototype.slice.call(panel.querySelectorAll('[data-photo-category-card]'));
            var empty = panel.querySelector('[data-photo-category-empty]');

            function applyFilter(category) {
                var visible = 0;

                buttons.forEach(function (button) {
                    button.classList.toggle('is-active', button.getAttribute('data-photo-category-filter') === category);
                });

                cards.forEach(function (card) {
                    var match = category === 'all' || card.getAttribute('data-photo-category') === category;
                    card.hidden = !match;
                    if (match) {
                        visible++;
                    }
                });

                if (empty) {
                    empty.hidden = visible > 0;
                }
            }

            buttons.forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    var category = button.getAttribute('data-photo-category-filter') || 'all';
                    applyFilter(category);

                    if (window.history && window.history.replaceState) {
                        var url = new URL(window.location.href);
                        if (category === 'all') {
                            url.searchParams.delete('category');
                        } else {
                            url.searchParams.set('category', category);
                        }
                        window.history.replaceState({}, '', url.toString());
                    }
                });
            });

            var active = tabs.querySelector('[data-photo-category-filter].is-active');
            applyFilter(active ? active.getAttribute('data-photo-category-filter') || 'all' : 'all');
        });
    }

    function initPhotoPreview() {
        var triggers = Array.prototype.slice.call(document.querySelectorAll('[data-photo-preview]'));
        var modal = document.querySelector('[data-master-photo-modal]');

        if (!triggers.length || !modal) {
            return;
        }

        var stage = modal.querySelector('[data-master-photo-stage]');
        var image = modal.querySelector('[data-master-photo-image]');
        var title = modal.querySelector('[data-master-photo-title]');
        var category = modal.querySelector('[data-master-photo-category]');
        var counter = modal.querySelector('[data-master-photo-counter]');
        var activeIndex = 0;
        var scale = 1;
        var rotate = 0;
        var panX = 0;
        var panY = 0;
        var pointerStart = null;
        var dragged = false;

        function currentItem() {
            return triggers[activeIndex];
        }

        function applyTransform() {
            if (image) {
                image.style.setProperty('--preview-scale', scale);
                image.style.setProperty('--preview-rotate', rotate + 'deg');
                image.style.setProperty('--preview-x', panX + 'px');
                image.style.setProperty('--preview-y', panY + 'px');
            }
        }

        function resetView() {
            scale = 1;
            rotate = 0;
            panX = 0;
            panY = 0;
            applyTransform();
        }

        function show(index) {
            activeIndex = (index + triggers.length) % triggers.length;
            var item = triggers[activeIndex];
            resetView();

            if (image) {
                image.src = item.getAttribute('data-photo-src') || '';
                image.alt = item.getAttribute('data-photo-title') || 'Preview foto';
            }

            if (title) {
                title.textContent = item.getAttribute('data-photo-title') || 'Preview Foto';
            }

            if (category) {
                category.textContent = item.getAttribute('data-photo-category') || '';
            }

            if (counter) {
                counter.textContent = (activeIndex + 1) + ' / ' + triggers.length;
            }
        }

        function open(index) {
            show(index);
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        }

        function close() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            if (image) {
                image.removeAttribute('src');
            }
            resetView();
        }

        triggers.forEach(function (trigger, index) {
            trigger.addEventListener('click', function () {
                open(index);
            });
        });

        modal.querySelectorAll('[data-master-photo-close]').forEach(function (button) {
            button.addEventListener('click', close);
        });

        var prev = modal.querySelector('[data-master-photo-prev]');
        var next = modal.querySelector('[data-master-photo-next]');
        var zoomIn = modal.querySelector('[data-master-photo-zoom-in]');
        var zoomOut = modal.querySelector('[data-master-photo-zoom-out]');
        var rotateLeft = modal.querySelector('[data-master-photo-rotate-left]');
        var rotateRight = modal.querySelector('[data-master-photo-rotate-right]');
        var reset = modal.querySelector('[data-master-photo-reset]');
        var save = modal.querySelector('[data-master-photo-save]');

        if (prev) {
            prev.addEventListener('click', function () {
                show(activeIndex - 1);
            });
        }

        if (next) {
            next.addEventListener('click', function () {
                show(activeIndex + 1);
            });
        }

        if (zoomIn) {
            zoomIn.addEventListener('click', function () {
                scale = Math.min(2.5, scale + .2);
                applyTransform();
            });
        }

        if (zoomOut) {
            zoomOut.addEventListener('click', function () {
                scale = Math.max(.6, scale - .2);
                applyTransform();
            });
        }

        if (rotateLeft) {
            rotateLeft.addEventListener('click', function () {
                rotate -= 90;
                applyTransform();
            });
        }

        if (rotateRight) {
            rotateRight.addEventListener('click', function () {
                rotate += 90;
                applyTransform();
            });
        }

        if (reset) {
            reset.addEventListener('click', function () {
                resetView();
            });
        }

        if (save) {
            save.addEventListener('click', function () {
                var degrees = ((rotate % 360) + 360) % 360;
                var item = currentItem();
                var body = new FormData();

                if (!degrees || !item) {
                    return;
                }

                body.append('scope', item.getAttribute('data-photo-scope') || '');
                body.append('property', item.getAttribute('data-photo-property') || '');
                body.append('type', item.getAttribute('data-photo-type') || '');
                body.append('file', item.getAttribute('data-photo-file') || '');
                body.append('degrees', degrees);
                save.disabled = true;
                save.textContent = 'Menyimpan';

                fetch(save.getAttribute('data-save-url'), {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin'
                })
                    .then(function (response) { return response.json(); })
                    .then(function (payload) {
                        if (!payload || !payload.ok) {
                            throw new Error(payload && payload.message ? payload.message : 'Gagal menyimpan rotasi.');
                        }

                        var src = item.getAttribute('data-photo-src') || '';
                        var separator = src.indexOf('?') === -1 ? '?' : '&';
                        var freshSrc = src + separator + 'v=' + (payload.cache_buster || Date.now());
                        item.setAttribute('data-photo-src', freshSrc);
                        var thumb = item.querySelector('img');
                        if (thumb) {
                            thumb.src = freshSrc;
                        }
                        rotate = 0;
                        panX = 0;
                        panY = 0;
                        scale = 1;
                        if (image) {
                            image.src = freshSrc;
                        }
                        applyTransform();
                    })
                    .catch(function (error) {
                        window.alert(error.message || 'Rotasi foto belum bisa disimpan.');
                    })
                    .finally(function () {
                        save.disabled = false;
                        save.textContent = 'Simpan';
                    });
            });
        }

        if (image) {
            image.setAttribute('draggable', 'false');
            image.addEventListener('dragstart', function (event) {
                event.preventDefault();
            });

            image.addEventListener('wheel', function (event) {
                event.preventDefault();
                var delta = event.deltaY < 0 ? .12 : -.12;
                scale = Math.max(.6, Math.min(3.5, scale + delta));
                applyTransform();
            }, { passive: false });
        }

        if (stage && image) {
            stage.addEventListener('pointerdown', function (event) {
                if (event.button !== 0) {
                    return;
                }

                event.preventDefault();
                pointerStart = {
                    x: event.clientX,
                    y: event.clientY,
                    panX: panX,
                    panY: panY
                };
                dragged = false;
                stage.classList.add('is-dragging');
                image.classList.add('is-dragging');
                stage.setPointerCapture(event.pointerId);
            });

            stage.addEventListener('pointermove', function (event) {
                if (!pointerStart) {
                    return;
                }

                event.preventDefault();
                var moveX = event.clientX - pointerStart.x;
                var moveY = event.clientY - pointerStart.y;
                if (Math.abs(moveX) + Math.abs(moveY) > 6) {
                    dragged = true;
                }

                panX = pointerStart.panX + moveX;
                panY = pointerStart.panY + moveY;
                applyTransform();
            });

            stage.addEventListener('pointerup', function (event) {
                var rect = stage.getBoundingClientRect();
                var clickedLeft = event.clientX < rect.left + rect.width / 2;
                stage.classList.remove('is-dragging');
                image.classList.remove('is-dragging');
                pointerStart = null;

                if (!dragged && scale <= 1.05) {
                    show(clickedLeft ? activeIndex - 1 : activeIndex + 1);
                }
            });

            stage.addEventListener('pointercancel', function () {
                stage.classList.remove('is-dragging');
                image.classList.remove('is-dragging');
                pointerStart = null;
            });
        }

        document.addEventListener('keydown', function (event) {
            if (!modal.classList.contains('is-open')) {
                return;
            }

            if (event.key === 'Escape') {
                close();
            } else if (event.key === 'ArrowLeft') {
                show(activeIndex - 1);
            } else if (event.key === 'ArrowRight') {
                show(activeIndex + 1);
            } else if (event.key === '+') {
                scale = Math.min(3.5, scale + .2);
                applyTransform();
            } else if (event.key === '-') {
                scale = Math.max(.6, scale - .2);
                applyTransform();
            }
        });
    }

    initDynamicControls();
    initMetricCardFilters();
    initCharts();
}());
