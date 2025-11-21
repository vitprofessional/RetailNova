<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;

class AuditsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_privileged_admin_cannot_view_audits()
    {
        $admin = AdminUser::create([
            'fullName' => 'Staff User',
            'sureName' => 'Staff',
            'storeName' => 'RetailNova',
            'mail' => 'staff@example.test',
            'contactNumber' => '000',
            'password' => Hash::make('secret'),
            'businessId' => 1,
            'role' => 'staff'
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('audits.index'))
            ->assertStatus(403);
    }

    public function test_superadmin_can_view_audits()
    {
        $admin = AdminUser::create([
            'fullName' => 'Super Admin',
            'sureName' => 'Admin',
            'storeName' => 'RetailNova',
            'mail' => 'super@example.test',
            'contactNumber' => '000',
            'password' => Hash::make('secret'),
            'businessId' => 1,
            'role' => 'superadmin'
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('audits.index'))
            ->assertStatus(200);
    }
}
