/**
 * validate.js - JavaScript for form validation and interactivity.
 *  1. Field validation driven by data-rule attributes (mirrors PHP rules)
 *  2. Live table filter while typing
 *  3. Confirmation dialogs for delete / return actions
 *  4. Due-date preview on the Issue Book form
 *  5. Flash message auto-hide
 */
(function () {
    'use strict';

    /* ---------- 1. Validation rules ---------- */
    var RULES = {
        name:   { ok: function (v) { return /^[A-Za-z][A-Za-z .'-]{1,99}$/.test(v); },
                  msg: 'Use letters only (2-100 characters).' },
        email:  { ok: function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v) && v.length <= 120; },
                  msg: 'Enter a valid email address.' },
        phone:  { ok: function (v) { return /^[6-9][0-9]{9}$/.test(v); },
                  msg: 'Enter a 10-digit mobile number starting with 6-9.' },
        isbn:   { ok: function (v) { return /^([0-9]{10}|[0-9]{13})$/.test(v); },
                  msg: 'ISBN must be 10 or 13 digits (no hyphens).' },
        enroll: { ok: function (v) { return /^[A-Za-z0-9\/-]{5,20}$/.test(v); },
                  msg: 'Enrollment no. must be 5-20 letters or digits.' },
        text:   { ok: function (v, el) {
                      var min = parseInt(el.getAttribute('data-min') || '2', 10);
                      var max = parseInt(el.getAttribute('data-max') || '150', 10);
                      return v.length >= min && v.length <= max;
                  },
                  msg: 'This field is required (2-150 characters).' },
        copies: { ok: function (v) { return /^[0-9]+$/.test(v) && +v >= 1 && +v <= 999; },
                  msg: 'Enter a whole number from 1 to 999.' },
        days:   { ok: function (v) { return /^[0-9]+$/.test(v) && +v >= 1 && +v <= 30; },
                  msg: 'Loan period must be 1 to 30 days.' },
        select: { ok: function (v) { return v !== ''; },
                  msg: 'Please choose an option.' }
    };

    function checkField(el) {
        var rule = RULES[el.getAttribute('data-rule')];
        if (!rule) { return true; }
        var value = el.value.trim();
        var valid = rule.ok(value, el);
        var box = el.parentElement.querySelector('.field-error');
        el.classList.toggle('is-invalid', !valid);
        el.classList.toggle('is-valid', valid);
        if (box) { box.textContent = valid ? '' : rule.msg; }
        return valid;
    }

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        var fields = form.querySelectorAll('[data-rule]');

        fields.forEach(function (el) {
            el.addEventListener('blur', function () { checkField(el); });
            el.addEventListener('input', function () {
                if (el.classList.contains('is-invalid')) { checkField(el); }
            });
            // digits only for phone / ISBN boxes
            if (el.hasAttribute('data-digits')) {
                el.addEventListener('input', function () {
                    el.value = el.value.replace(/\D/g, '');
                });
            }
        });

        form.addEventListener('submit', function (ev) {
            var allValid = true;
            fields.forEach(function (el) { if (!checkField(el)) { allValid = false; } });
            if (!allValid) {
                ev.preventDefault();
                var firstBad = form.querySelector('.is-invalid');
                if (firstBad) { firstBad.focus(); }
            }
        });
    });

    /* ---------- 2. Live table filter ---------- */
    document.querySelectorAll('input[data-filter-table]').forEach(function (box) {
        var table = document.getElementById(box.getAttribute('data-filter-table'));
        if (!table) { return; }
        box.addEventListener('input', function () {
            var term = box.value.trim().toLowerCase();
            table.querySelectorAll('tbody tr:not(.empty-row)').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().indexOf(term) === -1 ? 'none' : '';
            });
        });
    });

    /* ---------- 3. Confirmation dialogs ---------- */
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            if (!window.confirm(form.getAttribute('data-confirm'))) { ev.preventDefault(); }
        });
    });

    /* ---------- 4. Due-date preview ---------- */
    var days = document.getElementById('days');
    var preview = document.getElementById('duePreview');
    if (days && preview) {
        var update = function () {
            var n = parseInt(days.value, 10);
            if (isNaN(n) || n < 1 || n > 30) { preview.textContent = '-'; return; }
            var d = new Date();
            d.setDate(d.getDate() + n);
            preview.textContent = d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        };
        days.addEventListener('input', update);
        update();
    }

    /* ---------- 5. Flash auto-hide ---------- */
    document.querySelectorAll('.alert[data-autohide]').forEach(function (box) {
        setTimeout(function () {
            box.style.opacity = '0';
            setTimeout(function () { box.style.display = 'none'; }, 500);
        }, 6000);
    });
})();
