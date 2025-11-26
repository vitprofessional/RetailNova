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
    }
}
