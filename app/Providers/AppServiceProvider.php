<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\SaleProduct;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use App\Support\Currency;
use App\Support\MobileView;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ensure global helpers are loaded even if Composer's files autoload is cached
        $helpers = app_path('Support/helpers.php');
        if (file_exists($helpers)) {
            require_once $helpers;
        }

        // exists:/unique: rules on business-owned tables only see the current business.
        $this->app->extend('validation.presence', function ($verifier, $app) {
            return new \App\Validation\TenantPresenceVerifier($app['db']);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // A named limiter keeps first-run attempts in their own bucket; plain throttle:5,1
        // keys by IP only, so it would share a counter with the sign-in throttle.
        RateLimiter::for('first-run-setup', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        //global variable

        
        view()->composer('*',function($view){
            // Avoid querying the database during early bootstrap (migrations/tests)
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('business_setups')) {
                    $business = $this->isInvoiceRequest()
                        ? $this->invoiceRequestBusiness()
                        : BusinessSetup::query()->latest('id')->first();
                    $businessTable = $business ? collect([$business]) : collect();
                } elseif (\Illuminate\Support\Facades\Schema::hasTable('admin_users')) {
                    // Backward compatibility fallback if business_setups is not present
                    $businessTable = AdminUser::all();
                } else {
                    $businessTable = collect();
                }
            } catch (\Exception $e) {
                $businessTable = collect();
            }
            $view->with('config',$businessTable);
        });

        // Blade directive for currency formatting (respects symbol/position/neg-parentheses)
        Blade::directive('money', function($expression){
            $expr = $expression ?: '0';
            return "<?php echo \\App\\Support\\Currency::format($expr); ?>";    
        });
        // Simple decimal currency directive (two decimals) for raw numbers (no symbol)
        Blade::directive('currency', function ($expression) {
            return "<?php echo number_format($expression, 2); ?>";
        });

        // Share a computed page title (route-based) with all views. Views may override
        // by defining `@section('title', 'Custom')` in the template.
        try {
            if (!app()->runningInConsole()) {
                view()->composer('*', function ($view) {
                    $routeName = null;
                    try {
                        $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
                    } catch (\Throwable $e) {
                        $routeName = null;
                    }

                    $titles = config('page-titles', []);
                    $page = null;

                    if ($routeName && !empty($titles['routes'][$routeName])) {
                        $page = $titles['routes'][$routeName];
                    }

                    // patterns: fnmatch against request path
                    if (!$page && !empty($titles['patterns']) && request()) {
                        $path = ltrim(request()->path(), '/');
                        foreach ($titles['patterns'] as $pattern => $t) {
                            if (@fnmatch($pattern, $path)) { $page = $t; break; }
                        }
                    }

                    // fallback: humanize route name or path
                    if (!$page) {
                        if ($routeName) {
                            // Replace dots/underscores/hyphens with spaces and split camelCase
                            $human = preg_replace('/[._-]+/', ' ', $routeName);
                            $human = preg_replace('/([a-z])([A-Z])/', '$1 $2', $human);
                            $human = preg_replace('/\s+/', ' ', trim($human));
                            $page = ucwords(strtolower($human));
                        } else {
                            $p = trim(request() ? request()->path() : '', '/');
                            if ($p === '') {
                                $page = 'Dashboard';
                            } else {
                                $human = preg_replace('/[\/_-]+/', ' ', $p);
                                $human = preg_replace('/([a-z])([A-Z])/', '$1 $2', $human);
                                $human = preg_replace('/\s+/', ' ', trim($human));
                                $page = ucwords(strtolower($human));
                            }
                        }
                    }

                    $brand = config('app.name', 'Retail Nova');
                    try {
                        if (\Illuminate\Support\Facades\Schema::hasTable('business_setups')) {
                            // Invoice pages must use the sale owner's identity. In particular,
                            // never leak the globally latest business into another tenant's invoice.
                            $brandBusiness = $this->isInvoiceRequest()
                                ? $this->invoiceRequestBusiness()
                                : BusinessSetup::query()->latest('id')->first();
                            if (!empty($brandBusiness?->businessName)) {
                                $brand = $brandBusiness->businessName;
                            }
                        }
                    } catch (\Throwable $e) {
                        // keep fallback app name
                    }
                    // Child templates resolve localized headings before rendering the shared layout.
                    $heading = trim(view()->getSections()['backTitle'] ?? '');
                    if ($heading !== '') { $page = $heading; }
                    $view->with('pageTitle', $brand . ' | ' . $page);
                });
            }
        } catch (\Throwable $e) {
            // if anything fails during boot (migrations, tests) do not crash
        }

        // @mobile … @endmobile / @desktop … @enddesktop: the version this request gets (see MobileView).
        Blade::if('mobile', fn () => app(MobileView::class)->isMobile());
        Blade::if('desktop', fn () => !app(MobileView::class)->isMobile());

        // Blade conditional: @canEdit(true) ... @endcanEdit
        Blade::if('canEdit', function ($isSale = false) {
            $actor = auth('admin')->user();
            if (!$actor) return false;
            $role = strtolower($actor->role ?? '');
            if ($role === 'salesmanager') {
                return (bool)$isSale;
            }
            // Other roles can edit
            return true;
        });
    }

    private function isInvoiceRequest(): bool
    {
        try {
            return in_array(\Illuminate\Support\Facades\Route::currentRouteName(), [
                'invoiceGenerate',
                'invoicePage',
                'invoiceThermalPrint',
            ], true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function invoiceRequestBusiness(): ?BusinessSetup
    {
        try {
            $id = request()->route('id') ?? request()->query('id');
            if (!is_numeric($id) || (int) $id < 1) {
                return null;
            }

            return SaleProduct::query()->with('business')->find((int) $id)?->business;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
