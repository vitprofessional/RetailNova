<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductUnit;
use App\Policies\ProductPolicy;
use App\Policies\BrandPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ProductUnitPolicy;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Map policies explicitly (Laravel 11 minimal config style)
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Brand::class, BrandPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(ProductUnit::class, ProductUnitPolicy::class);
        
        Gate::define('viewAudits', function ($user) {
            if (!$user) {
                return false;
            }
            return in_array($user->role, ['admin','superadmin']);
        });

        Gate::define('manageSuperAdmin', function($user){
            if(!$user) return false;
            return $user->role === 'superadmin';
        });
    }
}
