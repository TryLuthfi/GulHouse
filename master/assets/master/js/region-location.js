(function () {
    'use strict';
    var source = document.getElementById('gh-region-data');
    if (!source) { return; }
    var regions;
    try { regions = JSON.parse(source.textContent); } catch (error) { return; }
    var regencies = regions.regencies || [];
    var districts = regions.districts || [];
    function populate(select, rows, parentKey, parentId, selectedId, placeholder) {
        select.replaceChildren(new Option(placeholder, ''));
        rows.forEach(function (row) {
            if (String(row[parentKey]) === String(parentId) && parentId) {
                select.add(new Option(row.name, row.id, false, String(row.id) === String(selectedId)));
            }
        });
    }
    document.querySelectorAll('[data-location-scope]').forEach(function (section) {
        var form = section.closest('form');
        var province = section.querySelector('[data-region-province]');
        var regency = section.querySelector('[data-region-regency]');
        var district = section.querySelector('[data-region-district]');
        var unitScope = section.dataset.locationScope === 'unit';
        var property = form.querySelector(unitScope ? '[name="property_id"]' : '[name="property_type"]');
        var legacy = section.querySelector('[data-region-legacy]');
        function enabledScope() {
            if (!property) { return true; }
            var type = unitScope ? (property.selectedOptions[0] || {}).dataset : null;
            return unitScope ? !!(type && type.propertyType === 'apartment') : property.value !== 'apartment';
        }
        function sync() {
            var active = enabledScope();
            section.hidden = !active;
            section.style.display = active ? '' : 'none';
            section.querySelectorAll('input,select,textarea').forEach(function (input) { input.disabled = !active; });
            if (active) {
                province.disabled = !(regions.provinces || []).length;
                regency.disabled = !province.value;
                district.disabled = !regency.value;
            }
            regency.required = active && !!province.value;
            district.required = active && !!province.value;
            if (legacy) { legacy.hidden = !!regency.value; }
        }
        province.addEventListener('change', function () {
            populate(regency, regencies, 'province_id', province.value, '', province.value ? 'Pilih kabupaten/kota' : 'Pilih provinsi dahulu');
            populate(district, [], 'regency_id', '', '', 'Pilih kabupaten/kota dahulu');
            sync();
        });
        regency.addEventListener('change', function () {
            populate(district, districts, 'regency_id', regency.value, '', regency.value ? 'Pilih kecamatan' : 'Pilih kabupaten/kota dahulu');
            sync();
        });
        if (property) { property.addEventListener('change', sync); }
        form.addEventListener('reset', function () { window.setTimeout(sync, 0); });
        sync();
    });
}());
