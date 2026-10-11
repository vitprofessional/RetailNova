<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetup;
use App\Services\LocaleResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LanguageSettingsController extends Controller
{
    private function businesses()
    {
        $actor = auth('admin')->user();
        abort_unless($actor && in_array(strtolower(trim($actor->role)), ['admin', 'superadmin'], true), 403);
        $query = BusinessSetup::query();
        if (!$actor->hasSuperAdminPrivileges()) { $query->whereKey((int) $actor->businessId); }
        return $query;
    }

    public function edit(Request $request)
    {
        $businesses = $this->businesses()->orderBy('id')->get();
        $id = $request->query('businessId', auth('admin')->user()->businessId);
        $business = $id ? $this->businesses()->findOrFail($id) : $businesses->first();
        return view('business.languageSettings', compact('business', 'businesses'));
    }

    public function update(Request $request)
    {
        $query = $this->businesses();
        $data = $request->validate(['businessId' => ['required', 'integer'],
            'default_locale' => ['required', Rule::in(LocaleResolver::SUPPORTED)]]);
        $business = $query->findOrFail($data['businessId']);
        $business->default_locale = $data['default_locale'];
        $business->save();
        app()->setLocale(app(LocaleResolver::class)->resolve(auth('admin')->user()));
        return back()->with('status', __('settings.language_saved'));
    }

    public function preference(Request $request)
    {
        $data = $request->validate(['preferred_locale' => ['nullable', Rule::in(LocaleResolver::SUPPORTED)]]);
        $user = auth('admin')->user();
        $user->preferred_locale = $data['preferred_locale'] ?? null;
        $user->save();
        $request->session()->put('locale', app(LocaleResolver::class)->resolve($user));
        return back();
    }
}
