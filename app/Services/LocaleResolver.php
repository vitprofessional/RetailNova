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
        foreach ([$user?->preferred_locale, $business?->default_locale,
            $user ? null : $guestPreference, config('app.locale'), 'en'] as $locale) {
            if (in_array($locale, self::SUPPORTED, true)) { return $locale; }
        }
        return 'en';
    }
}
