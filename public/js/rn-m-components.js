/*
 * RetailNova mobile components (UI-2): bottom sheets, the ⋮ menu of list cards and "load more".
 * Events are delegated from the document, so cards added by "load more" work the same way.
 * Delete-like actions inside the menus are confirmed by the layout's global handler.
 */
(function () {
    'use strict';

    var digits = window.RNLocalDigits || function (text) { return String(text); };

    // ---- Bottom sheets: [data-rn-sheet-open="id"] opens, [data-rn-sheet-close] closes ----
    var openSheet = null;
    var sheetOpener = null;

    function setSheet(sheet, open, opener) {
        sheet.hidden = !open;
        document.body.style.overflow = open ? 'hidden' : '';
        document.querySelectorAll('[data-rn-sheet-open="' + sheet.id + '"]').forEach(function (button) {
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        if (open) {
            openSheet = sheet;
            sheetOpener = opener || null;
            var first = sheet.querySelector('select, input:not([type="hidden"]), textarea, button');
            if (first) { first.focus({preventScroll: true}); }
        } else {
            openSheet = null;
            if (sheetOpener) { sheetOpener.focus({preventScroll: true}); }
        }
    }

    document.addEventListener('click', function (event) {
        var opener = event.target.closest('[data-rn-sheet-open]');
        if (opener) {
            var sheet = document.getElementById(opener.getAttribute('data-rn-sheet-open'));
            if (sheet) { event.preventDefault(); setSheet(sheet, true, opener); }
            return;
        }
        var closer = event.target.closest('[data-rn-sheet-close]');
        if (closer && openSheet && openSheet.contains(closer)) {
            event.preventDefault();
            setSheet(openSheet, false);
        }
    });

    // Filter and search forms send only the fields that have a value, so page URLs stay short.
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.matches || !form.matches('[data-rn-list] form[method="GET"], [data-rn-list] form:not([method])')) { return; }
        form.querySelectorAll('input[name], select[name]').forEach(function (field) {
            if (field.value === '') { field.disabled = true; field.setAttribute('data-rn-was-empty', ''); }
        });
    });
    // Coming back to the page (back/forward cache) re-enables those fields.
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('[data-rn-was-empty]').forEach(function (field) {
            field.disabled = false;
            field.removeAttribute('data-rn-was-empty');
        });
    });

    // ---- ⋮ menu of a card ----
    var openMenu = null;

    function setMenu(button, open) {
        var menu = document.getElementById(button.getAttribute('aria-controls'));
        if (!menu) { return; }
        menu.hidden = !open;
        menu.classList.remove('is-up');
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        openMenu = open ? button : null;
        if (!open) { return; }
        // Open upwards when the menu would end under the tab bar.
        var tabbar = document.querySelector('[data-rn-tabbar]');
        var limit = tabbar ? tabbar.getBoundingClientRect().top : window.innerHeight;
        if (menu.getBoundingClientRect().bottom > limit - 8) { menu.classList.add('is-up'); }
        var first = menu.querySelector('[role="menuitem"]');
        if (first) { first.focus({preventScroll: true}); }
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-rn-card-menu]');
        if (button) {
            event.preventDefault();
            var wasOpen = openMenu === button;
            if (openMenu) { setMenu(openMenu, false); }
            if (!wasOpen) { setMenu(button, true); }
            return;
        }
        if (openMenu && !event.target.closest('.rn-m-card-actions')) { setMenu(openMenu, false); }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') { return; }
        if (openMenu) { var button = openMenu; setMenu(button, false); button.focus(); return; }
        if (openSheet) { setSheet(openSheet, false); }
    });

    // ---- "Load more": next page of cards from the list's JSON endpoint ----
    function countText(box, shown, total) {
        var format = function (n) { return digits(Number(n).toLocaleString('en-US')); };
        return String(box.getAttribute('data-i18n-count') || '').replace(':shown', format(shown)).replace(':total', format(total));
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-rn-load-more-button]');
        if (!button || button.getAttribute('aria-busy') === 'true') { return; }
        var box = button.closest('[data-rn-load-more]');
        var list = document.getElementById(box.getAttribute('data-rn-load-more'));
        var status = box.querySelector('[data-rn-load-more-count]');
        var url = button.getAttribute('data-url');
        if (!list || !url) { return; }

        button.setAttribute('aria-busy', 'true');
        fetch(url, {credentials: 'same-origin', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (response) {
                if (!response.ok) { throw new Error('HTTP ' + response.status); }
                return response.json();
            })
            .then(function (page) {
                list.insertAdjacentHTML('beforeend', page.html || '');
                status.textContent = countText(box, page.shown, page.total);
                if (page.next_url) {
                    button.setAttribute('data-url', page.next_url);
                } else {
                    button.removeAttribute('data-url');
                    box.hidden = true;
                }
            })
            .catch(function () { status.textContent = box.getAttribute('data-i18n-failed') || ''; })
            .then(function () { button.removeAttribute('aria-busy'); });
    });

    document.addEventListener('change', function (event) {
        var input = event.target;
        if (!input.matches || !input.matches('.rn-m-file-picker input[type="file"]')) { return; }
        var picker = input.closest('.rn-m-file-picker');
        var name = picker && picker.querySelector('[data-rn-file-name]');
        if (!name) { return; }
        name.textContent = input.files && input.files.length ? input.files[0].name : name.getAttribute('data-empty-label') || name.textContent;
    });
})();
