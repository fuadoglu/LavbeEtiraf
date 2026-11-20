<?php

namespace Tests\Feature;

use App\Models\Confession;
use App\Models\User;
use App\Support\Lavbe\LocaleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Tests\TestCase;

class LocaleCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_runtime_key_and_placeholder_resolves_without_fallback(): void
    {
        $sourceFiles = glob(lang_path('en/*.php'));
        foreach (app(LocaleRegistry::class)->all() as $locale) {
            $code = $locale['code'];
            foreach ($sourceFiles as $sourceFile) {
                $file = basename($sourceFile);
                $group = basename($file, '.php');
                $expected = Arr::dot(require $sourceFile);
                $actual = Arr::dot(require lang_path($code.'/'.$file));
                $this->assertEqualsCanonicalizing(array_keys($expected), array_keys($actual), "$code/$file");
                foreach ($expected as $key => $source) {
                    $value = Lang::get("$group.$key", [], $code, false);
                    $this->assertSame($actual[$key], $value, "$code/$group.$key");
                    $this->assertNotSame('', trim($value));
                    preg_match_all('/:[a-zA-Z][a-zA-Z0-9_]*/', $source, $sourceTokens);
                    preg_match_all('/:[a-zA-Z][a-zA-Z0-9_]*/', $value, $translatedTokens);
                    $this->assertEqualsCanonicalizing($sourceTokens[0], $translatedTokens[0], "$code/$group.$key");
                }
            }
            $mail = json_decode(file_get_contents(lang_path($code.'.json')), true, flags: JSON_THROW_ON_ERROR);
            $base = json_decode(file_get_contents(lang_path('en.json')), true, flags: JSON_THROW_ON_ERROR);
            $this->assertEqualsCanonicalizing(array_keys($base), array_keys($mail));
            foreach ($mail as $key => $value) {
                $this->assertSame($value, Lang::get($key, [], $code, false));
                preg_match_all('/:[a-zA-Z]+/', $key, $tokens);
                preg_match_all('/:[a-zA-Z]+/', $value, $translatedTokens);
                $this->assertEqualsCanonicalizing($tokens[0], $translatedTokens[0]);
            }
        }
    }

    public function test_read_write_login_and_not_found_pages_use_the_url_language(): void
    {
        foreach (app(LocaleRegistry::class)->all() as $locale) {
            $base = '/'.$locale['segment'];
            $code = $locale['code'];
            $this->get($base.'/')->assertOk()
                ->assertSee('<html lang="'.$code.'"', false)
                ->assertSee(Lang::get('navigation.label', [], $code))
                ->assertSee(Lang::get('lavbe.sort_updates', [], $code));
            $story = Confession::factory()->create(['locale' => $code, 'public_id' => (string) Str::uuid()]);
            $this->get($base.'/report/confession/'.$story->public_id)->assertOk()->assertSee(Lang::get('reports.details_hint', [], $code));
            $this->get($base.'/my/'.$story->public_id.'/recover')->assertOk()->assertSee(Lang::get('guest.write_recovery_code', [], $code));
            $this->get($base.'/write')->assertOk()->assertSee(Lang::get('guest.write_body_hint', ['limit' => config('lavbe.limits.confession_body')], $code));
            $this->get(route('account.login', ['locale' => $locale['segment']]))->assertOk()
                ->assertSee(Lang::get('account.login_title', [], $code));
            $this->get($base.'/topics/unknown-topic')->assertNotFound()->assertSee(Lang::get('ui.missing', [], $code));
            app()->setLocale('en');
            $this->get($base.'/missing-route')->assertNotFound()->assertSee(Lang::get('ui.missing', [], $code));
            $user = new User(['preferred_locale' => $locale['segment']]);
            $this->assertSame($code, $user->preferredLocale());
        }
    }
}
