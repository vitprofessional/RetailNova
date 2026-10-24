<?php

namespace App\Support;

/**
 * Decides per request whether the mobile or the desktop version of a page is sent.
 *
 * The device (User-Agent) decides by default. The rn_view cookie ("mobile" or "desktop")
 * holds the user's own choice, set by the "Desktop version / Mobile version" switch.
 * Views use @mobile … @endmobile and @desktop … @enddesktop, so each version's HTML is
 * only ever sent to its own audience.
 */
class MobileView
{
    public const COOKIE = 'rn_view';
    public const MODES = ['mobile', 'desktop'];

    /** Phones and tablets; iPadOS that reports itself as a Mac counts as desktop. */
    private const DEVICE = '/Mobi|Android|iPhone|iPad|iPod|Windows Phone|IEMobile|BlackBerry|BB10|Opera Mini|Silk|Kindle|webOS/i';

    public function isMobile(): bool
    {
        return match ($this->choice()) {
            'mobile' => true,
            'desktop' => false,
            default => $this->isMobileDevice(),
        };
    }

    public function isMobileDevice(): bool
    {
        return (bool) preg_match(self::DEVICE, (string) request()->userAgent());
    }

    /** The version the user picked, or null to follow the device. */
    public function choice(): ?string
    {
        $value = request()->cookie(self::COOKIE);

        return in_array($value, self::MODES, true) ? $value : null;
    }
}
