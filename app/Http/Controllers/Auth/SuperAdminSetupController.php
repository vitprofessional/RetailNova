<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use App\Services\FirstAdminBootstrap;
use App\Services\BootstrapException;
use App\Services\LocaleResolver;
use Alert;

/** The single public first-run entry: creates the first business and its Super Admin together. */
class SuperAdminSetupController extends Controller
{
    public function show()
    {
        // The route middleware and this check both close setup after first provisioning.
        if (!FirstAdminBootstrap::firstRunOpen()) {
            return redirect()->route('userLogin');
        }
        $this->assertSetupAllowedInEnvironment();

        return view('auth.superadmin_setup', ['requiresSetupToken' => $this->setupToken() !== null]);
    }

    public function store(Request $request)
    {
        // Gate before validation so unauthenticated probes learn nothing (e.g. which emails exist).
        $this->assertSetupAllowedInEnvironment();
        $this->assertSetupToken($request);

        $data = $request->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'sureName' => ['nullable', 'string', 'max:255'],
            'storeName' => ['nullable', 'string', 'max:255'],
            'contactNumber' => ['nullable', 'string', 'max:50'],
            'mail' => ['required', 'email', 'max:255', 'unique:admin_users,mail'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
            'businessName' => ['required', 'string', 'max:255'],
        ]);

        try {
            $user = app(FirstAdminBootstrap::class)->createAdminWithBusiness(
                Arr::only($data, ['fullName', 'sureName', 'storeName', 'mail', 'contactNumber', 'password']),
                $data['businessName'],
                $this->guestLocale($request),
            );
        } catch (BootstrapException $e) {
            if ($e->reason() === BootstrapException::ALREADY_COMPLETED) abort(403, __('authentication.first_run_closed'));
            if ($e->reason() === BootstrapException::IN_PROGRESS) abort(409, __('authentication.first_run_in_progress'));
            report($e);
            abort(503, __('authentication.first_run_unavailable'));
        }

        Auth::guard('admin')->login($user);
        $request->session()->regenerate();
        session()->put(['pos' => $user->id, 'pos_user_id' => $user->id]);
        Alert::success(__('common.notification_success'),__('common.notification_super_admin_created'));
        return redirect()->route('dashboard');
    }

    private function setupToken(): ?string
    {
        $token = config('setup.token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    /** Production fails closed (503: server misconfiguration) until an operator sets SETUP_TOKEN. */
    private function assertSetupAllowedInEnvironment(): void
    {
        abort_if($this->setupToken() === null && app()->environment('production'), 503, __('authentication.setup_token_not_configured'));
    }

    private function assertSetupToken(Request $request): void
    {
        $expected = $this->setupToken();
        if ($expected === null) {
            return;
        }

        $given = $request->input('setup_token');
        abort_unless(is_string($given) && hash_equals($expected, $given), 403, __('authentication.setup_token_invalid'));
    }

    /** The language the guest chose on the sign-in page becomes the business default. */
    private function guestLocale(Request $request): string
    {
        $locale = $request->session()->get('locale');

        return in_array($locale, LocaleResolver::SUPPORTED, true) ? $locale : 'en';
    }
}
