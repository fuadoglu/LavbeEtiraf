<?php

namespace App\Support\Push;

class WebPushConfiguration
{
    public function isReady(): bool
    {
        $subject = (string) config('services.webpush.subject');

        return $this->validPublicKey((string) config('services.webpush.public_key'))
            && $this->validPrivateKey((string) config('services.webpush.private_key'))
            && (str_starts_with($subject, 'mailto:') && filter_var(substr($subject, 7), FILTER_VALIDATE_EMAIL)
                || str_starts_with($subject, 'https://') && parse_url($subject, PHP_URL_HOST));
    }

    public function validPublicKey(string $key): bool
    {
        $decoded = $this->decode($key);

        return $decoded !== null && strlen($decoded) === 65;
    }

    public function validPrivateKey(string $key): bool
    {
        $decoded = $this->decode($key);

        return $decoded !== null && strlen($decoded) === 32;
    }

    private function decode(string $value): ?string
    {
        if ($value === '' || preg_match('/^[A-Za-z0-9_-]+$/D', $value) !== 1) {
            return null;
        }

        $decoded = base64_decode(strtr($value.str_repeat('=', (4 - strlen($value) % 4) % 4), '-_', '+/'), true);

        return is_string($decoded) ? $decoded : null;
    }
}
