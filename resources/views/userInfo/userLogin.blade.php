


<!doctype html>
<html lang="en">
  
<!-- Mirrored from templates.iqonic.design/posdash/html/backend/auth-sign-in.html by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 12 Mar 2025 08:08:13 GMT -->
<head>
    <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <title>POS Dash | uesr-login</title>
      
      <!-- Favicon -->
      <link rel="shortcut icon" href="https://templates.iqonic.design/posdash/html/assets/images/favicon.ico" />
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
                     <div class="card-body">
                        <div class="d-flex align-items-center auth-content">
                           <div class="col-lg-7 align-self-center">
                              <div class="row">
                                 <div class="col-12">
                                       @if(session()->has('success'))
                                          <div class="alert alert-success w-100">
                                             {{ session()->get('success') }}
                                          </div>
                                       @endif
                                       @if(session()->has('error'))
                                          <div class="alert alert-danger w-100">
                                             {{ session()->get('error') }}
                                          </div>
                                       @endif
                                 </div>
                              </div>
                              <h2 class="mb-2">Admin Panel</h2>
                              <p>Discover Your World</p>
                              @if($config->count()>0)
                              <form action="{{ route('adminLogin') }}" class="login-form" method="POST">
                                    @csrf
                                 <div class="row">
                                    <div class="col-lg-12">
                                       <div class="floating-label form-group">
                                          <input class="floating-input form-control" type="email" id="userMail" name="userMail" placeholder=" ">
                                          <label>Email</label>
                                       </div>
                                    </div>
                                    <div class="col-lg-12">
                                       <div class="floating-label form-group">
                                          <input class="floating-input form-control" type="password" id="password" name="password" placeholder=" ">
                                          <label>Password</label>
                                       </div>
                                    </div>
                                 </div>
                                 <button type="submit" class="btn btn-primary">Sign In</button>
                              </form>
                              @else
                                 <form action="{{ route('creatAdmin') }}" class="login-form" method="POST">
                                    @csrf
                                    <div class="row">
                                       <div class="col-lg-6">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="text" id="fullName" name="fullName" placeholder=" ">
                                             <label>Full Name</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-6">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="text" id="sureName" name="sureName" placeholder=" ">
                                             <label>Last Name</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-6">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="email" id="mail" name="mail" placeholder=" ">
                                             <label>Email</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-6">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="number" id="contactNumber" name="contactNumber" placeholder=" ">
                                             <label>Phone No.</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-6">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="text" id="storeName" name="storeName" placeholder=" ">
                                             <label>Store Name</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-6">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="password" id="password" name="password" placeholder=" ">
                                             <label>Password</label>
                                          </div>
                                       </div>
                                       <div class="col-lg-6">
                                          <div class="floating-label form-group">
                                             <input class="floating-input form-control" type="password" id="confirmPass" name="confirmPass" placeholder=" ">
                                             <label>Confirm Password</label>
                                          </div>
                                       </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Pos Register</button>
                                 </form>
                              @endif
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
  </body>

<!-- Mirrored from templates.iqonic.design/posdash/html/backend/auth-sign-in.html by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 12 Mar 2025 08:08:13 GMT -->
</html>