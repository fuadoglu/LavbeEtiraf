<?php

namespace App\Support\Lavbe;

use RuntimeException;

class LocaleRegistry
{
    /** @var array<string, mixed>|null */
    private ?array $catalog = null;

    /** @return array<string, mixed> */
    public function catalog(): array
    {
        if ($this->catalog !== null) {
            return $this->catalog;
        }

        $path = dirname(__DIR__, 3).'/spec/catalog.json';
        $contents = @file_get_contents($path);
        $catalog = is_string($contents) ? json_decode($contents, true) : null;

        if (! is_array($catalog) || ! isset($catalog['locales'], $catalog['categories'], $catalog['first_visit'])) {
            throw new RuntimeException('The Lavbe locale catalog is missing or invalid.');
        }

        return $this->catalog = $catalog;
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->catalog()['locales'];
    }

    /** @return array<string, mixed>|null */
    public function bySegment(string $segment): ?array
    {
        foreach ($this->all() as $locale) {
            if (strtolower($locale['segment']) === strtolower($segment)) {
                return $locale;
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    public function resolveTag(string $tag): ?array
    {
        $tag = strtolower(str_replace('_', '-', trim($tag)));
        $aliases = $this->catalog()['first_visit']['language_aliases'];

        if (! preg_match('/^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$/', $tag)) {
            return null;
        }

        if (isset($aliases[$tag])) {
            $tag = strtolower($aliases[$tag]);
        }

        foreach ($this->all() as $locale) {
            if (strtolower($locale['code']) === $tag || strtolower($locale['segment']) === $tag) {
                return $locale;
            }
        }

        $primary = explode('-', $tag, 2)[0];
        if ($primary !== $tag && isset($aliases[$primary])) {
            $primary = strtolower($aliases[$primary]);
        }

        foreach ($this->all() as $locale) {
            if (strtolower($locale['code']) === $primary || strtolower($locale['segment']) === $primary) {
                return $locale;
            }
        }

        return null;
    }

    /** @param list<string> $headerValues
     * @return array<string, mixed>|null
     */
    public function fromAcceptLanguage(array $headerValues): ?array
    {
        $preferences = [];
        $position = 0;

        foreach ($headerValues as $header) {
            if (! is_string($header)) {
                continue;
            }
            foreach (explode(',', $header) as $item) {
                [$tag, $parameters] = array_pad(explode(';', trim($item), 2), 2, '');
                $tag = trim($tag);
                if (! preg_match('/^[A-Za-z]{1,8}(?:-[A-Za-z0-9]{1,8})*$/', $tag) && $tag !== '*') {
                    continue;
                }
                $quality = 1.0;
                $validQuality = true;
                foreach (explode(';', $parameters) as $parameter) {
                    if (trim($parameter) === '') {
                        continue;
                    }
                    if (! preg_match('/^\s*q\s*=\s*(0(?:\.\d{0,3})?|1(?:\.0{0,3})?)\s*$/i', $parameter, $match)) {
                        $validQuality = false;
                        break;
                    }
                    $quality = (float) $match[1];
                }

                if ($validQuality && $quality > 0 && $tag !== '*') {
                    $preferences[] = ['tag' => $tag, 'quality' => $quality, 'position' => $position++];
                }
            }
        }

        usort($preferences, static fn (array $left, array $right): int => ($right['quality'] <=> $left['quality']) ?: ($left['position'] <=> $right['position']));

        foreach ($preferences as $preference) {
            $locale = $this->resolveTag($preference['tag']);
            if ($locale !== null) {
                return $locale;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function fallback(): array
    {
        return $this->resolveTag($this->catalog()['first_visit']['fallback'])
            ?? throw new RuntimeException('The fallback locale is missing from the catalog.');
    }

    /** @return array<string, mixed>|null */
    public function category(string $key): ?array
    {
        foreach ($this->catalog()['categories'] as $category) {
            if ($category['key'] === $key) {
                return $category;
            }
        }

        return null;
    }

    public function categoryLabel(string $localeCode, string $key): ?string
    {
        $labels = $this->category($key)['labels'] ?? [];

        return $labels[$localeCode] ?? $labels[$this->fallback()['code']] ?? null;
    }

    /** @return list<array{key:string, label:string}> */
    public function categoryOptions(string $localeCode): array
    {
        $options = [];
        foreach ($this->catalog()['categories'] as $category) {
            $options[] = [
                'key' => $category['key'],
                'label' => $category['labels'][$localeCode] ?? $category['labels'][$this->fallback()['code']],
            ];
        }

        return $options;
    }

    /** @return list<string> */
    public function searchableNames(array $locale): array
    {
        $names = [
            $locale['native'] ?? null,
            $locale['english'] ?? null,
            $locale['code'] ?? null,
            $locale['segment'] ?? null,
            ...($locale['aliases'] ?? []),
        ];

        foreach ($this->catalog()['first_visit']['language_aliases'] as $alias => $target) {
            if (strtolower((string) $target) === strtolower((string) ($locale['code'] ?? ''))) {
                $names[] = $alias;
            }
        }

        return array_values(array_unique(array_filter($names, 'is_string')));
    }

    public function fromCountry(?string $country, ?string $acceptLanguage = null): ?array
    {
        $locale = $this->fromCountryCode($country);
        if ($locale !== null) {
            return $locale;
        }

        $country = strtoupper(trim((string) $country));
        if (in_array($country, $this->catalog()['first_visit']['multilingual_country_fallback'], true)) {
            $preferred = $this->fromAcceptLanguage([$acceptLanguage ?? '']);
            if ($preferred !== null) {
                return $preferred;
            }
        }

        return null;
    }

    private function fromCountryCode(?string $country): ?array
    {
        $country = strtoupper(trim((string) $country));
        $defaults = $this->catalog()['first_visit']['country_defaults'];

        return isset($defaults[$country]) ? $this->resolveTag($defaults[$country]) : null;
    }
}
