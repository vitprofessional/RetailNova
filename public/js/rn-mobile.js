/*
 * RetailNova mobile app shell (UI-1): back button, "More" page, home alerts and the mobile sale screen.
 * The sale screen never posts anything itself; it drives the existing sale form controls.
 */
(function () {
    'use strict';

    var BN_DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    var isBangla = (document.documentElement.lang || '').toLowerCase().indexOf('bn') === 0;

    function digits(text) {
        text = String(text);
        return isBangla ? text.replace(/[0-9]/g, function (d) { return BN_DIGITS[d]; }) : text;
    }
    window.RNLocalDigits = digits;

    function sameOriginReferrer() {
        try { return !!document.referrer && new URL(document.referrer).origin === window.location.origin; } catch (e) { return false; }
    }

    // Back: return to the previous page of the app when there is one, else follow the link.
    document.addEventListener('click', function (event) {
        var back = event.target.closest('[data-rn-back]');
        if (back && window.history.length > 1 && sameOriginReferrer()) {
            event.preventDefault();
            window.history.back();
        }
    });

    // "More" page: CSS opens it on #rn-more; closing returns to where the user was.
    var more = document.getElementById('rn-more');
    if (more) {
        var openedByTap = false;
        document.addEventListener('click', function (event) {
            if (event.target.closest('[data-rn-tab="more"]')) { openedByTap = true; }
            if (event.target.closest('[data-rn-more-close]') && openedByTap && window.location.hash === '#rn-more') {
                event.preventDefault();
                openedByTap = false;
                window.history.back();
            }
        });
        window.addEventListener('hashchange', function () {
            if (window.location.hash === '#rn-more') {
                var first = more.querySelector('[data-rn-more-close]');
                if (first) { first.focus({preventScroll: true}); }
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && window.location.hash === '#rn-more') {
                var close = more.querySelector('[data-rn-more-close]');
                if (close) { close.click(); }
            }
        });
    }

    // Home header bell: a small panel with the current alerts.
    var bell = document.querySelector('[data-rn-notifications-toggle]');
    var alerts = document.querySelector('[data-rn-notifications]');
    if (bell && alerts) {
        var setAlertsOpen = function (open) {
            alerts.hidden = !open;
            bell.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        bell.addEventListener('click', function () { setAlertsOpen(alerts.hidden); });
        document.addEventListener('click', function (event) {
            if (!alerts.hidden && !alerts.contains(event.target) && !bell.contains(event.target)) { setAlertsOpen(false); }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !alerts.hidden) { setAlertsOpen(false); bell.focus(); }
        });
    }

    initSaleScreen(document.querySelector('[data-rn-pos]'));

    function initSaleScreen(pos) {
        var form = document.getElementById('saveSaleForm');
        if (!pos || !form) { return; }

        var el = function (id) { return document.getElementById(id); };
        var rows = el('productDetails');
        var customer = el('customerName');
        var product = el('productName');
        var cards = Array.prototype.slice.call(pos.querySelectorAll('[data-rn-pos-item]'));
        var search = pos.querySelector('[data-rn-pos-search]');
        var none = pos.querySelector('[data-rn-pos-none]');
        var toastEl = pos.querySelector('[data-rn-pos-toast]');
        var bar = {
            count: pos.querySelector('[data-rn-pos-count]'),
            total: pos.querySelector('[data-rn-pos-total]'),
            button: pos.querySelector('[data-rn-pos-checkout]')
        };
        var sheet = pos.querySelector('[data-rn-checkout]');
        var sheetCustomer = pos.querySelector('[data-rn-checkout-customer]');
        var sheetItems = pos.querySelector('[data-rn-checkout-items]');
        var sheetDiscount = pos.querySelector('[data-rn-checkout-discount]');
        var given = pos.querySelector('[data-rn-checkout-given]');
        var result = pos.querySelector('[data-rn-checkout-result]');
        var activeCategory = '';

        var currency = pos.dataset.currency || '';
        var currencyRight = pos.dataset.currencyPosition === 'right';
        function number(value) { var n = parseFloat(value); return isFinite(n) ? n : 0; }
        function money(amount) {
            var n = Math.round(number(amount) * 100) / 100;
            var text = Math.abs(n).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 2});
            text = currencyRight ? text + currency : currency + text;
            return digits(n < 0 ? '-' + text : text);
        }
        function template(text, count) { return String(text || '').replace(':count', digits(count)); }
        function fire(input, type) { input.dispatchEvent(new Event(type || 'input', {bubbles: true})); }
        function toast(message) {
            if (!toastEl) { return; }
            toastEl.textContent = message;
            toastEl.hidden = false;
            clearTimeout(toast.timer);
            toast.timer = setTimeout(function () { toastEl.hidden = true; }, 3200);
        }

        // ---- Product grid: search, category chips, tap to add ----
        function filterCards() {
            var query = (search.value || '').trim().toLocaleLowerCase();
            var shown = 0;
            cards.forEach(function (card) {
                var visible = (!activeCategory || card.dataset.category === activeCategory)
                    && (!query || (card.dataset.search || '').indexOf(query) !== -1);
                card.hidden = !visible;
                if (visible) { shown++; }
            });
            if (none) { none.hidden = shown > 0; }
        }
        search.addEventListener('input', filterCards);
        pos.addEventListener('click', function (event) {
            var chip = event.target.closest('[data-rn-pos-category]');
            if (!chip) { return; }
            activeCategory = chip.dataset.rnPosCategory;
            pos.querySelectorAll('[data-rn-pos-category]').forEach(function (other) {
                var on = other === chip;
                other.classList.toggle('is-active', on);
                other.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            filterCards();
        });

        // The sale form is customer-first: use the walk-in customer when none is chosen yet.
        function ensureCustomer() {
            if (customer && customer.value) { return Promise.resolve(); }
            var walkIn = el('useWalkingCustomerBtn');
            if (walkIn) { walkIn.click(); }
            return customer && customer.value ? Promise.resolve() : Promise.reject();
        }
        function waitFor(check, timeout) {
            return new Promise(function (resolve, reject) {
                var started = Date.now();
                (function poll() {
                    var found = check();
                    if (found) { resolve(found); return; }
                    if (Date.now() - started > (timeout || 5000)) { reject(); return; }
                    setTimeout(poll, 120);
                })();
            });
        }
        function addProduct(id, button) {
            ensureCustomer()
                .then(function () {
                    return waitFor(function () {
                        return product && !product.disabled && product.querySelector('option[value="' + id + '"]');
                    });
                })
                .then(function () {
                    product.value = id;
                    fire(product, 'change');
                    if (button) {
                        button.classList.add('is-added');
                        setTimeout(function () { button.classList.remove('is-added'); }, 900);
                    }
                })
                .catch(function () { toast(pos.dataset.i18nUnavailable); });
        }
        pos.addEventListener('click', function (event) {
            var card = event.target.closest('[data-product-id]');
            if (card && !card.disabled) { addProduct(card.dataset.productId, card); }
        });

        // Barcode: hand the code to the existing barcode lookup.
        var barcodeBox = el('rnPosBarcode');
        var barcodeToggle = pos.querySelector('[data-rn-pos-barcode-toggle]');
        var barcodeInput = pos.querySelector('[data-rn-pos-barcode]');
        barcodeToggle.addEventListener('click', function () {
            var open = barcodeBox.hidden;
            barcodeBox.hidden = !open;
            barcodeToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) { barcodeInput.focus(); }
        });
        function lookupBarcode() {
            var code = (barcodeInput.value || '').trim();
            if (!code) { return; }
            ensureCustomer()
                .then(function () { return waitFor(function () { var b = el('searchBarcodeBtn'); return b && !b.disabled && b; }); })
                .then(function (button) {
                    var target = el('barcodeInput');
                    target.value = code;
                    fire(target);
                    button.click();
                    barcodeInput.value = '';
                })
                .catch(function () { toast(pos.dataset.i18nUnavailable); });
        }
        pos.querySelector('[data-rn-pos-barcode-go]').addEventListener('click', lookupBarcode);
        barcodeInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') { event.preventDefault(); lookupBarcode(); }
        });

        // ---- Bottom bar: "N items · total · Take Payment" ----
        function saleRows() { return rows ? Array.prototype.slice.call(rows.querySelectorAll('tr[data-product-summary]')) : []; }
        function refreshBar(withSheet) {
            var count = saleRows().length;
            bar.count.textContent = template(pos.dataset.i18nItems, count);
            bar.total.textContent = money(el('grandTotal') ? el('grandTotal').value : 0);
            bar.button.disabled = count === 0;
            if (withSheet !== false && !sheet.hidden) { refreshSheet(); }
        }
        if (rows) { new MutationObserver(refreshBar).observe(rows, {childList: true, subtree: true}); }
        form.addEventListener('input', function () { setTimeout(refreshBar, 0); });
        form.addEventListener('change', function () { setTimeout(refreshBar, 0); });
        // Totals are also set by script without events; a light poll keeps the bar in step.
        setInterval(function () { refreshBar(false); }, 1000);
        refreshBar();

        // ---- Checkout sheet ----
        function copyCustomers() {
            if (!customer) { return; }
            sheetCustomer.innerHTML = customer.innerHTML;
            sheetCustomer.value = customer.value;
        }
        function renderLines() {
            sheetItems.innerHTML = '';
            saleRows().forEach(function (row) {
                var qtyInput = row.querySelector('.qty');
                var item = document.createElement('li');
                item.className = 'rn-m-line';
                item.dataset.productGroup = row.dataset.productGroup || '';
                var body = document.createElement('div');
                body.className = 'rn-m-line-body';
                var name = document.createElement('strong');
                name.textContent = (row.querySelector('.rn-sale-product-name') || {}).textContent || '';
                var total = document.createElement('span');
                total.textContent = money((row.querySelector('.rn-sale-line-total') || {}).textContent || 0);
                body.appendChild(name);
                body.appendChild(total);
                var stepper = document.createElement('div');
                stepper.className = 'rn-m-stepper';
                stepper.innerHTML = '<button type="button" data-rn-step="-1"></button><output></output><button type="button" data-rn-step="1"></button>';
                stepper.children[0].textContent = '−';
                stepper.children[0].setAttribute('aria-label', pos.dataset.i18nDecrease);
                stepper.children[1].textContent = digits(qtyInput ? qtyInput.value : 1);
                stepper.children[2].textContent = '+';
                stepper.children[2].setAttribute('aria-label', pos.dataset.i18nIncrease);
                item.appendChild(body);
                item.appendChild(stepper);
                sheetItems.appendChild(item);
            });
        }
        sheetItems.addEventListener('click', function (event) {
            var step = event.target.closest('[data-rn-step]');
            if (!step) { return; }
            var group = step.closest('.rn-m-line').dataset.productGroup;
            var row = rows.querySelector('tr[data-product-group="' + group + '"][data-product-summary]');
            var button = row && row.querySelector('[data-sale-qty="' + step.dataset.rnStep + '"]');
            if (button) { button.click(); setTimeout(refreshSheet, 50); }
        });

        function grand() { return number(el('grandTotal') ? el('grandTotal').value : 0); }
        function method() { var checked = pos.querySelector('[data-rn-checkout-method]:checked'); return checked ? checked.value : 'cash'; }
        function applyPayment() {
            var paid = el('paidAmount');
            if (!paid) { return; }
            // The chosen method is posted through the sale form's hidden payment_method field.
            if (el('paymentMethod')) { el('paymentMethod').value = method(); }
            if (el('paymentMethodSelect')) { el('paymentMethodSelect').value = method(); }
            var total = grand();
            var gave = method() === 'due' ? 0 : number(given.value);
            paid.value = String(Math.min(Math.max(gave, 0), total));
            fire(paid);
            var change = gave - total;
            result.classList.toggle('is-change', change > 0.004);
            result.classList.toggle('is-due', change < -0.004);
            result.textContent = change > 0.004
                ? pos.dataset.i18nChange + ' ' + money(change)
                : (change < -0.004 ? pos.dataset.i18nDue + ' ' + money(-change) : '');
        }
        function refreshSheet() {
            renderLines();
            pos.querySelector('[data-rn-sum="subtotal"]').textContent = money(el('totalSaleAmount') ? el('totalSaleAmount').value : 0);
            pos.querySelector('[data-rn-sum="grand"]').textContent = money(grand());
            if (document.activeElement !== sheetDiscount && el('discountAmount')) { sheetDiscount.value = el('discountAmount').value; }
        }
        function openSheet() {
            copyCustomers();
            refreshSheet();
            if (!given.value) { given.value = String(grand()); }
            applyPayment();
            sheet.hidden = false;
            document.body.style.overflow = 'hidden';
            sheet.querySelector('[data-rn-checkout-close]').focus();
        }
        function closeSheet() {
            sheet.hidden = true;
            document.body.style.overflow = '';
            bar.button.focus();
        }
        bar.button.addEventListener('click', openSheet);
        sheet.querySelector('[data-rn-checkout-close]').addEventListener('click', closeSheet);
        sheet.addEventListener('keydown', function (event) { if (event.key === 'Escape') { closeSheet(); } });

        sheetCustomer.addEventListener('change', function () {
            if (!customer || customer.value === sheetCustomer.value) { return; }
            customer.value = sheetCustomer.value;
            fire(customer, 'change');
        });
        sheetDiscount.addEventListener('input', function () {
            var discount = el('discountAmount');
            if (!discount) { return; }
            discount.value = sheetDiscount.value;
            fire(discount);
            setTimeout(function () { refreshSheet(); applyPayment(); }, 0);
        });
        pos.querySelectorAll('[data-rn-checkout-method]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                given.disabled = method() === 'due';
                if (method() === 'due') { given.value = '0'; } else if (!number(given.value)) { given.value = String(grand()); }
                applyPayment();
            });
        });
        given.addEventListener('input', applyPayment);
        pos.addEventListener('click', function (event) {
            var quick = event.target.closest('[data-rn-quick]');
            if (!quick) { return; }
            var total = grand();
            var step = quick.dataset.rnQuick === 'exact' ? 0 : number(quick.dataset.rnQuick);
            given.value = String(step ? Math.max(step, Math.ceil(total / step) * step) : total);
            applyPayment();
        });
        pos.querySelector('[data-rn-checkout-complete]').addEventListener('click', function () {
            applyPayment();
            closeSheet();
            var save = el('saleSavePrintButton') || el('saleSaveButton');
            if (save) { save.click(); }
        });

        filterCards();
    }
})();
