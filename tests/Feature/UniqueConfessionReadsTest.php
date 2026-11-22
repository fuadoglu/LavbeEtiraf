<?php

namespace Tests\Feature;

use App\Models\Confession;
use App\Support\Analytics\UniqueReadCounter;
use App\Support\Lavbe\LocaleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class UniqueConfessionReadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_confession_counts_each_browser_profile_once_and_shows_the_count(): void
    {
        $confession = $this->publishedConfession();

        $this->withCookie((string) config('analytics.reader_consent_cookie_name'), 'accepted');
        $firstRead = $this->get('/az/confession/'.$confession->public_id)->assertOk();
        $firstRead->assertSee('aria-label="Unikal oxunuş: 1"', false)->assertSee('aria-hidden="true">1</span>', false);
        $cookie = $firstRead->getCookie(UniqueReadCounter::COOKIE_NAME);

        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $readerId = $cookie->getValue();
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $readerId);

        $repeatRead = $this->withCookie(UniqueReadCounter::COOKIE_NAME, $readerId)
            ->get('/az/confession/'.$confession->public_id)->assertOk()->assertSee('aria-label="Unikal oxunuş: 1"', false);
        $this->assertSame($readerId, $repeatRead->getCookie(UniqueReadCounter::COOKIE_NAME)?->getValue());
        $this->assertSame(1, $confession->fresh()->unique_read_count);

        $otherProfile = bin2hex(random_bytes(32));
        $this->withCookie(UniqueReadCounter::COOKIE_NAME, $otherProfile)
            ->get('/az/confession/'.$confession->public_id)->assertOk()->assertSee('aria-label="Unikal oxunuş: 2"', false);
        $this->assertSame(2, $confession->fresh()->unique_read_count);

        $storedHash = DB::table('confession_readers')->where('confession_id', $confession->id)->value('reader_hash');
        $this->assertNotSame($readerId, $storedHash);
        $this->assertSame(64, strlen($storedHash));
    }

    public function test_reading_without_consent_does_not_set_a_reader_cookie_or_row(): void
    {
        $confession = $this->publishedConfession();

        $this->get('/az/confession/'.$confession->public_id)->assertOk()
            ->assertCookieMissing(UniqueReadCounter::COOKIE_NAME);

        $this->assertSame(0, $confession->fresh()->unique_read_count);
        $this->assertDatabaseCount('confession_readers', 0);
    }

    public function test_out_of_range_pagination_does_not_count_as_a_read(): void
    {
        $confession = $this->publishedConfession();
        $path = '/az/confession/'.$confession->public_id;
        foreach (['comments_page', 'continuations_page'] as $pageParameter) {
            $this->withCookie((string) config('analytics.reader_consent_cookie_name'), 'accepted')
                ->get($path.'?'.$pageParameter.'=999')->assertNotFound()->assertCookieMissing(UniqueReadCounter::COOKIE_NAME);
        }
        $this->assertSame(0, $confession->fresh()->unique_read_count);
        $this->assertDatabaseCount('confession_readers', 0);
        $this->withCookie((string) config('analytics.reader_consent_cookie_name'), 'accepted')->get($path)->assertOk();
        $this->assertSame(1, $confession->fresh()->unique_read_count);
    }

    public function test_consent_can_be_accepted_and_revoked_without_blocking_public_reads(): void
    {
        $confession = $this->publishedConfession();
        $consentName = (string) config('analytics.reader_consent_cookie_name');
        $path = '/az/confession/'.$confession->public_id;
        $accepted = $this->post('/az/reader-consent', ['choice' => 'accept', 'return_to' => $path])
            ->assertRedirect($path)
            ->assertCookie($consentName, 'accepted');
        $consentCookie = $accepted->getCookie($consentName);
        $this->assertTrue($consentCookie->isHttpOnly());
        $this->assertSame('lax', $consentCookie->getSameSite());
        $this->assertGreaterThanOrEqual(364 * 86400, $consentCookie->getExpiresTime() - time());

        $read = $this->withCookie($consentName, 'accepted')->get($path)->assertOk();
        $readerId = $read->getCookie(UniqueReadCounter::COOKIE_NAME)?->getValue();
        $this->assertNotNull($readerId);
        $this->assertSame(1, $confession->fresh()->unique_read_count);
        $this->assertStringNotContainsString(__('consent.banner_title'), $read->getContent());

        $revoked = $this->withCookie(UniqueReadCounter::COOKIE_NAME, $readerId)
            ->post('/az/reader-consent', ['choice' => 'decline', 'return_to' => $path])
            ->assertRedirect($path)
            ->assertCookie($consentName, 'declined');
        $this->assertLessThan(time(), $revoked->getCookie(UniqueReadCounter::COOKIE_NAME)?->getExpiresTime());

        $deniedRead = $this->withCookie($consentName, 'declined')->get($path)->assertOk();
        $this->assertLessThan(time(), $deniedRead->getCookie(UniqueReadCounter::COOKIE_NAME)?->getExpiresTime());
        $this->assertSame(1, $confession->fresh()->unique_read_count);
        $this->assertDatabaseCount('confession_readers', 1);
    }

    public function test_global_config_can_disable_tracking_even_after_consent(): void
    {
        config(['analytics.unique_read_tracking_enabled' => false]);
        $confession = $this->publishedConfession();
        $disabled = $this->withCookie((string) config('analytics.reader_consent_cookie_name'), 'accepted')
            ->withCookie(UniqueReadCounter::COOKIE_NAME, bin2hex(random_bytes(32)))
            ->get('/az/confession/'.$confession->public_id)->assertOk();
        $this->assertLessThan(time(), $disabled->getCookie(UniqueReadCounter::COOKIE_NAME)?->getExpiresTime());
        $this->assertSame(0, $confession->fresh()->unique_read_count);
        $this->assertDatabaseCount('confession_readers', 0);
    }

    public function test_cookie_notice_page_exposes_changeable_reader_preference(): void
    {
        $this->withCookie((string) config('analytics.reader_consent_cookie_name'), 'declined')
            ->get('/az/legal/cookies')->assertOk()
            ->assertSee(__('consent.status_declined'))
            ->assertSee('name="choice" value="accept"', false)
            ->assertSee('name="choice" value="decline"', false);
    }

    public function test_visibility_change_before_counter_write_does_not_leave_a_reader_row(): void
    {
        $confession = $this->publishedConfession();
        $reader = $confession->fresh();
        $readerId = bin2hex(random_bytes(32));
        DB::table('confessions')->where('id', $confession->id)->update(['status' => 'hidden']);
        $request = Request::create('/az/confession/'.$confession->public_id);
        $request->cookies->set(UniqueReadCounter::COOKIE_NAME, $readerId);
        $request->cookies->set((string) config('analytics.reader_consent_cookie_name'), 'accepted');

        app(UniqueReadCounter::class)->record($request, $reader);

        $this->assertSame(0, $confession->fresh()->unique_read_count);
        $this->assertDatabaseCount('confession_readers', 0);
    }

    public function test_reader_hash_cannot_link_the_same_browser_across_confessions(): void
    {
        $first = $this->publishedConfession();
        $second = $this->publishedConfession();
        $readerId = bin2hex(random_bytes(32));

        $this->withCookie((string) config('analytics.reader_consent_cookie_name'), 'accepted')
            ->withCookie(UniqueReadCounter::COOKIE_NAME, $readerId)
            ->get('/az/confession/'.$first->public_id)->assertOk();
        $this->withCookie((string) config('analytics.reader_consent_cookie_name'), 'accepted')
            ->withCookie(UniqueReadCounter::COOKIE_NAME, $readerId)
            ->get('/az/confession/'.$second->public_id)->assertOk();

        $hashes = DB::table('confession_readers')->orderBy('confession_id')->pluck('reader_hash');
        $this->assertCount(2, $hashes);
        $this->assertNotSame($hashes[0], $hashes[1]);
    }

    public function test_feed_cards_show_each_confessions_unique_read_count(): void
    {
        $confession = $this->publishedConfession();

        $this->get('/az/')->assertOk()
            ->assertDontSee('Unikal oxunuş: 0')
            ->assertDontSee('<footer class="story-card-footer">', false);

        $this->withCookie((string) config('analytics.reader_consent_cookie_name'), 'accepted')
            ->get('/az/confession/'.$confession->public_id)->assertOk();
        $this->get('/az/')->assertOk()->assertSee('aria-label="Unikal oxunuş: 1"', false)->assertSee('aria-hidden="true">1</span>', false);
    }

    public function test_unpublished_prefetched_and_known_bot_details_do_not_count_as_reads(): void
    {
        $confession = $this->publishedConfession();
        $confession->forceFill(['status' => 'hidden'])->save();

        $this->get('/az/confession/'.$confession->public_id)->assertNotFound()->assertCookieMissing(UniqueReadCounter::COOKIE_NAME);
        $this->assertSame(0, $confession->fresh()->unique_read_count);

        $visible = $this->publishedConfession();
        $this->withCookie((string) config('analytics.reader_consent_cookie_name'), 'accepted')->withHeader('Purpose', 'prefetch')
            ->get('/az/confession/'.$visible->public_id)->assertOk()->assertCookieMissing(UniqueReadCounter::COOKIE_NAME);
        $this->withCookie((string) config('analytics.reader_consent_cookie_name'), 'accepted')->withHeader('User-Agent', 'SyntheticSearchBot/1.0')
            ->get('/az/confession/'.$visible->public_id)->assertOk()->assertCookieMissing(UniqueReadCounter::COOKIE_NAME);
        $this->assertSame(0, $visible->fresh()->unique_read_count);
        $this->assertDatabaseCount('confession_readers', 0);
    }

    public function test_reader_cookie_is_secure_when_session_cookie_security_is_enabled(): void
    {
        config(['session.secure' => true]);
        $confession = $this->publishedConfession();

        $response = $this->withCookie((string) config('analytics.reader_consent_cookie_name'), 'accepted')
            ->get('/az/confession/'.$confession->public_id)->assertOk();

        $this->assertTrue($response->getCookie(UniqueReadCounter::COOKIE_NAME)?->isSecure());
    }

    public function test_repeat_read_refreshes_retention_without_increasing_unique_count_and_pruner_removes_idle_hashes(): void
    {
        config(['analytics.unique_read_retention_days' => 1]);
        $confession = $this->publishedConfession();
        $first = $this->withCookie((string) config('analytics.reader_consent_cookie_name'), 'accepted')
            ->get('/az/confession/'.$confession->public_id)->assertOk();
        $readerId = $first->getCookie(UniqueReadCounter::COOKIE_NAME)?->getValue();
        $this->assertNotNull($readerId);
        $firstSeen = DB::table('confession_readers')->value('last_seen_at');

        $this->travel(12)->hours();
        $this->withCookie((string) config('analytics.reader_consent_cookie_name'), 'accepted')
            ->withCookie(UniqueReadCounter::COOKIE_NAME, $readerId)->get('/az/confession/'.$confession->public_id)->assertOk();
        $this->assertSame(1, $confession->fresh()->unique_read_count);
        $refreshedSeen = DB::table('confession_readers')->value('last_seen_at');
        $this->assertNotSame($firstSeen, $refreshedSeen);

        DB::table('confession_readers')->where('confession_id', $confession->id)->update(['last_seen_at' => now()->subDays(2)]);
        $this->artisan('lavbe:prune-confession-readers', ['--batches' => 1])->assertSuccessful();
        $this->assertDatabaseCount('confession_readers', 0);
        $this->assertSame(1, $confession->fresh()->unique_read_count);
    }

    public function test_category_and_confession_pages_use_their_category_favicon(): void
    {
        foreach (app(LocaleRegistry::class)->catalog()['categories'] as $category) {
            $icon = $category['icon'];
            $png = 'icons/category-favicons/'.$icon.'.png';
            $this->assertSame([32, 32, IMAGETYPE_PNG], array_slice(getimagesize(public_path($png)), 0, 3));

            $this->get('/az/topics/'.$category['key'])->assertOk()
                ->assertSee('<link rel="icon" type="image/png" href="http://localhost/'.$png.'" sizes="32x32">', false)
                ->assertSee('<link rel="icon" type="image/svg+xml" href="http://localhost/icons/lucide/'.$icon.'.svg" sizes="any">', false)
                ->assertDontSee('rel="alternate icon"', false);

            $confession = $this->publishedConfession($category['key']);
            $this->get('/az/confession/'.$confession->public_id)->assertOk()
                ->assertSee('<link rel="icon" type="image/png" href="http://localhost/'.$png.'" sizes="32x32">', false)
                ->assertSee('<link rel="icon" type="image/svg+xml" href="http://localhost/icons/lucide/'.$icon.'.svg" sizes="any">', false)
                ->assertDontSee('rel="alternate icon"', false);
        }

        $this->get('/az/')->assertOk()
            ->assertSee('icons/lavbe-feather.svg', false)
            ->assertSee('favicon.ico', false);
    }

    private function publishedConfession(string $category = 'family'): Confession
    {
        $publishedAt = Carbon::parse('2026-10-10 12:00:00 UTC');
        $confession = Confession::query()->create([
            'locale' => 'az',
            'category_key' => $category,
            'status' => 'public',
            'title' => 'Synthetic public title',
            'body' => 'Synthetic public body',
            'excerpt' => 'Synthetic public body',
            'public_id' => (string) Str::uuid(),
            'author_alias' => 'Lavbe #A1B2C3D4',
            'published_at' => $publishedAt,
            'public_updated_at' => $publishedAt,
            'ever_public' => true,
            'visibility_version' => 2,
            'lock_version' => 2,
        ]);
        $revision = $confession->revisions()->create([
            'revision' => 1,
            'title' => 'Synthetic public title',
            'body' => 'Synthetic public body',
            'category_key' => $category,
            'status' => 'approved',
        ]);
        $confession->forceFill(['public_revision_id' => $revision->id])->save();

        return $confession->fresh();
    }
}
