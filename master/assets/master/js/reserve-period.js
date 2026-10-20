(function () {
    'use strict';

    function daysInMonth(year, month) {
        if (month === 2) {
            return year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0) ? 29 : 28;
        }
        return [4, 6, 9, 11].indexOf(month) !== -1 ? 30 : 31;
    }

    function nextCalendarMonth(value) {
        var match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
        if (!match) { return ''; }
        var year = Number(match[1]);
        var month = Number(match[2]);
        var day = Number(match[3]);
        if (year < 1 || month < 1 || month > 12 || day < 1 || day > daysInMonth(year, month)) { return ''; }
        var targetYear = month === 12 ? year + 1 : year;
        var targetMonth = month === 12 ? 1 : month + 1;
        if (targetYear > 9999) { return ''; }
        var targetDay = Math.min(day, daysInMonth(targetYear, targetMonth));
        return String(targetYear).padStart(4, '0') + '-' + String(targetMonth).padStart(2, '0') + '-' + String(targetDay).padStart(2, '0');
    }

    document.querySelectorAll('[data-reserve-period]').forEach(function (form) {
        var start = form.querySelector('[data-reserve-start]');
        var end = form.querySelector('[data-reserve-end]');
        if (!start || !end) { return; }
        function update() {
            end.value = nextCalendarMonth(start.value);
            start.setCustomValidity(start.value && !end.value ? 'Pilih tanggal mulai yang valid untuk periode satu bulan kalender.' : '');
        }
        start.addEventListener('input', update);
        start.addEventListener('change', update);
        update();
    });
}());
