<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\AdminUser;

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
    }
}
