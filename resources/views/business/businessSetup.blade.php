@extends('include')
@section('backTitle') Business Setup @endsection
@section('container')

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
@endphp
@endif

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-gradient py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 text-dark"><i class="las la-building mr-2" style="font-size: 24px;"></i>Business Settings</h5>
            </div>
            <div class="card-body">
                <!-- Logo Section -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-4 col-sm-12">
                        <div class="card border-0 bg-light">
                            <div class="card-body text-center py-4">
                                @if(!empty($businessLogo))
                                <img class="img-fluid rounded" src="{{ asset('/public/uploads/business/') }}/{{ $businessLogo }}" alt="{{ $businessName }}" style="max-width: 150px; max-height: 150px;">
                                <p class="text-muted small mt-3">{{ $businessName }} Logo</p>
                                <a href="{{ route('delBusinessLogo',['id'=>$businessId]) }}" class="btn btn-danger btn-sm mt-2">
                                    <i class="las la-trash mr-1"></i>Remove Logo
                                </a>
                                @else
                                <div class="text-muted mb-3">
                                    <i class="las la-image" style="font-size: 60px; color: #ddd;"></i>
                                </div>
                                <p class="text-muted">No logo uploaded</p>
                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#logoModal">
                                    <i class="las la-upload mr-1"></i>Upload Logo
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
                                            <i class="las la-store mr-2"></i>Business Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="businessName" name="businessName" placeholder="Enter business name" value="{{ $businessName }}" required />
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="businessLocation" class="form-label font-weight-600">
                                            <i class="las la-map-marker mr-2"></i>Business Location
                                        </label>
                                        <input type="text" class="form-control" id="businessLocation" name="businessLocation" placeholder="Enter business location" value="{{ $businessLocation }}" />
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="mobile" class="form-label font-weight-600">
                                            <i class="las la-phone mr-2"></i>Mobile Number
                                        </label>
                                        <input type="tel" class="form-control" id="mobile" name="mobile" placeholder="Enter phone number" value="{{ $businessMobile }}" />
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="mail" class="form-label font-weight-600">
                                            <i class="las la-envelope mr-2"></i>Email Address <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" class="form-control" id="mail" name="mail" placeholder="Enter email address" value="{{ $businessEmail }}" />
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div class="col-md-6 mb-4">
                                    <div class="form-group mb-4">
                                        <label for="tinCert" class="form-label font-weight-600">
                                            <i class="las la-certificate mr-2"></i>TIN Number <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="tinCert" name="tinCert" placeholder="Enter TIN number" value="{{ $businessTin }}" />
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="website" class="form-label font-weight-600">
                                            <i class="las la-globe mr-2"></i>Website <span class="text-danger">*</span>
                                        </label>
                                        <input type="url" class="form-control" id="website" name="website" placeholder="https://example.com" value="{{ $businessWebsite }}" />
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="currencySymbol" class="form-label font-weight-600">
                                            <i class="las la-dollar-sign mr-2"></i>Currency Symbol <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="currencySymbol" name="currencySymbol" placeholder="e.g. $, ৳, €" value="{{ $business->currencySymbol ?? '' }}" />
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="currencyPosition" class="form-label font-weight-600">
                                            <i class="las la-align-left mr-2"></i>Currency Position <span class="text-danger">*</span>
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
                                    <h6 class="font-weight-700 mb-3"><i class="las la-receipt mr-2"></i>Walk-in Invoice Options</h6>
                                    @php
                                        $hideAckWalkin = config('pos.hide_ack_walkin', true);
                                        $hideSigWalkin = config('pos.hide_signatures_walkin', true);
                                    @endphp
                                    <div class="form-group form-check mb-3 p-3 bg-light rounded">
                                        <input type="checkbox" class="form-check-input" id="hideAckWalkin" name="hideAckWalkin" value="1" {{ $hideAckWalkin ? 'checked' : '' }}>
                                        <label class="form-check-label" for="hideAckWalkin">
                                            <span class="font-weight-600">Hide Acknowledgement on Walk-in Invoices</span>
                                            <small class="d-block text-muted">When enabled, the acknowledgement section is hidden for invoices with customer name "Walking Customer".</small>
                                        </label>
                                    </div>

                                    <div class="form-group form-check mb-4 p-3 bg-light rounded">
                                        <input type="checkbox" class="form-check-input" id="hideSignaturesWalkin" name="hideSignaturesWalkin" value="1" {{ $hideSigWalkin ? 'checked' : '' }}>
                                        <label class="form-check-label" for="hideSignaturesWalkin">
                                            <span class="font-weight-600">Hide Signature Boxes on Walk-in Invoices</span>
                                            <small class="d-block text-muted">When enabled, signature areas are hidden for invoices with customer name "Walking Customer".</small>
                                        </label>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="invoiceFooter" class="form-label font-weight-600">
                                            <i class="las la-file-invoice mr-2"></i>Invoice Footer Note
                                        </label>
                                        <textarea class="form-control" id="invoiceFooter" name="invoiceFooter" placeholder="Enter invoice footer note here" rows="3">{{ $invoiceFooter }}</textarea>
                                    </div>

                                    <div class="form-group form-check mb-4 p-3 bg-light rounded">
                                        @php $showTerms = $business->invoice_terms_enabled ?? true; @endphp
                                        <input type="checkbox" class="form-check-input" id="invoiceTermsEnabled" name="invoiceTermsEnabled" value="1" {{ $showTerms ? 'checked' : '' }}>
                                        <label class="form-check-label" for="invoiceTermsEnabled">
                                            <span class="font-weight-600">Show Terms & Conditions on Invoice</span>
                                            <small class="d-block text-muted">Toggle visibility of the Terms & Conditions section on printed/viewed invoices.</small>
                                        </label>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="invoiceTermsText" class="form-label font-weight-600">
                                            <i class="las la-file-contract mr-2"></i>Invoice Terms & Conditions Text
                                        </label>
                                        <textarea class="form-control" id="invoiceTermsText" name="invoiceTermsText" placeholder="Enter terms and conditions shown on invoice" rows="4">{{ $business->invoice_terms_text ?? "• Thanks for doing business with us.\n• Warranty doesn't cover any physical damage, burn, water damage to the product or warranty sticker removed.\n• Payment is due within 15 days from the date of invoice.\n• Goods once sold cannot be returned or exchanged." }}</textarea>
                                        <small class="text-muted d-block mt-1">Use line breaks to separate bullet points. This text appears when the toggle above is enabled.</small>
                                    </div>

                                    <!-- Live Preview -->
                                    <div class="form-group mb-4">
                                        <label class="form-label font-weight-600">
                                            <i class="las la-eye mr-2"></i>Invoice Terms Preview
                                        </label>
                                        @php
                                            $defaultTerms = "• Thanks for doing business with us.\n• Warranty doesn't cover any physical damage, burn, water damage to the product or warranty sticker removed.\n• Payment is due within 15 days from the date of invoice.\n• Goods once sold cannot be returned or exchanged.";
                                            $initialPreview = nl2br(e($business->invoice_terms_text ?? $defaultTerms));
                                        @endphp
                                        <div id="invoiceTermsPreview" class="p-3 border rounded bg-white" style="white-space:normal; min-height: 80px;">{!! $initialPreview !!}</div>
                                        <small class="text-muted d-block mt-1">Preview reflects the text above. Visibility on invoice depends on the checkbox setting.</small>
                                    </div>

                                    <div class="form-group form-check mb-4 p-3 bg-light rounded">
                                        @php $negP = $business->currencyNegParentheses ?? true; @endphp
                                        <input type="checkbox" class="form-check-input" id="currencyNegParentheses" name="currencyNegParentheses" value="1" {{ $negP ? 'checked' : '' }}>
                                        <label class="form-check-label" for="currencyNegParentheses">
                                            <span class="font-weight-600">Use parentheses for negative amounts</span>
                                            <small class="d-block text-muted">(e.g. (100) instead of -100)</small>
                                        </label>
                                    </div>
                                </div>

                                <!-- Social Media Section -->
                                <div class="col-12">
                                    <hr class="my-4">
                                    <h6 class="font-weight-700 mb-3"><i class="las la-share-alt mr-2"></i>Social Media Links</h6>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <div class="form-group mb-4">
                                        <label for="fbPage" class="form-label font-weight-600">
                                            <i class="lab la-facebook-f mr-2"></i>Facebook Page
                                        </label>
                                        <input type="url" class="form-control" id="fbPage" name="fbPage" placeholder="https://facebook.com/yourpage" value="{{ $facebookPage }}" />
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="twitter" class="form-label font-weight-600">
                                            <i class="lab la-twitter mr-2"></i>Twitter URL
                                        </label>
                                        <input type="url" class="form-control" id="twitter" name="twitter" placeholder="https://twitter.com/yourprofile" value="{{ $twitterUrl }}" />
                                    </div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <div class="form-group mb-4">
                                        <label for="youtubeChannel" class="form-label font-weight-600">
                                            <i class="lab la-youtube mr-2"></i>YouTube Channel
                                        </label>
                                        <input type="url" class="form-control" id="youtubeChannel" name="youtubeChannel" placeholder="https://youtube.com/yourchannel" value="{{ $youtubeChanel }}" />
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="linkedin" class="form-label font-weight-600">
                                            <i class="lab la-linkedin-in mr-2"></i>LinkedIn Profile
                                        </label>
                                        <input type="url" class="form-control" id="linkedin" name="linkedin" placeholder="https://linkedin.com/in/yourprofile" value="{{ $linkedin }}" />
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="las la-save mr-2"></i>Save Changes
                                    </button>
                                    <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-lg ml-2">
                                        <i class="las la-times mr-2"></i>Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Logo Modal -->
<div class="modal fade" id="logoModal" tabindex="-1" role="dialog" aria-labelledby="logoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="logoModalLabel">
                    <i class="las la-image mr-2"></i>Upload Business Logo
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('saveBusinessLogo') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" value="{{ $businessId ?? '' }}" name="businessId">
                <div class="modal-body">
                    @if(empty($businessId))
                    <div class="alert alert-warning">
                        <i class="las la-exclamation-triangle mr-2"></i>Please save business details first before uploading a logo.
                    </div>
                    @endif
                    <div class="form-group">
                        <label for="businessLogo" class="form-label font-weight-600">Choose Logo File</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="businessLogo" name="businessLogo" accept="image/*" {{ empty($businessId) ? 'disabled' : 'required' }}>
                            <label class="custom-file-label" for="businessLogo">Choose file...</label>
                        </div>
                        <small class="form-text text-muted d-block mt-2">
                            <i class="las la-info-circle"></i>Recommended size: 150px × 150px
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success" {{ empty($businessId) ? 'disabled' : '' }}>
                        <i class="las la-upload mr-2"></i>Upload Logo
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
    function esc(s){
        return s.replace(/[&<>"']/g, function(c){
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'})[c];
        });
    }
    function update(){ if(!ta || !prev) return; prev.innerHTML = esc(ta.value).replace(/\n/g,'<br>'); }
    if(ta && prev){ ta.addEventListener('input', update); }
})();
</script>
    </div>
</div>
<!-- Page end  -->
@endsection

