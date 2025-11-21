<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Brand;

class BrandPolicy
{
    public function viewAny(?User $user): bool { return true; }
    public function view(?User $user, Brand $brand): bool { return true; }
    public function create(?User $user): bool { return true; }
    public function update(?User $user, Brand $brand): bool { return true; }
    public function delete(?User $user, Brand $brand): bool { return $user && in_array($user->role,['admin','superadmin']); }
    public function forceDelete(?User $user, Brand $brand): bool { return $user && $user->role==='superadmin'; }
}
