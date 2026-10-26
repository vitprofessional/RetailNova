<div class="rn-q6-purchase" data-q6-purchase data-locale="{{ app()->getLocale() }}" data-currency="{{ $currencySymbol }}" data-sale-price-short="{{ __('mobile.sale_price_short') }}" data-serial-label="{{ __('mobile.serials_short') }}">
    <header class="rn-q6-header"><a href="{{ route('purchaseList') }}" data-purchase-leave aria-label="{{ __('common.cancel') }}"><i class="las la-arrow-left" aria-hidden="true"></i></a><h1>{{ __('mobile.purchase_title') }}</h1></header>
    <main class="rn-q6-main" data-q6-main>
        <section class="rn-q6-supplier-card">
            <span class="rn-q6-supplier-icon" aria-hidden="true"><i class="las la-truck"></i></span>
            <div class="rn-q6-supplier-copy"><small>{{ __('mobile.supplier') }}</small><strong data-q6-supplier-name>{{ __('mobile.select_supplier') }}</strong><span>{{ __('mobile.previous_due') }} <b data-q6-opening-due>—</b></span></div>
            <button type="button" data-q6-open-supplier>{{ __('mobile.change') }}</button>
        </section>
        <div class="rn-q6-meta-row">
            <button type="button" data-q6-open-date><i class="las la-calendar" aria-hidden="true"></i><span data-q6-date-label></span></button>
            <span class="rn-q6-chip"><i class="las la-file-invoice" aria-hidden="true"></i><span data-q6-invoice-label></span></span>
            <button type="button" data-q6-open-more>{{ __('mobile.more_information') }}</button>
        </div>
        <section class="rn-q6-items" aria-labelledby="q6PurchaseItemsTitle">
            <h2 id="q6PurchaseItemsTitle">{{ __('mobile.purchased_items') }} <span data-q6-item-count>(০)</span></h2>
            <div class="rn-q6-empty" data-q6-empty><i class="las la-box-open" aria-hidden="true"></i><strong>{{ __('mobile.purchase_empty_title') }}</strong><span>{{ __('mobile.purchase_empty_help') }}</span></div>
            <div class="rn-q6-lines" data-q6-lines></div>
            <button type="button" class="rn-q6-add" data-q6-open-picker><i class="las la-plus" aria-hidden="true"></i>{{ __('mobile.add_product') }}</button>
        </section>
    </main>
    <footer class="rn-q6-sticky" data-q6-main-footer><div><small>{{ __('mobile.total') }} (<span data-q6-total-qty>০</span> {{ __('mobile.pieces') }})</small><strong data-q6-total><x-m.money :value="0" /></strong></div><button type="button" data-q6-open-payment>{{ __('mobile.money_and_save') }}<i class="las la-angle-right" aria-hidden="true"></i></button></footer>

    <section class="rn-q6-payment" data-q6-payment hidden aria-labelledby="q6PaymentTitle">
        <header class="rn-q6-header"><button type="button" data-q6-back-main aria-label="{{ __('common.cancel') }}"><i class="las la-arrow-left" aria-hidden="true"></i></button><h1 id="q6PaymentTitle">{{ __('mobile.money_and_save') }}</h1></header>
        <select name="payment_method" data-q6-mobile-method hidden><option value="cash">cash</option><option value="bkash">bkash</option><option value="nagad">nagad</option><option value="bank">bank</option><option value="due">due</option></select>
        <div class="rn-q6-payment-content">
            <section class="rn-q6-card rn-q6-total-card"><div><span>{{ __('mobile.products_total') }} (<span data-q6-payment-count>০</span> {{ __('mobile.pieces') }})</span><strong data-q6-subtotal><x-m.money :value="0" /></strong></div>
                <div class="rn-q6-discount"><label for="q6DiscountAmount">{{ __('common.discount_amount') }}</label><div class="rn-q6-discount-type"><button type="button" data-q6-discount-type="1" class="is-active">৳</button><button type="button" data-q6-discount-type="2">%</button></div><input id="q6DiscountAmount" type="number" min="0" step="0.01" inputmode="decimal" value="0" data-q6-discount-value></div>
                <div class="rn-q6-total-final"><span>{{ __('mobile.grand_total') }}</span><strong data-q6-grand><x-m.money :value="0" /></strong></div>
            </section>
            <section class="rn-q6-card rn-q6-method-card"><h2>{{ __('mobile.how_pay') }}</h2><div class="rn-q6-methods" role="group" aria-label="{{ __('common.payment_method') }}">
                @foreach(['cash','bkash','nagad','bank','due'] as $method)<button type="button" data-q6-method="{{ $method }}">{{ $method === 'bank' ? __('common.bank') : __('sales.payment_method_'.$method) }}</button>@endforeach
            </div><label class="rn-q6-label" for="q6PaidAmount">{{ __('mobile.paid_supplier') }}</label><input id="q6PaidAmount" type="number" min="0" step="0.01" inputmode="decimal" value="0" data-q6-paid>
                <div class="rn-q6-quick"><button type="button" data-q6-paid-fraction="1">{{ __('mobile.pay_full') }}</button><button type="button" data-q6-paid-fraction="0.5">{{ __('mobile.pay_half') }}</button><button type="button" data-q6-paid-fraction="0">{{ __('mobile.pay_none') }}</button></div>
                <div class="rn-q6-due"><span>{{ __('mobile.supplier_due') }}</span><strong data-q6-new-due><x-m.money :value="0" /></strong></div>
                <p class="rn-q6-previous-due">{{ __('mobile.previous_due_adds') }} <strong data-q6-due-total><x-m.money :value="0" /></strong></p>
            </section>
            <details class="rn-q6-card rn-q6-note"><summary>{{ __('mobile.add_note') }}</summary><label for="q6Note">{{ __('purchases.special_note') }}</label><textarea id="q6Note" rows="3" data-q6-note></textarea></details>
        </div>
        <footer class="rn-q6-payment-footer"><button type="button" data-q6-back-main>{{ __('common.cancel') }}</button><button type="button" data-q6-submit>{{ __('mobile.save_purchase') }}</button></footer>
    </section>

    <div class="rn-q6-sheet" data-q6-sheet="supplier" hidden><button class="rn-q6-scrim" type="button" data-q6-close-sheet aria-label="{{ __('mobile.close') }}"></button><section class="rn-q6-sheet-panel" role="dialog" aria-modal="true" aria-labelledby="q6SupplierTitle"><header><span></span><h2 id="q6SupplierTitle">{{ __('mobile.select_supplier') }}</h2><button type="button" data-q6-close-sheet aria-label="{{ __('mobile.close') }}">×</button></header><label class="rn-q6-search"><i class="las la-search" aria-hidden="true"></i><input type="search" placeholder="{{ __('mobile.search_supplier') }}" data-q6-supplier-search></label><div class="rn-q6-supplier-options" data-q6-supplier-options>@foreach($supplierList ?? [] as $supplierData)<button type="button" data-q6-supplier="{{ $supplierData->id }}" data-name="{{ $supplierData->name }}" data-due="{{ (float) ($supplierData->q6PreviousDue ?? $supplierData->openingBalance ?? 0) }}">{{ $supplierData->name }}<small><x-m.money :value="$supplierData->q6PreviousDue ?? $supplierData->openingBalance ?? 0" /></small></button>@endforeach</div></section></div>
    <div class="rn-q6-sheet" data-q6-sheet="date" hidden><button class="rn-q6-scrim" type="button" data-q6-close-sheet aria-label="{{ __('mobile.close') }}"></button><section class="rn-q6-sheet-panel" role="dialog" aria-modal="true"><header><span></span><h2>{{ __('common.date') }}</h2><button type="button" data-q6-close-sheet aria-label="{{ __('mobile.close') }}">×</button></header><label>{{ __('common.date') }}<input type="date" data-q6-date></label><button type="button" class="rn-q6-sheet-done" data-q6-close-sheet>{{ __('common.save') }}</button></section></div>
    <div class="rn-q6-sheet" data-q6-sheet="more" hidden><button class="rn-q6-scrim" type="button" data-q6-close-sheet aria-label="{{ __('mobile.close') }}"></button><section class="rn-q6-sheet-panel" role="dialog" aria-modal="true"><header><span></span><h2>{{ __('mobile.more_information') }}</h2><button type="button" data-q6-close-sheet aria-label="{{ __('mobile.close') }}">×</button></header><label>{{ __('common.reference') }}<input type="text" data-q6-reference></label><p class="rn-q6-location"><span>{{ __('purchases.location') }}</span><strong>{{ $businesses->first()?->businessLocation ?? '—' }}</strong></p><button type="button" class="rn-q6-sheet-done" data-q6-close-sheet>{{ __('common.save') }}</button></section></div>

    <div class="rn-q6-sheet rn-q6-product-sheet" data-q6-sheet="products" hidden><button class="rn-q6-scrim" type="button" data-q6-close-sheet aria-label="{{ __('mobile.close') }}"></button><section class="rn-q6-sheet-panel" role="dialog" aria-modal="true" aria-labelledby="q6ProductPickerTitle"><header><span></span><h2 id="q6ProductPickerTitle">{{ __('mobile.add_product') }}</h2><button type="button" data-q6-close-sheet aria-label="{{ __('mobile.close') }}">×</button></header><div class="rn-q6-picker-slot" data-q6-picker-slot></div></section></div>

    <div class="rn-q6-sheet" data-q6-sheet="line" hidden><button class="rn-q6-scrim" type="button" data-q6-close-sheet aria-label="{{ __('mobile.close') }}"></button><section class="rn-q6-sheet-panel rn-q6-line-panel" role="dialog" aria-modal="true" aria-labelledby="q6LineTitle"><header><span></span><h2 id="q6LineTitle" data-q6-line-name></h2><button type="button" data-q6-close-sheet aria-label="{{ __('mobile.close') }}">×</button></header><p data-q6-line-meta></p>
        <label>{{ __('common.quantity') }}<div class="rn-q6-stepper"><button type="button" data-q6-step="-1">−</button><input type="number" min="1" step="1" data-q6-line-qty><button type="button" data-q6-step="1">+</button></div></label>
        <div class="rn-q6-line-prices"><label>{{ __('common.buy_price') }} ({{ __('mobile.per_item') }})<input type="number" min="0" step="0.01" data-q6-line-buy></label><label>{{ __('common.sale_price_ex_vat') }} ({{ __('mobile.per_item') }})<input type="number" min="0" step="0.01" data-q6-line-sale></label></div>
        <div class="rn-q6-profit"><span>{{ __('mobile.profit_per_item') }}</span><strong data-q6-profit></strong></div>
        <button type="button" class="rn-q6-serial" data-q6-open-serial><i class="las la-barcode" aria-hidden="true"></i>{{ __('purchases.serials') }} <span data-q6-serial-count></span><i class="las la-angle-right" aria-hidden="true"></i></button>
        <details class="rn-q6-optional"><summary>{{ __('mobile.optional_vat') }}</summary><label><input type="checkbox" data-q6-vat-check> {{ __('purchases.vat_include') }}</label><label>{{ __('purchases.vat_percent') }}<input type="number" min="0" max="100" step="0.01" data-q6-vat-percent></label><label>{{ __('sales.warranty_days') }}<input type="number" min="0" step="1" data-q6-warranty></label></details>
        <footer><button type="button" data-q6-remove-line>{{ __('common.remove') }}</button><button type="button" data-q6-line-done>{{ __('mobile.done') }}</button></footer>
    </section></div>
</div>
