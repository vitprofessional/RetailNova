


<!doctype html>
<html lang="en">
  
<!-- Mirrored from templates.iqonic.design/posdash/html/backend/auth-sign-up.html by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 12 Mar 2025 08:08:13 GMT -->
<head>
    <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <title>{{ $config->count() > 0 ? ($config[0]->businessName ?? 'Retail Nova') : 'Retail Nova' }} | Store Setup</title>
      
      <!-- Favicon -->
      <link rel="shortcut icon" href="{{asset('/public/eshop/')}}/assets/images/favicon.ico" />
      <link rel="stylesheet" href="{{asset('/public/eshop/')}}/assets/css/backend-plugin.min.css">
      <link rel="stylesheet" href="{{asset('/public/eshop/')}}/assets/css/backende209.css?v=1.0.0">
      <link rel="stylesheet" href="{{asset('/public/eshop/')}}/assets/vendor/%40fortawesome/fontawesome-free/css/all.min.css">
      <link rel="stylesheet" href="{{asset('/public/eshop/')}}/assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
      <link rel="stylesheet" href="{{asset('/public/eshop/')}}/assets/vendor/remixicon/fonts/remixicon.css">  </head>
  <body class=" ">
    <!-- loader Start -->
    <div id="loading">
          <div id="loading-center">
          </div>
    </div>
    <!-- loader END -->
    
   <div class="wrapper">
      <section class="login-content">
         <div class="container">
            <div class="row align-items-center justify-content-center height-self-center">
               <div class="col-lg-7">
                  <div class="card auth-card">
                     <div class="card-body p-0">
                        <div class="d-flex align-items-center auth-content">
                           <div class="col-lg-7 align-self-center">
                              <div class="p-3">
                                <div class="row">
                                    <div class="col-7">
                                 <h6 class="mb-2">Setup Your Business</h6>
                                    </div>
                                    <div class="col-5 mb-2 text-right">
                                        <a href="{{ route('userLogin') }}" class="btn btn-warning btn-sm">Skip For Now</a>
                                    </div>
                                </div>

                                @if(session('success'))
                                    <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-3" role="alert">
                                        {{ session('success') }}
                                        <button type="button" class="close py-2" data-dismiss="alert">&times;</button>
                                    </div>
                                @endif
                                @if(session('error'))
                                    <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3" role="alert">
                                        {{ session('error') }}
                                        <button type="button" class="close py-2" data-dismiss="alert">&times;</button>
                                    </div>
                                @endif

                                 <form action="{{ route('saveStoreSetup') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                       <div class="col-lg-12">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="text" id="businessName" name="businessName" value="{{ old('businessName') }}" placeholder=" " required>
                                             <label>Business Name <span class="text-danger">*</span></label>
                                          </div>
                                       </div>
                                       <div class="col-lg-12">
                                          <div class="floating-label form-group">
                                             <select class="form-control" id="businessType" name="businessType">
                                                <option value="">— Select Business Type —</option>
                                                <option value="mobile_shop"           {{ old('businessType') == 'mobile_shop'            ? 'selected' : '' }}>Mobile Shop</option>
                                                <option value="vehicle_shop"          {{ old('businessType') == 'vehicle_shop'           ? 'selected' : '' }}>Vehicle / Auto Parts Shop</option>
                                                <option value="computer_shop"         {{ old('businessType') == 'computer_shop'          ? 'selected' : '' }}>Computer Shop</option>
                                                <option value="electronics_parts_shop"{{ old('businessType') == 'electronics_parts_shop' ? 'selected' : '' }}>Electronics Parts Shop</option>
                                                <option value="garments_shop"         {{ old('businessType') == 'garments_shop'          ? 'selected' : '' }}>Garments Shop</option>
                                                <option value="pharmacy_shop"         {{ old('businessType') == 'pharmacy_shop'          ? 'selected' : '' }}>Pharmacy Shop</option>
                                             </select>
                                             <label>Business Type</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-12">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="text" id="mobile" name="mobile" value="{{ old('mobile') }}" placeholder=" ">
                                             <label>Contact Number</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-12">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="email" id="mail" name="mail" value="{{ old('mail') }}" placeholder=" ">
                                             <label>Email</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-12">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="text" id="businessLocation" name="businessLocation" value="{{ old('businessLocation') }}" placeholder=" ">
                                             <label>Address</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-12">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="url" id="website" name="website" value="{{ old('website') }}" placeholder=" ">
                                             <label>Website</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-12">
                                          <div class="custom-control custom-checkbox mb-2" id="demoCbWrapper">
                                             <input type="checkbox" class="custom-control-input" id="seedDemoData" name="seedDemoData" value="1" checked>
                                             <label class="custom-control-label" for="seedDemoData">
                                                Load demo data for selected business type
                                             </label>
                                          </div>
                                          <p class="text-muted small mb-3" id="demoHint">
                                             Demo products, customers, purchases &amp; sales will be pre-loaded so you can explore the system right away.
                                          </p>
                                       </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Save &amp; Continue</button>
                                 </form>
                              </div>
                           </div>
                           <div class="col-lg-5 content-right">
                              <img src="{{asset('/public/eshop/')}}/assets/images/login/01.png" class="img-fluid image-right" alt="">
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
      </div>
    
    <!-- Backend Bundle JavaScript -->
    <script src="{{asset('/public/eshop/')}}/assets/js/backend-bundle.min.js"></script>
    
    <!-- Table Treeview JavaScript -->
    <script src="{{asset('/public/eshop/')}}/assets/js/table-treeview.js"></script>
    
    <!-- Chart Custom JavaScript -->
    <script src="{{asset('/public/eshop/')}}/assets/js/customizer.js"></script>
    
    <!-- Chart Custom JavaScript -->
    <script async src="{{asset('/public/eshop/')}}/assets/js/chart-custom.js"></script>
    
    <!-- app JavaScript -->
    <script src="{{asset('/public/eshop/')}}/assets/js/app.js"></script>

    <script>
        // Disable demo-data checkbox when no business type is selected
        (function() {
            var typeSelect = document.getElementById('businessType');
            var demoCb    = document.getElementById('seedDemoData');
            var demoHint  = document.getElementById('demoHint');

            function syncCheckbox() {
                var hasType = typeSelect.value !== '';
                demoCb.disabled = !hasType;
                if (!hasType) {
                    demoCb.checked = false;
                    demoHint.textContent = 'Select a business type above to enable demo data loading.';
                } else {
                    demoHint.textContent = 'Demo products, customers, purchases & sales will be pre-loaded so you can explore the system right away.';
                }
            }

            typeSelect.addEventListener('change', syncCheckbox);
            syncCheckbox();
        })();
    </script>
  </body>

<!-- Mirrored from templates.iqonic.design/posdash/html/backend/auth-sign-up.html by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 12 Mar 2025 08:08:13 GMT -->
</html>