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

            nextRow.querySelectorAll('select').forEach(function (select) {
                select.selectedIndex = 0;
            });

            nextRow.querySelectorAll('input[type="file"]').forEach(function (input) {
                input.value = '';
            });

            rows.appendChild(nextRow);
            refreshRows();
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

    initModals();
    initConfirmSave();
    initConfirmDelete();
    initCharts();
    initRoomUploadRows();
    initPhotoPreview();
}());
