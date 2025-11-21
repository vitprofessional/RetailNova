<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProductUnit;

class ProductUnitPolicy
{
    public function viewAny(?User $user): bool { return true; }
    public function view(?User $user, ProductUnit $unit): bool { return true; }
    public function create(?User $user): bool { return true; }
    public function update(?User $user, ProductUnit $unit): bool { return true; }
    public function delete(?User $user, ProductUnit $unit): bool { return $user && in_array($user->role,['admin','superadmin']); }
    public function forceDelete(?User $user, ProductUnit $unit): bool { return $user && $user->role==='superadmin'; }
}
