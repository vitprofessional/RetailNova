<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AdminUser;
use App\Models\ProductUnit;

class ProductUnitPolicy
{
    public function viewAny(User|AdminUser|null $user): bool { return true; }
    public function view(User|AdminUser|null $user, ProductUnit $unit): bool { return true; }
    public function create(User|AdminUser|null $user): bool { return true; }
    public function update(User|AdminUser|null $user, ProductUnit $unit): bool { return true; }
    public function delete(User|AdminUser|null $user, ProductUnit $unit): bool { return (bool)$user; }
    public function forceDelete(User|AdminUser|null $user, ProductUnit $unit): bool { return (bool)$user; }
}
