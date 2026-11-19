<?php

namespace App\Support\Lavbe;

final class RoutePatterns
{
    public const UUID_V4 = '[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}';

    public const REPORT_TARGET_TYPES = ['confession', 'comment'];

    public const REPORT_CHANNELS = ['community', 'legal'];
}
