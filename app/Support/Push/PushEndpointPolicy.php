<?php

namespace App\Support\Push;

class PushEndpointPolicy
{
    public function allows(string $endpoint): bool
    {
        $parts = parse_url($endpoint);
        if (! is_array($parts) || ($parts['scheme'] ?? null) !== 'https' || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || isset($parts['port']) && $parts['port'] !== 443) {
            return false;
        }

        $host = strtolower(rtrim((string) $parts['host'], '.'));

        return in_array($host, ['fcm.googleapis.com', 'web.push.apple.com'], true)
            || str_ends_with($host, '.push.services.mozilla.com')
            || str_ends_with($host, '.notify.windows.com');
    }

    public function decodeKey(string $value, int $expectedBytes): ?string
    {
        if (preg_match('/^[A-Za-z0-9_-]+$/D', $value) !== 1) {
            return null;
        }

        $decoded = base64_decode(strtr($value.str_repeat('=', (4 - strlen($value) % 4) % 4), '-_', '+/'), true);

        return is_string($decoded) && strlen($decoded) === $expectedBytes ? $decoded : null;
    }
}
