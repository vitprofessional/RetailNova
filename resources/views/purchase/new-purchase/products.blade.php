<section class="card rn-m-form-section" aria-labelledby="purchaseAddProductsTitle"><div class="card-body rn-m-form-section-body"><h2 class="rn-m-form-section-title rn-purchase-icon-heading" id="purchaseAddProductsTitle"><span class="rn-purchase-section-icon" aria-hidden="true"><i class="las la-cube"></i></span>{{ __('purchases.add_products') }}</h2>
 <p class="rn-purchase-help" data-purchase-supplier-help data-no-supplier="{{ __('purchases.supplier_first_help') }}" data-supplier-selected="{{ __('purchases.supplier_selected_help') }}" data-product-selected="{{ __('purchases.product_selected_help') }}">{{ __('purchases.supplier_first_help') }}</p><div class="rn-m-purchase-picker"><label class="sr-only" for="purchaseProductSearch">{{ __('mobile.list_search') }}</label><input id="purchaseProductSearch" class="form-control" type="search" placeholder="{{ __('mobile.list_search') }}" autocomplete="off"><div class="rn-m-purchase-product-cards" role="listbox" aria-label="{{ __('common.product') }}"></div><button type="button" class="rn-m-btn rn-m-btn--outline" data-toggle="modal" data-target="#newProduct"><i class="las la-plus mr-2" aria-hidden="true"></i>{{ __('purchases.new_product') }}</button></div><div class="rn-purchase-product-controls"><div class="rn-purchase-field">
                                <div class="form-group">
                                    <label for="productName" class="form-label">{{ __('common.product') }} *</label>
                                    <!-- productAdd is used to pick a product to append as a purchase row. Disabled until supplier selected. -->
                                    <select id="productName" name="productAdd" class="form-control js-product-select">
                                        <option value="">{{ __('purchases.please_select_a_supplier_first') }}</option>
                                    @if(!empty($productList) && count($productList)>0)
                                        @php
                                            // Render product options into a data container so JS can restore them when supplier is chosen
                                            $productOptions = '';
                                        @endphp
                                        @foreach($productList as $productData)
                                            @php
                                                $brand = \App\Models\Brand::find($productData->brand);
                                                $brandName = $brand ? ' - ' . $brand->name : '';
                                                $imageAttr = $productData->image_thumbnail_url ? ' data-image="'.htmlspecialchars($productData->image_thumbnail_url, ENT_QUOTES, 'UTF-8').'"' : '';
                                                $productOptions .= '<option value="'. $productData->id .'"'.$imageAttr.'>'. htmlspecialchars($productData->name . $brandName) .'</option>';
                                            @endphp
                                        @endforeach
                                        {!! $productOptions !!}
                                    @endif
                                    </select>
                                </div>
                            </div><div class="rn-purchase-product-actions"><button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#newProduct"><i class="las la-plus mr-2"></i>{{ __('purchases.new_product') }}</button><button type="button" id="addProductRow" class="btn btn-primary btn-sm">{{ __('purchases.add_to_list') }}</button></div></div><p class="rn-purchase-help">{{ __('purchases.quantity_in_items_help') }}</p></div></section>
