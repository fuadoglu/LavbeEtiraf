<?php

namespace App\Support\Moderation;

final class LocalMfaBypass
{
    public static function enabled(): bool
    {
        return app()->environment('local') && (bool) config('moderation.local_skip_mfa');
    }
}
