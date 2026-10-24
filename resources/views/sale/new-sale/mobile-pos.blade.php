{{--
    Mobile sale screen (mobile version only). It sits outside #saveSaleForm and its controls have no name,
    so nothing here is submitted: every action drives the existing sale form (customer, product
    selector, quantity buttons, discount, paid amount, payment method and save button), which
    keeps all sale rules and POST fields unchanged.
--}}
@php
    $pos = app(\App\Services\PosCatalog::class)->cards($productList ?? []);
    $money = fn ($amount) => \App\Support\LocalizedNumber::money($amount);
@endphp
<section class="rn-m-pos" data-rn-pos aria-label="{{ __('mobile.sale') }}"
         data-currency="{{ \App\Support\Currency::symbol() }}" data-currency-position="{{ \App\Support\Currency::position() }}"
         data-i18n-items="{{ __('mobile.items_count') }}" data-i18n-change="{{ __('mobile.give_change') }}"
         data-i18n-due="{{ __('mobile.remains_due') }}" data-i18n-unavailable="{{ __('mobile.not_available') }}"
         data-i18n-decrease="{{ __('mobile.decrease') }}" data-i18n-increase="{{ __('mobile.increase') }}">
    <div class="rn-m-pos-search">
        <i class="ri-search-line" aria-hidden="true"></i>
        <label class="sr-only" for="rnPosSearch">{{ __('mobile.search_products') }}</label>
        <input id="rnPosSearch" type="search" autocomplete="off" placeholder="{{ __('mobile.search_products') }}" data-rn-pos-search>
        <button type="button" class="rn-m-icon-btn" data-rn-pos-barcode-toggle aria-expanded="false" aria-controls="rnPosBarcode" aria-label="{{ __('mobile.scan_barcode') }}"><i class="ri-barcode-line" aria-hidden="true"></i></button>
    </div>
    <div id="rnPosBarcode" class="rn-m-pos-barcode" hidden>
        <label class="sr-only" for="rnPosBarcodeInput">{{ __('mobile.scan_barcode') }}</label>
        <input id="rnPosBarcodeInput" type="text" inputmode="numeric" autocomplete="off" placeholder="{{ __('mobile.barcode_placeholder') }}" data-rn-pos-barcode>
        <button type="button" class="rn-m-btn rn-m-btn--primary" data-rn-pos-barcode-go>{{ __('mobile.scan_barcode') }}</button>
    </div>

    <div class="rn-m-chips" role="group" aria-label="{{ __('sales.product_category') }}">
        <button type="button" class="rn-m-chip is-active" data-rn-pos-category="" aria-pressed="true">{{ __('mobile.all_categories') }}</button>
        @foreach($pos['categories'] as $category)
            <button type="button" class="rn-m-chip" data-rn-pos-category="{{ $category['id'] }}" aria-pressed="false">{{ $category['name'] }}</button>
        @endforeach
    </div>

    <ul class="rn-m-products" role="list" data-rn-pos-products>
        @foreach($pos['cards'] as $card)
            <li data-rn-pos-item data-category="{{ $card['category'] }}" data-search="{{ \Illuminate\Support\Str::lower($card['name'].' '.$card['barcode']) }}">
                <button type="button" class="rn-m-product{{ $card['stock'] <= 0 ? ' is-out' : '' }}" data-product-id="{{ $card['id'] }}" @disabled($card['stock'] <= 0)>
                    <span class="rn-m-product-name">{{ $card['name'] }}</span>
                    <span class="rn-m-product-price">{{ $card['price'] !== null ? $money($card['price']) : '—' }}</span>
                    <span class="rn-m-product-stock{{ $card['stock'] <= 0 ? ' is-out' : '' }}">{{ $card['stock'] > 0 ? __('mobile.in_stock', ['count' => \App\Support\LocalizedNumber::count($card['stock'])]) : __('mobile.out_of_stock') }}</span>
                </button>
            </li>
        @endforeach
    </ul>
    <p class="rn-m-empty" data-rn-pos-none @if($pos['cards'] !== []) hidden @endif>{{ __('mobile.no_products') }}</p>
    <p class="rn-m-toast" role="status" aria-live="polite" data-rn-pos-toast hidden></p>

    {{-- The whole bar is one button: "N items · total · Take payment →"; disabled (light) while the cart is empty. --}}
    <div class="rn-m-pos-bar" data-rn-pos-bar>
        <button type="button" class="rn-m-pos-bar-btn" data-rn-pos-checkout aria-haspopup="dialog" aria-controls="rnCheckout" disabled>
            <span class="rn-m-pos-bar-count" data-rn-pos-count>{{ __('mobile.items_count', ['count' => \App\Support\LocalizedNumber::digits(0)]) }}</span>
            <span class="rn-m-pos-bar-dot" aria-hidden="true">·</span>
            <strong class="rn-m-pos-bar-total" data-rn-pos-total>{{ $money(0) }}</strong>
            <span class="rn-m-pos-bar-dot" aria-hidden="true">·</span>
            <span class="rn-m-pos-bar-action">{{ __('mobile.take_payment') }}<i class="ri-arrow-right-line" aria-hidden="true"></i></span>
        </button>
    </div>

    <div id="rnCheckout" class="rn-m-sheet" role="dialog" aria-modal="true" aria-labelledby="rnCheckoutTitle" data-rn-checkout hidden>
        <header class="rn-m-header rn-m-sheet-header">
            <button type="button" class="rn-m-header-btn" data-rn-checkout-close aria-label="{{ __('mobile.close') }}"><i class="ri-close-line" aria-hidden="true"></i></button>
            <h2 id="rnCheckoutTitle" class="rn-m-title">{{ __('mobile.take_payment') }}</h2>
        </header>
        <div class="rn-m-sheet-body">
            <div class="rn-m-field">
                <label for="rnCheckoutCustomer">{{ __('mobile.customer') }}</label>
                <select id="rnCheckoutCustomer" class="rn-m-input" data-rn-checkout-customer></select>
            </div>

            <h3 class="rn-m-section-title">{{ __('mobile.items') }}</h3>
            <ul class="rn-m-lines" role="list" data-rn-checkout-items></ul>

            <dl class="rn-m-sums">
                <div><dt>{{ __('mobile.subtotal') }}</dt><dd data-rn-sum="subtotal">{{ $money(0) }}</dd></div>
                <div class="rn-m-sums-edit"><dt><label for="rnCheckoutDiscount">{{ __('mobile.discount') }}</label></dt><dd><input id="rnCheckoutDiscount" type="number" min="0" step="0.01" inputmode="decimal" class="rn-m-input rn-m-input--amount" data-rn-checkout-discount></dd></div>
                <div class="rn-m-sums-grand"><dt>{{ __('mobile.grand_total') }}</dt><dd data-rn-sum="grand">{{ $money(0) }}</dd></div>
            </dl>

            <fieldset class="rn-m-fieldset">
                <legend class="rn-m-section-title">{{ __('sales.payment_method') }}</legend>
                <div class="rn-m-segment rn-m-segment--4">
                    @foreach(\App\Models\SaleProduct::PAYMENT_METHODS as $method)
                        <label class="rn-m-segment-btn"><input type="radio" class="sr-only" name="rnPaymentMethod" value="{{ $method }}" @checked($method === 'cash') data-rn-checkout-method>{{ __('sales.payment_method_'.$method) }}</label>
                    @endforeach
                </div>
            </fieldset>

            <div class="rn-m-field" data-rn-checkout-given-field>
                <label for="rnCheckoutGiven">{{ __('mobile.customer_gave') }}</label>
                <input id="rnCheckoutGiven" type="number" min="0" step="0.01" inputmode="decimal" class="rn-m-input rn-m-input--amount" data-rn-checkout-given>
                <div class="rn-m-quick" role="group" aria-label="{{ __('mobile.customer_gave') }}">
                    <button type="button" class="rn-m-chip" data-rn-quick="exact">{{ __('mobile.exact_amount') }}</button>
                    @foreach([100, 500, 1000] as $note)
                        <button type="button" class="rn-m-chip" data-rn-quick="{{ $note }}">{{ $money($note) }}</button>
                    @endforeach
                </div>
            </div>
            <p class="rn-m-result" data-rn-checkout-result aria-live="polite"></p>
        </div>
        <footer class="rn-m-sheet-footer">
            <button type="button" class="rn-m-btn rn-m-btn--primary rn-m-btn--block" data-rn-checkout-complete>{{ __('mobile.complete_sale') }}</button>
        </footer>
    </div>
</section>
