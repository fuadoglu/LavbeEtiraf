<?php

namespace App\Support\Lavbe;

use App\Models\Confession;

final class AuthorAlias
{
    private const PREFIX_SEPARATOR = ' #';

    public static function generate(): string
    {
        $length = max(1, (int) config('lavbe.alias_suffix_characters'));
        $suffix = strtoupper(substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length));

        return config('app.name').self::PREFIX_SEPARATOR.$suffix;
    }

    public static function generateAvailable(): string
    {
        for ($attempt = 0; $attempt < 16; $attempt++) {
            $alias = self::generate();
            if (! Confession::query()->where('author_alias', $alias)->exists()) {
                return $alias;
            }
        }

        throw new \RuntimeException('No available confession author alias could be generated.');
    }
}
