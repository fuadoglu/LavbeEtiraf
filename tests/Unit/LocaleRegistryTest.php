<?php

namespace Tests\Unit;

use App\Support\Lavbe\LocaleRegistry;
use PHPUnit\Framework\TestCase;

class LocaleRegistryTest extends TestCase
{
    public function test_registry_contains_all_seventeen_languages_and_search_names(): void
    {
        $registry = new LocaleRegistry;

        self::assertCount(17, $registry->all());
        self::assertContains('日本語', $registry->searchableNames($registry->resolveTag('ja')));
        self::assertContains('pt', $registry->searchableNames($registry->resolveTag('pt-BR')));
    }

    public function test_first_visit_priority_parsing_and_country_fallback(): void
    {
        $registry = new LocaleRegistry;

        self::assertSame('pt-BR', $registry->resolveTag('pt-PT')['code']);
        self::assertSame('nb', $registry->resolveTag('no')['code']);
        self::assertSame('ja', $registry->fromAcceptLanguage(['fr;q=0.5, ja ; q = 0.9, en;q=0'])['code']);
        self::assertSame('az', $registry->fromCountry('AZ')['code']);
        self::assertSame('fr', $registry->fromCountry('CH', 'fr-CH,en;q=0.8')['code']);
        self::assertNull($registry->fromCountry('XX', 'ja'));
        self::assertNull($registry->resolveTag('http://evil.test/'));
        self::assertSame('en', $registry->fromAcceptLanguage(['ja;q=bogus,en;q=0.8'])['code']);
    }
}
