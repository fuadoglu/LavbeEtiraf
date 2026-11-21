<?php

namespace App\Support\Security;

final class IdempotencyKey
{
    public static function generate(): string
    {
        $length = max(1, (int) config('lavbe.limits.idempotency_key'));

        return substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length);
    }
}
