<?php

namespace App\Services;

use App\Models\BusinessSetup;

class LocaleResolver
{
    public const SUPPORTED = ['en', 'bn'];

    public function resolve($user = null, ?string $guestPreference = null): string
    {
        $business = $user && $user->businessId
            ? BusinessSetup::query()->find($user->businessId) : null;
        $candidates = $user
            ? [$user->preferred_locale, $business?->default_locale, config('app.locale'), 'en']
            : [$guestPreference, $business?->default_locale, config('app.locale'), 'en'];

        foreach ($candidates as $locale) {
            if (in_array($locale, self::SUPPORTED, true)) { return $locale; }
        }
        return 'en';
    }
}
