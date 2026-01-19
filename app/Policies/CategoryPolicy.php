<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AdminUser;
use App\Models\Category;

class CategoryPolicy
{
    public function viewAny(User|AdminUser|null $user): bool { return true; }
    public function view(User|AdminUser|null $user, Category $category): bool { return true; }
    public function create(User|AdminUser|null $user): bool { return true; }
    public function update(User|AdminUser|null $user, Category $category): bool { return true; }
    public function delete(User|AdminUser|null $user, Category $category): bool { return $user && in_array($user->role,['admin','superadmin']); }
    public function forceDelete(User|AdminUser|null $user, Category $category): bool { return $user && $user->role==='superadmin'; }
}
