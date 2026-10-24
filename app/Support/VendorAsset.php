<?php

namespace App\Support;

/**
 * Third-party browser libraries are served from public/vendor/ so the app works without
 * internet. A library whose local copy has not been added yet falls back to its CDN.
 */
class VendorAsset
{
    /** URL of public/vendor/<path> (versioned by file time), or null when the file is missing. */
    public static function local(string $path): ?string
    {
        $file = public_path('vendor/'.$path);

        return is_file($file) ? asset('vendor/'.$path).'?v='.filemtime($file) : null;
    }

    public static function url(string $path, string $cdn): string
    {
        return self::local($path) ?? $cdn;
    }
}
