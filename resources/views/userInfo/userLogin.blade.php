


<!doctype html>
<html lang="en">
  
<!-- Mirrored from templates.iqonic.design/posdash/html/backend/auth-sign-in.html by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 12 Mar 2025 08:08:13 GMT -->
<head>
    <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <title>@if($config->count()>0){{ $config[0]->businessName ?? 'Retail Nova' }}@else Retail Nova @endif | User Login</title>
      
      <!-- Favicon -->
      <link rel="shortcut icon" href="{{asset('/public/eshop/')}}/assets/images/favicon.ico" />
      <link rel="stylesheet" href="{{asset('/public/eshop/')}}/assets/css/backend-plugin.min.css">
      <link rel="stylesheet" href="{{asset('/public/eshop/')}}/assets/css/backende209.css?v=1.0.0">
      <link rel="stylesheet" href="{{asset('/public/eshop/')}}/assets/vendor/%40fortawesome/fontawesome-free/css/all.min.css">
      <link rel="stylesheet" href="{{asset('/public/eshop/')}}/assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
      <link rel="stylesheet" href="{{asset('/public/eshop/')}}/assets/vendor/remixicon/fonts/remixicon.css">  
   <head>
      <style>
         .bg-login {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%) !important;
         }
         .login-content {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
         }
         .auth-card {
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(60,72,120,0.15);
            padding: 2rem 2.5rem;
         }
         .auth-content h2 {
            font-weight: 700;
            color: #3b82f6;
         }
         .auth-content p {
            color: #64748b;
            font-size: 1.1rem;
         }
         .floating-label {
            position: relative;
         }
         .floating-label label {
            position: absolute;
            left: 12px;
            top: 10px;
            color: #94a3b8;
            font-size: 0.95rem;
            pointer-events: none;
            transition: 0.2s;
         }
         .floating-input:focus + label,
         .floating-input:not(:placeholder-shown) + label {
            top: -12px;
            left: 8px;
            font-size: 0.8rem;
            color: #3b82f6;
         }
         .form-control {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding-left: 2.5rem;
         }
         .input-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
         }
         .btn-primary {
            background: #3b82f6;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.6rem 2rem;
         }
         .image-right {
            max-width: 120px;
         }
         /* ── Mobile responsiveness ── */
         @media (max-width: 576px) {
            .auth-card {
               padding: 1.25rem 1rem;
               border-radius: 10px;
            }
            .auth-content h2 {
               font-size: 1.35rem;
            }
            .auth-content p {
               font-size: 0.9rem;
            }
            .login-content {
               padding: 1rem 0.5rem;
               align-items: flex-start;
               padding-top: 2rem;
            }
            .form-control {
               font-size: 0.95rem;
               padding: 0.5rem 0.5rem 0.5rem 2.2rem;
            }
            .btn-primary {
               font-size: 1rem;
               padding: 0.6rem 1rem;
            }
            .shop-info-card {
               min-width: 0 !important;
               width: 100%;
            }
         }
         @media (max-width: 768px) {
            .auth-card {
               padding: 1.5rem 1.25rem;
            }
         }
      </style>
   </head>
  <body class="bg-login">
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
               <div class="@if($config->count()>0) col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5 @else col-12 col-sm-10 col-md-8 col-lg-7 @endif">
                  <div class="card auth-card shadow">
                     <div class="card-body">
                        <div class="d-flex align-items-center auth-content">
                           <div class="col-12 align-self-center">
                              <div class="row">
                                 <div class="col-12 text-center mb-3">
                                          @php
                                             $shop = $business[0] ?? null;
                                             $displayShop = $shop ?? ($config[0] ?? null);
                                          @endphp
                                          @if($displayShop)
                                             <div class="mb-3 px-3 py-3 shop-info-card" style="background:#f3f6fb;border-radius:12px;box-shadow:0 2px 8px rgba(60,72,120,0.07);display:block;width:100%;max-width:400px;margin:0 auto;">
                                                   <div class="d-flex flex-column align-items-start">
                                                   <img src="{{ !empty($displayShop->businessLogo) ? asset('/public/uploads/business/' . $displayShop->businessLogo) : asset('/public/logo.png') }}" alt="Logo" style="max-width:70px;max-height:70px;border-radius:8px;margin-bottom:8px;">
                                                      <h4 class="mb-1" style="color:#3b82f6;margin-bottom:0.25rem;text-align:left;width:100%;word-break:break-word;">{{ $displayShop->businessName ?? '' }}, <small style="font-size:12px">{{ $displayShop->businessLocation }}</small></h4>
                                                   <div style="color:#64748b;font-size:1rem;width:100%;">
                                                      <div style="display:flex;flex-wrap:wrap;justify-content:flex-start;gap:12px;">
                                                         @if(!empty($displayShop->mobile))
                                                            <span style="display:flex;align-items:center;gap:6px;"><i class="ri-phone-line" style="font-size:1.15em;"></i> <span>{{ $displayShop->mobile }}</span></span>
                                                         @endif
                                                         @if(!empty($displayShop->email))
                                                            <span style="display:flex;align-items:center;gap:6px;word-break:break-all;"><i class="ri-mail-line" style="font-size:1.15em;flex-shrink:0;"></i> <span>{{ $displayShop->email }}</span></span>
                                                         @endif
                                                      </div>
                                                   </div>
                                                </div>
                                             </div>
                                          @endif
                                 </div>
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
                              <h2 class="mb-2 text-center">Welcome to {{ $config->count() > 0 ? ($config[0]->businessName ?? 'Retail Nova') : 'Retail Nova' }}</h2>
                              <p class="text-center">Sign in to your account to continue</p>
                                 @if($hasAdminUsers || $config->count()>0)
                              <form action="{{ route('adminLogin') }}" class="login-form" method="POST">
                                    @csrf
                                 <div class="row">
                                    <div class="col-lg-12">
                                       <div class="floating-label form-group position-relative">
                                          <span class="input-icon"><i class="ri-mail-line"></i></span>
                                          <input class="form-control" type="email" id="userMail" name="userMail" autocomplete="username" placeholder="Email">
                                       </div>
                                    </div>
                                    <div class="col-lg-12">
                                       <div class="floating-label form-group position-relative">
                                          <span class="input-icon"><i class="ri-lock-line"></i></span>
                                          <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" placeholder="Password">
                                       </div>
                                    </div>
                                 </div>
                                 <button type="submit" class="btn btn-primary w-100 mt-2">Sign In</button>
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
                                    <button type="submit" class="btn btn-primary w-100 mt-2">POS Register</button>
                                 </form>
                              @endif
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