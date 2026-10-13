


<!doctype html>
<html lang="{{ app()->getLocale() }}">

<!-- Mirrored from templates.iqonic.design/posdash/html/backend/auth-sign-up.html by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 12 Mar 2025 08:08:13 GMT -->
<head>
    <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <title>{{ $config->count() > 0 ? ($config[0]->businessName ?? 'Retail Nova') : 'Retail Nova' }} | Register</title>

      <!-- Favicon -->
      <link rel="shortcut icon" href="{{asset('eshop')}}/assets/images/favicon.ico" />
      <link rel="stylesheet" href="{{asset('eshop')}}/assets/css/backend-plugin.min.css">
      <link rel="stylesheet" href="{{asset('eshop')}}/assets/css/backende209.css?v=1.0.0">
      <link rel="stylesheet" href="{{asset('eshop')}}/assets/vendor/fortawesome/fontawesome-free/css/all.min.css">
      <link rel="stylesheet" href="{{asset('eshop')}}/assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
      <link rel="stylesheet" href="{{asset('eshop')}}/assets/vendor/remixicon/fonts/remixicon.css">  @include('partials.localization')
</head>
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
                                 <h2 class="mb-2">{{ __('authentication.sign_up') }}</h2>
                                 <p>{{ __('authentication.create_your_posdash_account') }}</p>
                                 <form>
                                    <div class="row">
                                       <div class="col-lg-6">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="text" id="fullName" name="fullName" placeholder=" ">
                                             <label>{{ __('authentication.full_name') }}</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-6">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="text" id="lastName" name="lastName" placeholder=" ">
                                             <label>{{ __('authentication.last_name') }}</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-6">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="email" id="mail" name="mail" placeholder=" ">
                                             <label>{{ __('authentication.email') }}</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-6">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="number" id="mobile" name="mobile" placeholder=" ">
                                             <label>{{ __('authentication.phone_no') }}</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-6">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="number" id="otp" name="otp" placeholder=" ">
                                             <label>{{ __('authentication.otp') }}</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-6">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="password" id="password" name="password" placeholder=" ">
                                             <label>{{ __('authentication.password') }}</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-6">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="password" id="confirmPass" name="confirmPass" placeholder=" ">
                                             <label>{{ __('authentication.confirm_password') }}</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-12">
                                          <div class="custom-control custom-checkbox mb-3">
                                             <input type="checkbox" class="custom-control-input" id="customCheck1">
                                             <label class="custom-control-label" for="customCheck1">{{ __('authentication.i_agree_with_the_terms_of_use') }}</label>
                                          </div>
                                       </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">{{ __('authentication.sign_up') }}</button>
                                    <p class="mt-3">
                                       {{ __('authentication.already_have_an_account') }} <a href="{{route('userLogin')}}" class="text-primary">{{ __('authentication.sign_in') }}</a>
                                    </p>
                                 </form>
                              </div>
                           </div>
                           <div class="col-lg-5 content-right">
                              <img src="{{asset('eshop')}}/assets/images/login/01.png" class="img-fluid image-right" alt="">
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
    <script src="{{asset('eshop')}}/assets/js/backend-bundle.min.js"></script>

    <!-- Table Treeview JavaScript -->
    <script src="{{asset('eshop')}}/assets/js/table-treeview.js"></script>

    <!-- Chart Custom JavaScript -->
    <script src="{{asset('eshop')}}/assets/js/customizer.js"></script>

    <!-- Chart Custom JavaScript -->
    <script async src="{{asset('eshop')}}/assets/js/chart-custom.js"></script>

    <!-- app JavaScript -->
    <script src="{{asset('eshop')}}/assets/js/app.js"></script>
  </body>

<!-- Mirrored from templates.iqonic.design/posdash/html/backend/auth-sign-up.html by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 12 Mar 2025 08:08:13 GMT -->
</html>