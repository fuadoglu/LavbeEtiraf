<?php

namespace App\Support\Http;

final class ResponseHeaders
{
    public const CACHE_PRIVATE_NO_STORE = 'private, no-store, max-age=0';

    public const CACHE_NO_STORE = 'no-store';

    public const CACHE_PUBLIC_REVALIDATE = 'public, no-cache, must-revalidate';

    public const CACHE_PUBLIC_MAX_AGE_PREFIX = 'public, max-age=';

    public const ROBOTS_PRIVATE = 'noindex, nofollow, noarchive';

    public const ROBOTS_NO_INDEX = 'noindex';

    public const ROBOTS_INDEX = 'index, follow';

    public const ROBOTS_NO_ARCHIVE = 'noindex, nofollow';

    public const REFERRER_NO_REFERRER = 'no-referrer';

    public const CONTENT_TYPE_TEXT = 'text/plain; charset=UTF-8';

    public const CONTENT_TYPE_XML = 'application/xml; charset=UTF-8';

    public const CONTENT_TYPE_OPTIONS_NOSNIFF = 'nosniff';
}
