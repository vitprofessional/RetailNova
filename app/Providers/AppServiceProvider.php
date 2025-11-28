<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Blade;
use App\Support\Currency;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //global variable
       
        
        view()->composer('*',function($view){
            // Avoid querying the database during early bootstrap (migrations/tests)
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('admin_users')) {
                    $businessTable = AdminUser::all();
                } else {
                    $businessTable = collect();
                }
            } catch (\Exception $e) {
                $businessTable = collect();
            }
            $view->with('config',$businessTable);
        });

        // Blade directive for currency formatting
        Blade::directive('money', function($expression){
            return "<?php echo \\App\\Support\\Currency::format((int)($expression)); ?>";    
        });
        // Simple decimal currency directive (two decimals) for amounts
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
                    $view->with('pageTitle', $brand . ' | ' . $page);
                });
            }
        } catch (\Throwable $e) {
            // if anything fails during boot (migrations, tests) do not crash
        }
    }
}
