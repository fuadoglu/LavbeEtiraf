<?php

namespace Tests\Feature;

use App\Models\Confession;
use App\Models\ConfessionContinuation;
use App\Support\Lavbe\FeedCursor;
use App\Support\Lavbe\LocaleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UiFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_populated_feed_hides_the_intro_and_uses_compact_topic_and_sort_controls(): void
    {
        Confession::factory()->create(['locale' => 'az', 'title' => 'İlk etiraf başlığı']);

        $html = $this->get('/az/')->assertOk()
            ->assertSee('feed-page', false)
            ->assertSee('feed-topic-picker-mobile', false)
            ->assertSee('Mövzular', false)
            ->assertSee('aria-label="Etirafın mövzusu"', false)
            ->getContent();

        $this->assertStringNotContainsString('Başqalarının yazdıqlarını oxu.', $html);
        $this->assertStringContainsString('line-clamp: 3', file_get_contents(resource_path('css/app.css')));
        $this->assertLessThan(strpos($html, 'class="sort-panel"'), strpos($html, 'feed-heading--populated'));
        $this->assertStringContainsString('İlk etiraf başlığı', $html);
        $this->assertSame(26, substr_count($html, '<a class="topic-chip'));
        $this->assertStringContainsString('class="feed-topic-picker feed-topic-picker-mobile"', $html);
        $this->assertStringContainsString('.feed-topic-picker-mobile:not([open]) > .topic-panel { display: none; }', file_get_contents(resource_path('css/app.css')));
        $this->assertStringContainsString('.feed-controls:has(.feed-topic-picker-mobile[open]) .sort-panel { grid-column: 1 / -1;', file_get_contents(resource_path('css/app.css')));
        $this->assertStringContainsString('class="sort-label-full"', $html);
        $this->assertStringContainsString('class="sort-label-short"', $html);
        $this->assertStringContainsString('.sort-label-full { display: none; } .sort-label-short { display: inline; }', file_get_contents(resource_path('css/app.css')));
        $this->assertStringNotContainsString('Unikal oxunuş: 0', $html);
    }

    public function test_confession_writing_starts_with_the_story_and_keeps_public_name_opt_in(): void
    {
        $html = $this->get('/az/write')->assertOk()
            ->assertSee(__('guest.write_anonymous', [], 'az'))
            ->assertSee('autocomplete="off"', false)
            ->getContent();

        $body = strpos($html, 'id="body"');
        $title = strpos($html, 'id="title"');
        $author = strpos($html, 'id="author_display_name"');
        $options = strpos($html, 'class="sharing-options"');
        $this->assertNotFalse($body);
        $this->assertNotFalse($title);
        $this->assertNotFalse($author);
        $this->assertNotFalse($options);
        $this->assertTrue($body < $title && $title < $options && $options < $author);
        $this->assertStringNotContainsString('autocomplete="name"', $html);
        $this->assertStringContainsString('data-character-count', $html);
        $this->assertStringContainsString('>6000</output>', $html);
        $this->assertStringNotContainsString('<details class="sharing-options"', $html);
        $this->assertStringContainsString('<section class="sharing-options"', $html);
        $this->assertStringContainsString('name="age_confirmed"', $html);
        $this->assertStringContainsString('name="remember_device"', $html);
    }

    public function test_feed_hides_empty_zero_read_footer_and_shows_approved_activity(): void
    {
        $empty = Confession::factory()->create(['locale' => 'az', 'title' => 'Sakit etiraf']);
        $active = Confession::factory()->create(['locale' => 'az', 'title' => 'Davam edən etiraf']);
        $comment = $active->comments()->create([
            'public_id' => (string) Str::uuid(), 'status' => 'public', 'author_alias' => 'Lavbe #AABBCCDD',
            'body' => 'Synthetic approved comment.', 'published_at' => now(),
        ]);
        $revision = $comment->revisions()->create(['revision' => 1, 'body' => 'Synthetic approved comment.', 'status' => 'approved']);
        $comment->forceFill(['public_revision_id' => $revision->id])->save();
        $continuation = ConfessionContinuation::query()->create([
            'confession_id' => $active->id, 'public_id' => (string) Str::uuid(), 'status' => 'public',
            'body' => 'Synthetic continuation.', 'published_at' => now(),
        ]);
        $continuationRevision = $continuation->revisions()->create(['revision' => 1, 'body' => 'Synthetic continuation.', 'status' => 'approved']);
        $continuation->forceFill(['public_revision_id' => $continuationRevision->id])->save();

        $html = $this->get('/az/')->assertOk()->getContent();
        $this->assertStringNotContainsString('Unikal oxunuş: 0', $html);
        $this->assertStringContainsString('1 rəy', $html);
        $this->assertStringContainsString('Müəllif davamını yazıb', $html);
        $this->assertStringNotContainsString('story-card-footer', substr($html, strpos($html, 'Sakit etiraf'), strpos($html, 'Davam edən etiraf') - strpos($html, 'Sakit etiraf')));
    }

    public function test_confession_detail_puts_story_before_read_count_and_localizes_its_date(): void
    {
        $confession = Confession::factory()->create(['locale' => 'az', 'public_id' => (string) Str::uuid()]);
        $continuation = ConfessionContinuation::query()->create([
            'confession_id' => $confession->id,
            'public_id' => (string) Str::uuid(),
            'status' => 'public',
            'body' => 'Approved synthetic author continuation.',
            'published_at' => now(),
        ]);
        $continuationRevision = $continuation->revisions()->create([
            'revision' => 1,
            'body' => 'Approved synthetic author continuation.',
            'status' => 'approved',
        ]);
        $continuation->forceFill(['public_revision_id' => $continuationRevision->id])->save();

        $html = $this->get('/az/confession/'.$confession->public_id)->assertOk()
            ->assertSee($confession->published_at->locale('az')->translatedFormat('j F Y'), false)
            ->assertSee('Approved synthetic author continuation.')
            ->getContent();

        $title = strpos($html, 'id="confession-title"');
        $body = strpos($html, 'class="confession-body"');
        $reads = strpos($html, 'confession-read-count');
        $continuation = strpos($html, 'Approved synthetic author continuation.');
        $actions = strpos($html, 'class="confession-actions');
        $this->assertTrue($title < $body && $body < $reads && $reads < $continuation && $continuation < $actions);
        $this->assertStringNotContainsString('story-meta">Ailə', $html);
    }

    public function test_language_menu_precedes_the_three_header_icons(): void
    {
        foreach (['/az', '/az/topics/family', '/az/search'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, 'class="icon-button header-search"'));
            $this->assertStringNotContainsString('class="button button-secondary feed-search"', $html);
            $language = strpos($html, 'class="language-menu"');
            $search = strpos($html, 'class="icon-button header-search"');
            $supported = strpos($html, 'class="icon-button header-supported"');
            $theme = strpos($html, 'class="icon-button theme-button"');
            $this->assertNotFalse($language);
            $this->assertNotFalse($search);
            $this->assertNotFalse($supported);
            $this->assertNotFalse($theme);
            $this->assertTrue($language < $search && $search < $supported && $supported < $theme);
        }
    }

    public function test_public_form_labels_and_descriptions_reference_existing_controls(): void
    {
        $confession = Confession::factory()->create(['public_id' => (string) Str::uuid()]);
        foreach ([
            '/az/write',
            '/az/account/login',
            '/az/account/register',
            '/az/moderation/login',
            '/az/my/'.$confession->public_id.'/recover',
            '/az/report/confession/'.$confession->public_id,
            '/az/confession/'.$confession->public_id,
        ] as $path) {
            $response = $this->get($path);
            $this->assertSame(200, $response->status(), $path);
            $html = $response->getContent();
            $document = new \DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
            $xpath = new \DOMXPath($document);
            $ids = [];
            foreach ($xpath->query('//*[@id]') as $element) {
                $id = $element->getAttribute('id');
                $this->assertArrayNotHasKey($id, $ids, $path.' duplicates '.$id);
                $ids[$id] = $element->tagName;
            }
            foreach ($xpath->query('//label[@for]') as $label) {
                $target = $label->getAttribute('for');
                $this->assertContains($ids[$target] ?? null, ['input', 'select', 'textarea'], $path.' label '.$target);
            }
            foreach ($xpath->query('//*[@aria-describedby]') as $element) {
                foreach (explode(' ', $element->getAttribute('aria-describedby')) as $id) {
                    $this->assertArrayHasKey($id, $ids, $path.' missing description '.$id);
                }
            }
        }
    }

    public function test_error_and_shared_component_copy_is_localized_in_every_locale(): void
    {
        foreach (app(LocaleRegistry::class)->all() as $locale) {
            $code = $locale['code'];
            $this->get('/'.$locale['segment'].'/topics/does-not-exist')
                ->assertNotFound()
                ->assertSee(__('ui.missing', [], $code))
                ->assertSee(__('ui.back', [], $code))
                ->assertSee('icons/lavbe-logo.svg')
                ->assertSee('/'.$locale['segment'].'/legal/terms')
                ->assertDontSee('Lavbe · '.__('lavbe.feed_title', [], $code));
            $this->view('components.feed-skeleton')
                ->assertSee(__('ui.loading', [], $code))
                ->assertDontSee('ui.loading');
            $this->view('components.dialog', ['id' => 'synthetic-dialog', 'title' => 'Synthetic title', 'slot' => ''])
                ->assertSee(__('ui.close', [], $code))
                ->assertDontSee('ui.close');
        }
    }

    public function test_feed_pages_normalize_first_page_and_reject_invalid_or_empty_pages(): void
    {
        foreach (['/az/', '/az/topics/family'] as $path) {
            $response = $this->get($path.'?page=1&sort=weekly');
            $response->assertStatus(301);
            $location = $response->headers->get('Location');
            $this->assertStringNotContainsString('page=', $location);
            $this->assertStringContainsString('sort=weekly', $location);
            foreach (['0', '-1', 'abc', '1.5', '999999999999999999999999', '2', 'page[]=1'] as $page) {
                $query = str_contains($page, '=') ? $page : 'page='.$page;
                $this->get($path.'?'.$query)->assertNotFound();
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['session.driver' => 'array']);
    }

    public function test_home_page_keeps_the_global_empty_feed_message(): void
    {
        $response = $this->get('/');
        $this->assertSame('en', app()->getLocale());
        $response->assertRedirect('/en/');

        $this->get('/az/')
            ->assertOk()
            ->assertSee('Hələ dərc olunmuş etiraf yoxdur');
    }

    public function test_topic_page_uses_the_shared_feed_layout_and_selected_filter(): void
    {
        $this->get('/az/topics/family')
            ->assertOk()
            ->assertSee('Ailə')
            ->assertSee('aria-current="page"', false)
            ->assertSee('Bu mövzuda hələ etiraf yoxdur.')
            ->assertSee('name="viewport"', false);
    }

    public function test_unknown_topic_uses_the_styled_not_found_screen(): void
    {
        $this->get('/az/topics/not-a-category')
            ->assertNotFound()
            ->assertSee('Səhifə tapılmadı')
            ->assertSee('Etiraflara qayıt');
    }

    public function test_search_query_limit_is_configurable_on_the_server(): void
    {
        config(['lavbe.limits.search_query' => 5]);

        $this->post('/en/search', ['q' => '12345'])->assertOk();
        $this->post('/en/search', ['q' => '123456'])->assertStatus(422);
    }

    public function test_admin_preview_is_synthetic_and_is_not_exposed_as_an_admin_route(): void
    {
        $this->get('/_ui/admin-preview')
            ->assertOk()
            ->assertSee(__('ui.preview'))
            ->assertSee(__('ui.preview_hint'));

        $this->get('/admin')->assertNotFound();
    }

    public function test_admin_preview_is_not_available_in_production(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->get('/_ui/admin-preview')->assertNotFound();
    }

    public function test_explicit_locale_is_not_overridden_and_language_choice_persists(): void
    {
        $this->withHeaders(['Accept-Language' => 'ja,en;q=0.5'])
            ->get('/az/')
            ->assertOk()
            ->assertSee('<html lang="az"', false);

        $this->post('/az/language', ['locale' => 'tr', 'return_to' => '/az/topics/family?sort=weekly'])
            ->assertRedirect('/tr/topics/family?sort=weekly')
            ->assertCookie('lavbe_locale');

        $this->withCookie('lavbe_locale', 'tr')->get('/')
            ->assertRedirect('/tr/');
    }

    public function test_first_visit_uses_language_then_country_then_english(): void
    {
        $this->withHeaders(['Accept-Language' => 'ja;q=0.9,en;q=0.5'])
            ->get('/')
            ->assertRedirect('/ja/');

        $this->withHeaders(['Accept-Language' => '', 'CF-IPCountry' => 'AZ'])
            ->get('/')
            ->assertRedirect('/az/');

        $this->withHeaders(['Accept-Language' => '', 'CF-IPCountry' => 'XX'])
            ->get('/')
            ->assertRedirect('/en/');
    }

    public function test_feed_renders_only_public_same_locale_records_in_twelve_item_pages(): void
    {
        Confession::factory()->count(13)->create(['locale' => 'az']);
        Confession::factory()->create(['locale' => 'en', 'title' => 'Other locale story']);
        Confession::factory()->create(['locale' => 'az', 'status' => 'draft', 'title' => 'Private draft']);

        $this->get('/az/')
            ->assertOk()
            ->assertDontSee('Other locale story')
            ->assertDontSee('Private draft')
            ->assertSee('Növbəti səhifə')
            ->assertSee('pagination-number', false)
            ->assertSee('Sınaq hekayəsi');

        $this->get('/az/?page=2')
            ->assertOk()
            ->assertDontSee('<link rel="next"', false)
            ->assertSee('<meta name="robots" content="noindex,follow">', false);
    }

    public function test_feed_cursor_is_bound_to_locale_and_filter_and_fragments_exclude_robots(): void
    {
        Confession::factory()->count(13)->create(['locale' => 'az']);
        $cursor = app(FeedCursor::class)->issue('az', null, 'new', 12);

        $this->get('/az/feed-fragment?cursor='.urlencode($cursor).'&order=new')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex')
            ->assertJsonPath('has_more', false)
            ->assertJsonPath('next_cursor', null);

        $this->get('/az/feed-fragment?cursor='.urlencode($cursor).'&order=new&category=family')->assertBadRequest();

        $this->get('/en/feed-fragment?cursor='.urlencode($cursor).'&order=new')->assertBadRequest();
        $this->get('/az/feed-fragment?cursor=broken&order=new')->assertBadRequest();
    }

    public function test_search_is_same_locale_posted_and_private_from_cache_and_referrers(): void
    {
        Confession::factory()->create(['locale' => 'az', 'title' => 'İstanbul işıqları']);
        Confession::factory()->create(['locale' => 'en', 'title' => 'İstanbul other language']);
        Confession::factory()->create(['locale' => 'ja', 'title' => '家族のことを話したい']);

        $this->post('/az/search', ['q' => 'İstanbul'])
            ->assertOk()
            ->assertSee('İstanbul işıqları')
            ->assertDontSee('İstanbul other language')
            ->assertHeader('X-Robots-Tag', 'noindex')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertDontSee('value="İstanbul"', false);

        $this->post('/ja/search', ['q' => '家族'])
            ->assertOk()
            ->assertSee('家族のことを話したい');
    }

    public function test_expired_cursor_returns_gone(): void
    {
        $this->travelTo(now()->subHours(2));
        $cursor = app(FeedCursor::class)->issue('az', null, 'new', 12);
        $this->travelBack();

        $this->get('/az/feed-fragment?cursor='.urlencode($cursor).'&order=new')->assertStatus(410);
    }
}
