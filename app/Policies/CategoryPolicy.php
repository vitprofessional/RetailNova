<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Category;

class CategoryPolicy
{
    public function viewAny(?User $user): bool { return true; }
    public function view(?User $user, Category $category): bool { return true; }
    public function create(?User $user): bool { return true; }
    public function update(?User $user, Category $category): bool { return true; }
    public function delete(?User $user, Category $category): bool { return $user && in_array($user->role,['admin','superadmin']); }
    public function forceDelete(?User $user, Category $category): bool { return $user && $user->role==='superadmin'; }
}
