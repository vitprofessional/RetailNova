<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AdminUser;
use App\Models\Product;

class ProductPolicy
{
    public function viewAny(User|AdminUser|null $user): bool { return true; }
    public function view(User|AdminUser|null $user, Product $product): bool { return true; }
    public function create(User|AdminUser|null $user): bool { return true; }
    public function update(User|AdminUser|null $user, Product $product): bool { return true; }
    public function delete(User|AdminUser|null $user, Product $product): bool { return (bool)$user; }
    public function forceDelete(User|AdminUser|null $user, Product $product): bool { return (bool)$user; }
}
