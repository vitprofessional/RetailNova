@extends('include')
@section('backTitle'){{ __('common.heading_business_setup') }}@endsection
@section('container')
@if(auth('admin')->check() && in_array(strtolower(auth('admin')->user()->role), ['admin','superadmin']))
<a class="btn btn-outline-primary mb-3" href="{{ route('language.settings.edit') }}">{{ __('settings.language') }}</a>
@endif

<div class="col-12">
    @include('sweetalert::alert')
</div>

@if($business)
@php
    $businessId         = $business->id;
    $businessName       = $business->businessName;
    $businessLocation   = $business->businessLocation;
    $businessMobile     = $business->mobile;
    $businessEmail      = $business->email;
    $businessWebsite    = $business->website;
    $businessTin        = $business->tinCert;
    $invoiceFooter      = $business->invoiceFooter;
    $facebookPage       = $business->facebook;
    $twitterUrl         = $business->twitter;
    $youtubeChanel      = $business->youtube;
    $linkedin           = $business->linkedin;
    $businessStatus     = $business->status;
    $businessType       = $business->businessType;
    $businessLogo       = $business->businessLogo;
    $hideInvoiceAcknowledgement = $business->hide_invoice_acknowledgement ?? false;
@endphp
@else
@php
    $businessId         = "";
    $businessName       = "";
    $businessLocation   = "";
    $businessMobile     = "";
    $businessEmail      = "";
    $businessWebsite    = "";
    $businessTin        = "";
    $invoiceFooter      = "";
    $facebookPage       = "";
    $twitterUrl         = "";
    $youtubeChanel      = "";
    $linkedin           = "";
    $businessStatus     = "";
    $businessType       = "";
    $businessLogo       = "";
    $hideInvoiceAcknowledgement = false;
@endphp
@endif

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-gradient py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 text-dark"><i class="las la-building mr-2" style="font-size: 24px;"></i>{{ __('settings.business_settings') }}</h5>
                @if(auth('admin')->user()?->canManageSalesSettings())
                <a class="btn btn-outline-primary btn-sm mt-2" href="{{ route('sales.settings.edit') }}">{{ __('settings.sales_settings') }}</a>
                @endif
            </div>
            <div class="card-body">
                <!-- Logo Section -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-4 col-sm-12">
                        <div class="card border-0 bg-light">
                            <div class="card-body text-center py-4">
                                @if(!empty($businessLogo))
                                <img class="img-fluid rounded" src="{{ asset('uploads/business') }}/{{ $businessLogo }}" alt="{{ $businessName }}" style="max-width: 150px; max-height: 150px;">
                                <p class="text-muted small mt-3">{{ $businessName }} {{ __('common.logo') }}</p>
                                <button type="button" class="btn btn-sm btn-info mt-2" data-toggle="modal" data-target="#logoModal">
                                    <i class="las la-upload mr-1"></i>{{ __('settings.change_logo') }}
                                </button>
                                <a href="{{ route('delBusinessLogo',['id'=>$businessId]) }}" class="btn btn-danger btn-sm mt-2">
                                    <i class="las la-trash mr-1"></i>{{ __('settings.remove_logo') }}
                                </a>
                                @else
                                <div class="text-muted mb-3">
                                    <i class="las la-image" style="font-size: 60px; color: #ddd;"></i>
                                </div>
                                <p class="text-muted">{{ __('settings.no_logo_uploaded') }}</p>
                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#logoModal">
                                    <i class="las la-upload mr-1"></i>{{ __('settings.upload_logo') }}
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Business Details Form -->
                    <div class="col-lg-9 col-md-8 col-sm-12">
                        <form action="{{ route('saveBusiness') }}" method="POST">
                            @csrf
                            <input type="hidden" value="{{ $businessId }}" name="businessId">

                            <div class="row">
                                <!-- Left Column -->
                                <div class="col-md-6 mb-4">
                                    <div class="form-group mb-4">
                                        <label for="businessName" class="form-label font-weight-600">
                                            <i class="las la-store mr-2"></i>{{ __('settings.business_name') }} <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="businessName" name="businessName" placeholder="{{ __('common.enter_business_name') }}" value="{{ $businessName }}" required />
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="businessType" class="form-label font-weight-600">
                                            <i class="las la-layer-group mr-2"></i>{{ __('settings.business_type') }}
                                        </label>
                                        <select class="form-control" id="businessType" name="businessType">
                                            <option value="">{{ __('settings.select_a_business_type') }}</option>
                                            @foreach(($businessTypes ?? []) as $typeValue => $typeLabel)
                                            <option value="{{ $typeValue }}" {{ $businessType === $typeValue ? 'selected' : '' }}>{{ $typeLabel }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block mt-2">{{ __('common.selecting_a_business_type_lets_retailnova_load_the_matching_demo_catalog_customers_purchases_and_sales') }}</small>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="businessLocation" class="form-label font-weight-600">
                                            <i class="las la-map-marker mr-2"></i>{{ __('settings.business_location') }}
                                        </label>
                                        <input type="text" class="form-control" id="businessLocation" name="businessLocation" placeholder="{{ __('common.enter_business_location') }}" value="{{ $businessLocation }}" />
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="mobile" class="form-label font-weight-600">
                                            <i class="las la-phone mr-2"></i>{{ __('settings.mobile_number') }}
                                        </label>
                                        <input type="tel" class="form-control" id="mobile" name="mobile" placeholder="{{ __('common.enter_phone_number') }}" value="{{ $businessMobile }}" />
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="mail" class="form-label font-weight-600">
                                            <i class="las la-envelope mr-2"></i>{{ __('settings.email_address') }} <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" class="form-control" id="mail" name="mail" placeholder="{{ __('common.enter_email_address') }}" value="{{ $businessEmail }}" />
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div class="col-md-6 mb-4">
                                    <div class="form-group mb-4">
                                        <label for="tinCert" class="form-label font-weight-600">
                                            <i class="las la-certificate mr-2"></i>{{ __('settings.tin_number') }} <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="tinCert" name="tinCert" placeholder="{{ __('common.enter_tin_number') }}" value="{{ $businessTin }}" />
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="website" class="form-label font-weight-600">
                                            <i class="las la-globe mr-2"></i>{{ __('settings.website') }} <span class="text-danger">*</span>
                                        </label>
                                        <input type="url" class="form-control" id="website" name="website" placeholder="https://example.com" value="{{ $businessWebsite }}" />
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="currencySymbol" class="form-label font-weight-600">
                                            <i class="las la-dollar-sign mr-2"></i>{{ __('settings.currency_symbol') }} <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="currencySymbol" name="currencySymbol" placeholder="{{ __('common.e_g') }}" value="{{ $business->currencySymbol ?? '' }}" />
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="currencyPosition" class="form-label font-weight-600">
                                            <i class="las la-align-left mr-2"></i>{{ __('settings.currency_position') }} <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" id="currencyPosition" name="currencyPosition">
                                            @php $pos = $business->currencyPosition ?? 'left'; @endphp
                                            <option value="left" {{ $pos==='left'?'selected':'' }}>Left (e.g. $100)</option>
                                            <option value="right" {{ $pos==='right'?'selected':'' }}>Right (e.g. 100$)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Full Width Sections -->
                                <div class="col-12">
                                    <hr class="my-4">
                                    @php $isProductionProject = app()->environment('production'); @endphp

                                    @if(!$isProductionProject)
                                    <h6 class="font-weight-700 mb-3"><i class="las la-database mr-2"></i>{{ __('settings.demo_data_setup') }}</h6>

                                    <div class="form-group form-check mb-4 p-3 bg-light rounded">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            id="seedBusinessDemoData"
                                            name="seedBusinessDemoData"
                                            value="1"
                                            {{ empty($businessType) && !empty($canSeedDemoData) ? 'checked' : '' }}
                                            {{ empty($canSeedDemoData) ? 'disabled' : '' }}
                                        >
                                        <label class="form-check-label" for="seedBusinessDemoData">
                                            <span class="font-weight-600">{{ __('settings.load_demo_data_for_the_selected_business_type') }}</span>
                                            <small class="d-block text-muted">
                                                @if(!empty($canSeedDemoData))
                                                On first setup, RetailNova can seed products, suppliers, customers, purchases, and sales that match the selected business type.
                                                @else
                                                Demo data auto-seeding is disabled because this business already has operational data.
                                                @endif
                                            </small>
                                        </label>
                                    </div>

                                    @endif

                                    <h6 class="font-weight-700 mb-3"><i class="las la-receipt mr-2"></i>{{ __('settings.walk_in_invoice_options') }}</h6>
                                    @php
                                        $hideAckWalkin = config('pos.hide_ack_walkin', true);
                                        $hideSigWalkin = config('pos.hide_signatures_walkin', true);
                                    @endphp
                                    <div class="form-group form-check mb-3 p-3 bg-light rounded">
                                        <input type="checkbox" class="form-check-input" id="hideAckWalkin" name="hideAckWalkin" value="1" {{ $hideAckWalkin ? 'checked' : '' }}>
                                        <label class="form-check-label" for="hideAckWalkin">
                                            <span class="font-weight-600">{{ __('settings.hide_acknowledgement_on_walk_in_invoices') }}</span>
                                            <small class="d-block text-muted">When enabled, the acknowledgement section is hidden for invoices with customer name "Walking Customer".</small>
                                        </label>
                                    </div>

                                    <div class="form-group form-check mb-4 p-3 bg-light rounded">
                                        <input type="checkbox" class="form-check-input" id="hideSignaturesWalkin" name="hideSignaturesWalkin" value="1" {{ $hideSigWalkin ? 'checked' : '' }}>
                                        <label class="form-check-label" for="hideSignaturesWalkin">
                                            <span class="font-weight-600">{{ __('settings.hide_signature_boxes_on_walk_in_invoices') }}</span>
                                            <small class="d-block text-muted">When enabled, signature areas are hidden for invoices with customer name "Walking Customer".</small>
                                        </label>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="invoiceFooter" class="form-label font-weight-600">
                                            <i class="las la-file-invoice mr-2"></i>{{ __('settings.invoice_footer_note') }}
                                        </label>
                                        <textarea class="form-control" id="invoiceFooter" name="invoiceFooter" placeholder="{{ __('common.enter_invoice_footer_note_here') }}" rows="3">{{ $invoiceFooter }}</textarea>
                                    </div>

                                    <div class="form-group form-check mb-4 p-3 bg-light rounded">
                                        <input type="checkbox" class="form-check-input" id="hideInvoiceAcknowledgement" name="hideInvoiceAcknowledgement" value="1" {{ $hideInvoiceAcknowledgement ? 'checked' : '' }}>
                                        <label class="form-check-label" for="hideInvoiceAcknowledgement">
                                            <span class="font-weight-600">{{ __('settings.hide_acknowledgement_on_sale_invoice_print') }}</span>
                                            <small class="d-block text-muted">{{ __('settings.when_enabled_the_acknowledgement_section_is_hidden_for_all_printed_sale_invoices_from_this_business') }}</small>
                                        </label>
                                    </div>

                                    <div class="form-group form-check mb-4 p-3 bg-light rounded">
                                        @php $showTerms = $business->invoice_terms_enabled ?? true; @endphp
                                        <input type="checkbox" class="form-check-input" id="invoiceTermsEnabled" name="invoiceTermsEnabled" value="1" {{ $showTerms ? 'checked' : '' }}>
                                        <label class="form-check-label" for="invoiceTermsEnabled">
                                            <span class="font-weight-600">{{ __('settings.show_terms_conditions_on_invoice') }}</span>
                                            <small class="d-block text-muted">{{ __('settings.toggle_visibility_of_the_terms_conditions_section_on_printed_viewed_invoices') }}</small>
                                        </label>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="invoiceTermsText" class="form-label font-weight-600">
                                            <i class="las la-file-contract mr-2"></i>{{ __('settings.invoice_terms_conditions_text') }}
                                        </label>
                                        <textarea class="form-control" id="invoiceTermsText" name="invoiceTermsText" placeholder="{{ __('common.enter_terms_and_conditions_shown_on_invoice') }}" rows="4">{{ $business->invoice_terms_text ?? __('invoices.default_terms') }}</textarea>
                                        <small class="text-muted d-block mt-1">{{ __('settings.use_line_breaks_to_separate_bullet_points_this_text_appears_when_the_toggle_above_is_enabled') }}</small>
                                    </div>

                                    <!-- Live Preview -->
                                    <div class="form-group mb-4">
                                        <label class="form-label font-weight-600">
                                            <i class="las la-eye mr-2"></i>{{ __('settings.invoice_terms_preview') }}
                                        </label>
                                        @php
                                            $defaultTerms = __('invoices.default_terms');
                                            $initialPreview = nl2br(e($business->invoice_terms_text ?? $defaultTerms));
                                        @endphp
                                        <div id="invoiceTermsPreview" class="p-3 border rounded bg-white" style="white-space:normal; min-height: 80px;">{!! $initialPreview !!}</div>
                                        <small class="text-muted d-block mt-1">{{ __('settings.preview_reflects_the_text_above_visibility_on_invoice_depends_on_the_checkbox_setting') }}</small>
                                    </div>

                                    <div class="form-group form-check mb-4 p-3 bg-light rounded">
                                        @php $negP = $business->currencyNegParentheses ?? true; @endphp
                                        <input type="checkbox" class="form-check-input" id="currencyNegParentheses" name="currencyNegParentheses" value="1" {{ $negP ? 'checked' : '' }}>
                                        <label class="form-check-label" for="currencyNegParentheses">
                                            <span class="font-weight-600">{{ __('settings.use_parentheses_for_negative_amounts') }}</span>
                                            <small class="d-block text-muted">(e.g. (100) instead of -100)</small>
                                        </label>
                                    </div>
                                </div>

                                <!-- Social Media Section -->
                                <div class="col-12">
                                    <hr class="my-4">
                                    <h6 class="font-weight-700 mb-3"><i class="las la-share-alt mr-2"></i>{{ __('settings.social_media_links') }}</h6>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <div class="form-group mb-4">
                                        <label for="fbPage" class="form-label font-weight-600">
                                            <i class="lab la-facebook-f mr-2"></i>{{ __('settings.facebook_page') }}
                                        </label>
                                        <input type="url" class="form-control" id="fbPage" name="fbPage" placeholder="https://facebook.com/yourpage" value="{{ $facebookPage }}" />
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="twitter" class="form-label font-weight-600">
                                            <i class="lab la-twitter mr-2"></i>{{ __('settings.twitter_url') }}
                                        </label>
                                        <input type="url" class="form-control" id="twitter" name="twitter" placeholder="https://twitter.com/yourprofile" value="{{ $twitterUrl }}" />
                                    </div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <div class="form-group mb-4">
                                        <label for="youtubeChannel" class="form-label font-weight-600">
                                            <i class="lab la-youtube mr-2"></i>{{ __('settings.youtube_channel') }}
                                        </label>
                                        <input type="url" class="form-control" id="youtubeChannel" name="youtubeChannel" placeholder="https://youtube.com/yourchannel" value="{{ $youtubeChanel }}" />
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="linkedin" class="form-label font-weight-600">
                                            <i class="lab la-linkedin-in mr-2"></i>{{ __('settings.linkedin_profile') }}
                                        </label>
                                        <input type="url" class="form-control" id="linkedin" name="linkedin" placeholder="https://linkedin.com/in/yourprofile" value="{{ $linkedin }}" />
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="las la-save mr-2"></i>{{ __('settings.save_changes') }}
                                    </button>
                                    <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-lg ml-2">
                                        <i class="las la-times mr-2"></i>{{ __('settings.cancel') }}
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Replace Demo Data Form (outside main form to avoid nesting) ── --}}
        @if(!empty($config) && $config->count() > 0 && !($config[0]->count() > 0 || $config[0]->businessName ? true : false))
        @php
            $business = $business ?? new \App\Models\BusinessSetup();
            $canSeedDemoData = $canSeedDemoData ?? true;
        @endphp
        @endif
        @if(!$canSeedDemoData && !empty($business->businessType))
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark py-2 px-3">
                        <i class="las la-redo-alt mr-1"></i>
                        <strong>{{ __('settings.replace_demo_data') }}</strong>
                    </div>
                    <div class="card-body py-3">
                        <p class="text-muted small mb-3">
                            {{ __('settings.this_will') }} <strong>{{ __('settings.permanently_delete') }}</strong> {{ __('common.all_current_products_customers_product_stocks_purchases_and_sales_for_this_business_then_re_seed_fresh_demo_data_for_the_selected_type') }}
                            <strong class="text-danger">{{ __('settings.this_cannot_be_undone') }}</strong>
                        </p>
                        <form action="{{ route('business.demo.reset') }}" method="POST"
                              onsubmit="return confirm('⚠ WARNING: This will delete ALL products, customers, purchases and sales for this business and replace them with new demo data.\n\nAre you absolutely sure?');">
                            @csrf
                                                        <input type="hidden" name="businessId" value="{{ (int)($business->id ?? 0) }}">
                            <div class="form-row align-items-end">
                                <div class="col-sm-7">
                                    <label class="font-weight-600 small mb-1">{{ __('settings.new_business_type') }}</label>
                                    <select name="newBusinessType" class="form-control form-control-sm" required>
                                        <option value="">— Select —</option>
                                        @foreach($businessTypes as $key => $label)
                                            <option value="{{ $key }}" {{ $business->businessType === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-5 mt-2 mt-sm-0">
                                    <button type="submit" class="btn btn-warning btn-sm w-100">
                                        <i class="las la-sync mr-1"></i> Reset &amp; Reseed
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Upload Logo Modal -->
<div class="modal fade" id="logoModal" tabindex="-1" role="dialog" aria-labelledby="logoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="logoModalLabel">
                    <i class="las la-image mr-2"></i>{{ __('settings.upload_business_logo') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('settings.close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('saveBusinessLogo') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" value="{{ $businessId ?? '' }}" name="businessId">
                <div class="modal-body">
                    @if($errors->has('businessLogo') || $errors->has('businessId'))
                    <div class="alert alert-danger">
                        @error('businessId')
                        <div>{{ $message }}</div>
                        @enderror
                        @error('businessLogo')
                        <div>{{ $message }}</div>
                        @enderror
                    </div>
                    @endif
                    @if(empty($businessId))
                    <div class="alert alert-warning">
                        <i class="las la-exclamation-triangle mr-2"></i>{{ __('settings.please_save_business_details_first_before_uploading_a_logo') }}
                    </div>
                    @endif
                        @if($errors->has('businessLogo') || $errors->has('businessId'))
                        <script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery) {
            jQuery('#logoModal').modal('show');
        }
    });
    </script>
                        @endif
                    <div class="form-group">
                        <label for="businessLogo" class="form-label font-weight-600">{{ __('settings.choose_logo_file') }}</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="businessLogo" name="businessLogo" accept="image/*" {{ empty($businessId) ? 'disabled' : 'required' }}>
                            <label class="custom-file-label" for="businessLogo">{{ __('settings.choose_file') }}</label>
                        </div>
                        <small class="form-text text-muted d-block mt-2">
                            <i class="las la-info-circle"></i>Recommended size: 150px × 150px
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('settings.close') }}</button>
                    <button type="submit" class="btn btn-success" {{ empty($businessId) ? 'disabled' : '' }}>
                        <i class="las la-upload mr-2"></i>{{ __('settings.upload_logo') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-gradient {
        background: linear-gradient(135deg, #4680ff 0%, #36a3ff 100%);
        color: white;
    }
    .card {
        border: none;
        transition: box-shadow 0.3s ease;
    }
    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    }
</style>
<script>
(function(){
    var ta = document.getElementById('invoiceTermsText');
    var prev = document.getElementById('invoiceTermsPreview');
    var businessType = document.getElementById('businessType');
    var seedDemo = document.getElementById('seedBusinessDemoData');
    function esc(s){
        return s.replace(/[&<>"']/g, function(c){
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'})[c];
        });
    }
    function update(){ if(!ta || !prev) return; prev.innerHTML = esc(ta.value).replace(/\n/g,'<br>'); }
    function syncSeedToggle(){
        if(!businessType || !seedDemo || seedDemo.disabled) return;
        if(!businessType.value){
            seedDemo.checked = false;
        }
    }
    if(ta && prev){ ta.addEventListener('input', update); }
    if(businessType && seedDemo){ businessType.addEventListener('change', syncSeedToggle); }
})();
</script>
    </div>
</div>
<!-- Page end  -->
@endsection

