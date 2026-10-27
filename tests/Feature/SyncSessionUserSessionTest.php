<?php

namespace Tests\Feature;

use App\Http\Middleware\SyncSessionUser;
use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SyncSessionUserSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_web_user_is_resolved_without_auth_session_writes(): void
    {
        $legacyUser = User::factory()->create();
        $session = Mockery::mock();
        $session->shouldReceive('get')->once()->with('pos_user_id')->andReturn($legacyUser->id);
        $session->shouldReceive('getId')->once()->andReturn('q9-stable-session');
        $session->shouldNotReceive('put', 'regenerate', 'invalidate', 'forget');

        $webGuard = Mockery::mock();
        $webGuard->shouldReceive('setUser')->once()->with(Mockery::on(
            fn ($user) => $user instanceof User && (int) $user->id === (int) $legacyUser->id
        ));
        Auth::shouldReceive('check')->once()->andReturnFalse();
        Auth::shouldReceive('guard')->once()->with('web')->andReturn($webGuard);
        Session::swap($session);

        (new SyncSessionUser())->handle(Request::create('/'), fn () => new Response('ok'));

        $this->assertSame('q9-stable-session', $session->getId());
    }

    public function test_admin_session_can_fetch_both_sale_ajax_payloads(): void
    {
        BusinessSetup::forceCreate(['id' => 1, 'businessName' => 'Session Test Shop']);
        $admin = AdminUser::forceCreate([
            'fullName' => 'Session Test Admin', 'mail' => 'session-admin@example.test',
            'password' => Hash::make('secret'), 'role' => 'admin', 'businessId' => 1,
        ]);
        $customer = Customer::forceCreate(['businessId' => 1, 'name' => 'Customer', 'mobile' => '01700000000']);

        $this->actingAs($admin, 'admin');
        $this->getJson(route('ajax.customer.products.public', $customer->id))->assertOk();
        $this->getJson(route('ajax.customer.previousDue.public', $customer->id))->assertOk();
    }
}
