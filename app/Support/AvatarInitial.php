<?php

namespace App\Support;

final class AvatarInitial
{
    public static function fromName(?string $name): ?string
    {
        $count = preg_match_all('/[\p{L}\p{M}\p{N}]+/u', (string) $name, $words);
        if ($count === false || $count === 0) {
            return null;
        }

        foreach ($words[0] ?? [] as $word) {
            if (!preg_match('/^\p{L}/u', $word)) {
                continue;
            }
            if (!preg_match('/^\p{L}\p{M}*/u', $word, $match)) {
                continue;
            }

            return preg_match('/\A\p{Script=Latin}/u', $match[0]) ? mb_strtoupper($match[0], 'UTF-8') : $match[0];
        }

        return null;
    }

    public static function toneIndex(?string $name): int
    {
        $hash = 2166136261;
        foreach (unpack('C*', (string) $name) ?: [] as $byte) {
            $hash = (($hash ^ $byte) * 16777619) & 0xffffffff;
        }

        return $hash % 8;
    }

    public static function toneClass(?string $name): string
    {
        return 'rn-m-avatar--tone-'.self::toneIndex($name);
    }
}
