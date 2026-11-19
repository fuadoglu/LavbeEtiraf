<?php

namespace App\Support\Lavbe;

use RuntimeException;

class LocalePackageMessages
{
    /** @return array<string, mixed> */
    public function group(string $segment, string $group): array
    {
        $locale = app(LocaleRegistry::class)->bySegment($segment);
        abort_unless($locale, 404);

        $path = resource_path('lavbe/locales/'.$locale['segment'].'.json');
        $content = @file_get_contents($path);
        $package = is_string($content) ? json_decode($content, true) : null;
        if (! is_array($package) || ($package['locale'] ?? null) !== $locale['code'] || ! is_array($package['messages'][$group] ?? null)) {
            throw new RuntimeException('The requested locale message package is unavailable.');
        }

        return $package['messages'][$group];
    }

    public function text(string $segment, string $group, string $key): string
    {
        $value = $this->group($segment, $group)[$key] ?? null;
        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException('The requested locale message is unavailable.');
        }

        return $value;
    }
}
