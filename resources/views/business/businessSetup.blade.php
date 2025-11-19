@extends('include') 
@section('backTitle') addCoustomer @endsection @section('container')

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
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="header-title">
                    <h4 class="card-title">Business Settings</h4>
                </div>
            </div>
            <div class="card-body">
                @if(!empty($businessLogo))
                <div class="row">
                    <div class="col-xl-3 col-lg-6 col-12 form-group mg-t-30">
                        <!-- jodi thake photo -->
                        <img class="w-75" src="{{ asset('/public/uploads/business/') }}/{{ $businessLogo }}" alt="{{ $businessName }}"><br>
                        <a href="{{ route('delBusinessLogo',['id'=>$businessId]) }}" class="btn btn-danger btn-sm mt-4">Remove</a>
                    </div>
                </div>
                @else
                <form action="{{ route('saveBusinessLogo') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" value="{{ $businessId }}" name="businessId">
                    <div class="row">
                        <div class="col-xl-3 col-lg-6 col-12 form-group mg-t-30">
                            <!-- normal photo field -->
                            <label class="text-dark-medium">Choose  Logo (150px X 150px)</label>
                            <input type="file" name="businessLogo" class="form-control-file">
                            <div class="mt-4"><button type="submit" class=" btn btn-success btn-sm">Update</button></div>
                        </div>
                    </div>
                </form>
                @endif
                <form action="{{ route('saveBusiness') }}" method="POST" >
                    @csrf
                    <input type="hidden" value="{{ $businessId }}" name="businessId">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label> Business Name *</label>
                                <input type="text" class="form-control" placeholder="Enter business name" value="{{ $businessName }}"  id="businessName" name="businessName" required />
                            </div>
                            <div class="form-group">
                                <label for="businessLocation" class="form-label">Business Location *</label>
                                <input type="text" class="form-control" placeholder="Enter business location"  value="{{ $businessLocation }}"id="businessLocation" name="businessLocation" required />
                            </div>
                            <div class="form-group">
                                <label for="mobile" class="form-label"> Mobile*</label>
                                <input type="text" class="form-control" placeholder="Enter Phone Number" value="{{ $businessMobile }}" id="mobile" name="mobile" required />
                            </div>
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" class="form-control" placeholder="Enter email address" value="{{ $businessEmail }}" id="mail" name="mail" />
                            </div>
                            <div class="form-group">
                                <label for="tin" class="form-label">TIN Number *</label>
                                <input type="number" class="form-control" placeholder="Enter TIN Number" value="{{ $businessTin }}" id="tin" name="tinCert" />
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Website *</label>
                                <input type="text" class="form-control" placeholder="Enter Website" value="{{ $businessWebsite }}" id="website" name="website" />
                            </div>
                            <div class="form-group">
                                <label> Facebook Page *</label>
                                <input type="text" class="form-control" placeholder="Enter your facebook page link" value="{{ $facebookPage }}"  id="fbPage" name="fbPage" />
                            </div>
                            <div class="form-group">
                                <label for="youtubeChannel" class="form-label"> Youtube Chanel*</label>
                                <input type="text" class="form-control" placeholder="Enter shop youtube chanel link" value="{{ $youtubeChanel }}" id="youtubeChannel" name="youtubeChannel" />
                            </div>
                            <div class="form-group">
                                <label> Twitter *</label>
                                <input type="text" class="form-control" placeholder="Enter your twitter url" value="{{ $twitterUrl }}"  id="twitter" name="twitter" />
                            </div>
                            <div class="form-group">
                                <label for="linkedin" class="form-label"> Linkedin*</label>
                                <input type="text" class="form-control" placeholder="Enter your linkedin profile url" value="{{ $linkedin }}" id="linkedin" name="linkedin" required />
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="invoiceFooter" class="form-label"> Invoice Footer Note</label>
                                <textarea class="form-control" placeholder="Enter invoice footer note here" id="invoiceFooter" name="invoiceFooter" >{{ $invoiceFooter }}</textarea>
                            </div>
                            <div class="form-group">
                                <label for="currencySymbol" class="form-label">Currency Symbol *</label>
                                <input type="text" class="form-control" placeholder="e.g. $, ৳, €" value="{{ $business->currencySymbol ?? '' }}" id="currencySymbol" name="currencySymbol" />
                            </div>
                            <div class="form-group">
                                <label for="currencyPosition" class="form-label">Currency Position *</label>
                                <select class="form-control" id="currencyPosition" name="currencyPosition">
                                    @php $pos = $business->currencyPosition ?? 'left'; @endphp
                                    <option value="left" {{ $pos==='left'?'selected':'' }}>Left (e.g. $100)</option>
                                    <option value="right" {{ $pos==='right'?'selected':'' }}>Right (e.g. 100$)</option>
                                </select>
                            </div>
                            <div class="form-group form-check">
                                @php $negP = $business->currencyNegParentheses ?? true; @endphp
                                <input type="checkbox" class="form-check-input" id="currencyNegParentheses" name="currencyNegParentheses" value="1" {{ $negP ? 'checked' : '' }}>
                                <label class="form-check-label" for="currencyNegParentheses">Use parentheses for negative (e.g. (100))</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mr-2"> Update</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Page end  -->
@endsection
