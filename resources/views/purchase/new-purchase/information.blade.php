<section class="card rn-m-form-section rn-purchase-information" aria-labelledby="purchaseInformationTitle"><div class="card-body rn-m-form-section-body">
 <h2 class="rn-m-form-section-title rn-purchase-icon-heading" id="purchaseInformationTitle"><span class="rn-purchase-section-icon" aria-hidden="true"><i class="las la-clipboard"></i></span>{{ __('purchases.purchase_information') }}</h2><div class="rn-purchase-information-grid">@include('partials.business_selector', ['businessSelectionAllowed'=>app(\App\Services\PurchaseWorkflow::class)->platform(), 'businesses' => $businesses ?? [] , 'selectedBusinessId' => null])<div class="rn-purchase-field">
                                <div class="form-group">
                                    <label for="date" class="form-label">{{ __('common.date') }} *</label>
                                    <input type="date" class="form-control" id="date" name="purchaseDate" value="{{ old('purchaseDate', date('Y-m-d')) }}" />
                                </div>
                            </div><div class="rn-purchase-field">
                                <div class="form-group">
                                    <label for="invoice" class="form-label">{{ __('common.invoice') }} *</label>
                                    <input type="text" class="form-control" id="invoice" name="invoiceData" value="{{ $generatedInvoice ?? '' }}" readonly placeholder="{{ __('common.auto_generated_invoice') }}" />
                                </div>
                            </div><div class="rn-purchase-field">
                                <div class="form-group">
                                    <label for="supplierName" class="form-label">{{ __('common.supplier') }} *</label>
                                    <div class="rn-purchase-input-action"><select id="supplierName" name="supplierName" data-onchange="actProductList()" class="form-control" required>
                                    <option value="">{{ __('purchases.select_supplier') }}</option>
                                    <!--  form option show proccessing -->
                                    @if(!empty($supplierList) && count($supplierList)>0)
                                    @foreach($supplierList as $supplierData)
                                        <option value="{{$supplierData->id}}">{{$supplierData->name}}</option>
                                        @endforeach
                                        @endif
                                    </select><button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#supplier"><i class="las la-plus mr-2"></i>{{ __('purchases.new_supplier') }}</button></div>
                                </div>
                            </div><div class="rn-purchase-field">
                                <div class="form-group">
                                    <label for="reference" class="form-label">{{ __('common.reference') }}</label>
                                    <input type="text" class="form-control" id="reference" name="refData" value="{{ old('refData') }}" placeholder="{{ __('common.optional') }}" />
                                </div>
                            </div></div></div></section>
