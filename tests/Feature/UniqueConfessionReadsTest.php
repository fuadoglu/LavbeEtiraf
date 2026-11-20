<?php

namespace Tests\Feature;

use App\Models\Confession;
use App\Support\Analytics\UniqueReadCounter;
use App\Support\Lavbe\LocaleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $firstRead = $this->get('/az/confession/'.$confession->public_id)->assertOk();
        $firstRead->assertSee('Unikal oxunuş: 1');
        $cookie = $firstRead->getCookie(UniqueReadCounter::COOKIE_NAME);

        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $readerId = $cookie->getValue();
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $readerId);

        $repeatRead = $this->withCookie(UniqueReadCounter::COOKIE_NAME, $readerId)
            ->get('/az/confession/'.$confession->public_id)->assertOk()->assertSee('Unikal oxunuş: 1');
        $this->assertSame($readerId, $repeatRead->getCookie(UniqueReadCounter::COOKIE_NAME)?->getValue());
        $this->assertSame(1, $confession->fresh()->unique_read_count);

        $otherProfile = bin2hex(random_bytes(32));
        $this->withCookie(UniqueReadCounter::COOKIE_NAME, $otherProfile)
            ->get('/az/confession/'.$confession->public_id)->assertOk()->assertSee('Unikal oxunuş: 2');
        $this->assertSame(2, $confession->fresh()->unique_read_count);

        $storedHash = DB::table('confession_readers')->where('confession_id', $confession->id)->value('reader_hash');
        $this->assertNotSame($readerId, $storedHash);
        $this->assertSame(64, strlen($storedHash));
    }

    public function test_reader_hash_cannot_link_the_same_browser_across_confessions(): void
    {
        $first = $this->publishedConfession();
        $second = $this->publishedConfession();
        $readerId = bin2hex(random_bytes(32));

        $this->withCookie(UniqueReadCounter::COOKIE_NAME, $readerId)
            ->get('/az/confession/'.$first->public_id)->assertOk();
        $this->withCookie(UniqueReadCounter::COOKIE_NAME, $readerId)
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

        $this->get('/az/confession/'.$confession->public_id)->assertOk();
        $this->get('/az/')->assertOk()->assertSee('Unikal oxunuş: 1');
    }

    public function test_unpublished_prefetched_and_known_bot_details_do_not_count_as_reads(): void
    {
        $confession = $this->publishedConfession();
        $confession->forceFill(['status' => 'hidden'])->save();

        $this->get('/az/confession/'.$confession->public_id)->assertNotFound()->assertCookieMissing(UniqueReadCounter::COOKIE_NAME);
        $this->assertSame(0, $confession->fresh()->unique_read_count);

        $visible = $this->publishedConfession();
        $this->withHeader('Purpose', 'prefetch')
            ->get('/az/confession/'.$visible->public_id)->assertOk()->assertCookieMissing(UniqueReadCounter::COOKIE_NAME);
        $this->withHeader('User-Agent', 'SyntheticSearchBot/1.0')
            ->get('/az/confession/'.$visible->public_id)->assertOk()->assertCookieMissing(UniqueReadCounter::COOKIE_NAME);
        $this->assertSame(0, $visible->fresh()->unique_read_count);
        $this->assertDatabaseCount('confession_readers', 0);
    }

    public function test_reader_cookie_is_secure_when_session_cookie_security_is_enabled(): void
    {
        config(['session.secure' => true]);
        $confession = $this->publishedConfession();

        $response = $this->get('/az/confession/'.$confession->public_id)->assertOk();

        $this->assertTrue($response->getCookie(UniqueReadCounter::COOKIE_NAME)?->isSecure());
    }

    public function test_repeat_read_refreshes_retention_without_increasing_unique_count_and_pruner_removes_idle_hashes(): void
    {
        config(['analytics.unique_read_retention_days' => 1]);
        $confession = $this->publishedConfession();
        $first = $this->get('/az/confession/'.$confession->public_id)->assertOk();
        $readerId = $first->getCookie(UniqueReadCounter::COOKIE_NAME)?->getValue();
        $this->assertNotNull($readerId);
        $firstSeen = DB::table('confession_readers')->value('last_seen_at');

        $this->travel(12)->hours();
        $this->withCookie(UniqueReadCounter::COOKIE_NAME, $readerId)->get('/az/confession/'.$confession->public_id)->assertOk();
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
            $this->get('/az/topics/'.$category['key'])->assertOk()
                ->assertSee('<link rel="icon" type="image/svg+xml" href="http://localhost/icons/lucide/'.$category['icon'].'.svg" sizes="any">', false)
                ->assertDontSee('rel="alternate icon"', false);
        }

        $confession = $this->publishedConfession();
        $this->get('/az/confession/'.$confession->public_id)->assertOk()
            ->assertSee('<link rel="icon" type="image/svg+xml" href="http://localhost/icons/lucide/house.svg" sizes="any">', false);

        $this->get('/az/')->assertOk()
            ->assertSee('icons/lavbe-feather.svg', false)
            ->assertSee('favicon.ico', false);
    }

    private function publishedConfession(): Confession
    {
        $publishedAt = Carbon::parse('2026-10-10 12:00:00 UTC');
        $confession = Confession::query()->create([
            'locale' => 'az',
            'category_key' => 'family',
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
            'category_key' => 'family',
            'status' => 'approved',
        ]);
        $confession->forceFill(['public_revision_id' => $revision->id])->save();

        return $confession->fresh();
    }
}
