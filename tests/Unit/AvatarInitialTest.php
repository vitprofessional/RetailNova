<?php

namespace Tests\Unit;

use App\Support\AvatarInitial;
use PHPUnit\Framework\TestCase;

class AvatarInitialTest extends TestCase
{
    public function test_it_uses_the_first_letter_led_word_and_preserves_bengali_conjunct_starter(): void
    {
        self::assertSame('D', AvatarInitial::fromName('12V 65Ah Dry Cell Battery'));
        self::assertSame('R', AvatarInitial::fromName('10KΩ Resistor'));
        self::assertSame('স্', AvatarInitial::fromName('স্ক্রু ড্রাইভার'));
        self::assertNull(AvatarInitial::fromName('123'));
    }

    public function test_names_map_to_one_of_eight_avatar_tones(): void
    {
        self::assertContains(AvatarInitial::toneIndex('Retail'), range(0, 7));
        self::assertSame(AvatarInitial::toneClass('Retail'), AvatarInitial::toneClass('Retail'));
    }
}
