<?php

namespace App\Support\Lavbe;

final class AuthorAlias
{
    private const PREFIX_SEPARATOR = ' #';

    public static function generate(): string
    {
        $length = max(1, (int) config('lavbe.alias_suffix_characters'));
        $suffix = strtoupper(substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length));

        return config('app.name').self::PREFIX_SEPARATOR.$suffix;
    }
}
