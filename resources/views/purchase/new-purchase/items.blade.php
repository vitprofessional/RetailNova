<section class="rn-m-form-section rn-purchase-items-section" aria-labelledby="purchaseItemsTitle"><div class="card-body rn-m-form-section-body"><div class="rn-purchase-section-heading"><h2 class="rn-m-form-section-title rn-purchase-icon-heading rn-purchase-items-title" id="purchaseItemsTitle"><span class="rn-purchase-section-icon" aria-hidden="true"><i class="las la-list-alt"></i></span>{{ __('purchases.purchased_items') }}</h2><span data-serial-label="{{ __('purchases.selected_serials') }}" id="purchaseItemCount" class="rn-purchase-count" aria-live="polite">0</span></div>
                <div id="purchaseEmptyState" class="rn-purchase-empty"><i class="las la-box-open rn-purchase-empty-icon" aria-hidden="true"></i><h3>{{ __('purchases.purchase_empty_title') }}</h3><p>{{ __('purchases.purchase_empty_help') }}</p></div>
<div class="mb-3 table-responsive product-table" id="purchaseItemsScroll" tabindex="0" aria-label="{{ __('purchases.purchased_items') }}" hidden>
                    <table class="table mb-0 table-bordered rounded-0 rn-table-pro">
                                <thead class="bg-white text-uppercase">
                            <tr>
                                <th>{{ __('purchases.product_name') }}</th>
                                <th>{{ __('purchases.serial') }}</th>
                                <th>{{ __('purchases.purchase_qty') }}</th>
                                <th>{{ __('purchases.current_stock') }}</th>
                                <th>{{ __('common.buy_price') }} ({{ $currencySymbol }})</th>
                                <th>{{ __('common.sale_price_ex_vat') }} ({{ $currencySymbol }})</th>
                                <th>{{ __('purchases.vat_include') }}</th>
                                <th>{{ __('common.sale_price_inc_vat') }} ({{ $currencySymbol }})</th>
                                <th>{{ __('purchases.profit') }}</th>
                                <th>{{ __('common.total_price') }} ({{ $currencySymbol }})</th>
                                <th>{{ __('common.remove') }}</th>
                            </tr>
                        </thead>
                        <tbody id="productDetails">
                            <!-- purchase rows will be appended here -->
                        </tbody>
                    </table>
                    <!-- Row template (used by JS) -->
                    <template id="purchase-row-template">
                        <tr class="product-row" data-idx="__IDX__">
                            <td width="20%" data-label="{{ __('purchases.product_name') }}">
                                <input type="hidden" name="productName[__IDX__]" value="__PRODUCT_ID__" />
                                <input type="text" class="form-control" name="selectProductName[__IDX__]" value="__PRODUCT_NAME__" readonly />
                            </td>
                            <td width="8%" data-label="{{ __('purchases.serial') }}">
                                <button type="button" class="btn btn-sm btn-outline-secondary open-serials" data-idx="__IDX__">{{ __('purchases.serials') }}</button>
                            </td>
                            <td width="9%" data-label="{{ __('purchases.purchase_qty') }}">
                                <input type="number" class="form-control quantity" id="quantity__IDX__" name="quantity[__IDX__]" min="1" step="1" placeholder="{{ __('purchases.qty') }}" />
                            </td>
                            <td width="9%" data-label="{{ __('purchases.current_stock') }}">
                                <input type="number" class="form-control current-stock" id="currentStock__IDX__" name="currentStock[__IDX__]" readonly />
                            </td>
                            <td width="9%" data-label="{{ __('common.buy_price') }} ({{ $currencySymbol }})">
                                <input type="number" class="form-control" id="buyPrice__IDX__" name="buyPrice[__IDX__]" placeholder="{{ __('common.buy_price') }}" />
                            </td>
                            <td width="9%" data-label="{{ __('common.sale_price_ex_vat') }} ({{ $currencySymbol }})">
                                <input type="number" class="form-control sale-price" id="salePriceExVat__IDX__" name="salePriceExVat[__IDX__]" placeholder="{{ __('common.sale_price_ex_vat') }}" />
                            </td>
                            <td width="9%" data-label="{{ __('purchases.vat_include') }}">
                                    <div class="d-flex align-items-center">
                                    <div class="form-check mr-2">
                                        <input type="checkbox" class="form-check-input include-vat" id="includeVat__IDX__" name="vatStatus[__IDX__]" value="1" />
                                        <label class="form-check-label" for="includeVat__IDX__">{{ __('purchases.yes') }}</label>
                                    </div>
                                    <span class="vat-badge" id="vatBadge__IDX__" style="display:none" title="">{{ __('purchases.vat') }}</span>
                                    <!-- Numeric VAT percent per-row. A small readonly text below shows the computed VAT amount for this row. -->
                                    <div style="display:flex; flex-direction:column;">
                                        <input type="number" name="vatPercent[__IDX__]" class="form-control vat-percent" step="0.01" style="margin-top:0; width:100px;" disabled placeholder="%" />
                                        <div class="small text-muted row-vat-amount" id="rowVat__IDX__"></div>
                                    </div>
                                </div>
                            </td>
                            <td width="9%" data-label="{{ __('common.sale_price_inc_vat') }} ({{ $currencySymbol }})">
                                <input type="number" class="form-control sale-price-inc" id="salePriceInVat__IDX__" name="salePriceInVat[__IDX__]" readonly placeholder="{{ __('common.inc_vat') }}" />
                            </td>
                            <td width="9%" data-label="{{ __('purchases.profit') }}">
                                <input type="number" class="form-control" id="profitMargin__IDX__" name="profitMargin[__IDX__]" readonly placeholder="%" />
                            </td>
                            <td width="9%" data-label="{{ __('common.total_price') }} ({{ $currencySymbol }})">
                                <input type="number" class="form-control" id="totalAmount__IDX__" name="totalAmount[__IDX__]" readonly placeholder="{{ __('purchases.total') }}" />
                            </td>
                            <td width="4%" data-label="{{ __('common.remove') }}">
                                <button type="button" class="btn btn-danger btn-sm remove-row rn-m-purchase-remove" aria-label="{{ __('purchases.remove') }}" title="{{ __('purchases.remove') }}"><i class="ri-delete-bin-line" aria-hidden="true"></i></button>
                            </td>
                        </tr>
                    </template></div></div></section>
