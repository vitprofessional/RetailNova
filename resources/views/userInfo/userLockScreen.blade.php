


<!doctype html>
<html lang="{{ app()->getLocale() }}">

<!-- Mirrored from templates.iqonic.design/posdash/html/backend/auth-lock-screen.html by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 12 Mar 2025 08:08:14 GMT -->
<head>
    <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <title>{{ $config->count() > 0 ? ($config[0]->businessName ?? 'Retail Nova') : 'Retail Nova' }} | Lock Screen</title>

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
                                 <img src="{{asset('eshop')}}/assets/images/user/1.png" class="rounded avatar-80 mb-3" alt="">
                                 <h2 class="mb-2">{{ __('common.lock_greeting') }}</h2>
                                 <p>{{ __('authentication.enter_your_password_to_access_the_admin') }}</p>
                                 <form>
                                    <div class="row">
                                       <div class="col-lg-12">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="password" id="password" name="password" placeholder=" ">
                                             <label>{{ __('authentication.password') }}</label>
                                          </div>
                                       </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">{{ __('authentication.login') }}</button>
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

<!-- Mirrored from templates.iqonic.design/posdash/html/backend/auth-lock-screen.html by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 12 Mar 2025 08:08:14 GMT -->
</html>