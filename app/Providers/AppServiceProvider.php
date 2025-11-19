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
            $businessTable = AdminUser::all();
            $view->with('config',$businessTable);
        });

        // Blade directive for currency formatting
        Blade::directive('money', function($expression){
            return "<?php echo \\App\\Support\\Currency::format((int)($expression)); ?>";    
        });
    }
}
