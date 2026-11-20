<?php

namespace Tests\Feature;

use App\Models\Confession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReaderDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_heart_opens_only_the_current_private_supported_list(): void
    {
        $story = Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'az', 'title' => 'AZ supported story']);
        $other = Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'en', 'title' => 'EN supported story']);
        $this->withSession(['lavbe_supported' => [$story->public_id, $other->public_id]])
            ->get('/az/supported')->assertOk()->assertSessionHas('lavbe_supported', [$story->public_id, $other->public_id])->assertSee($story->publicRevision->title)->assertSee($other->publicRevision->title)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->get('/az')->assertOk()->assertSee('header-supported')->assertSee('/az/supported')
            ->assertSee('/az/legal/terms')->assertSee('/az/legal/community')->assertSee('/az/legal/privacy');
    }

    public function test_support_and_unsupport_update_the_private_list(): void
    {
        $story = Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'az', 'title' => 'Toggle support story']);
        $this->post('/az/confession/'.$story->public_id.'/support', ['action' => 'support', 'idempotency_key' => str_repeat('a', 32)])
            ->assertRedirect('/az/confession/'.$story->public_id);
        $this->get('/az/supported')->assertOk()->assertSee('Toggle support story');

        $this->post('/az/confession/'.$story->public_id.'/support', ['action' => 'unsupport', 'idempotency_key' => str_repeat('b', 32)])
            ->assertRedirect('/az/confession/'.$story->public_id);
        $this->get('/az/supported')->assertOk()->assertSee(__('participation.supported_empty'));
    }

    public function test_share_control_copy_is_available_in_all_supported_locales(): void
    {
        foreach (['en', 'de', 'fr', 'nl', 'sv', 'nb', 'da', 'fi', 'ja', 'ko', 'es', 'it', 'pt-BR', 'pl', 'tr', 'az', 'ru'] as $locale) {
            app()->setLocale($locale);

            foreach (['share_confession', 'share_copied', 'share_copy_hint'] as $key) {
                $this->assertNotSame('participation.'.$key, __('participation.'.$key), "Missing {$key} translation for {$locale}.");
                $this->assertNotSame('', trim(__('participation.'.$key)));
            }
        }
    }

    public function test_public_story_shows_distinct_category_recommendations_in_same_locale_and_approved_optional_name(): void
    {
        $story = Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'az', 'category_key' => 'family']);
        $revision = $story->revisions()->create(['revision' => 2, 'title' => $story->title, 'author_display_name' => 'Nigar Məmmədova', 'body' => $story->body, 'category_key' => 'family', 'status' => 'approved']);
        $story->forceFill(['public_revision_id' => $revision->id])->save();
        Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'az', 'category_key' => 'family', 'title' => 'AZ same-category story']);
        $workStories = collect(['AZ lower-ranked work story', 'AZ leading work story', 'AZ another work story', 'AZ fourth work story', 'AZ fifth work story'])
            ->map(fn (string $title, int $index) => Confession::factory()->create([
                'public_id' => (string) Str::uuid(), 'locale' => 'az', 'category_key' => 'work', 'title' => $title,
                'excerpt' => $index === 1 ? 'Recommendation excerpt must stay hidden.' : null,
                'support_count' => $index === 1 ? 12 : max(1, 5 - $index),
            ]));
        $moneyStory = Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'az', 'category_key' => 'money', 'title' => 'AZ money story']);
        $valuesStory = Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'az', 'category_key' => 'values', 'title' => 'AZ values story']);
        Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'en', 'category_key' => 'education', 'title' => 'EN synthetic recommendation']);

        $eligible = $workStories->concat([$moneyStory, $valuesStory])->keyBy('public_id');
        $observedWorkTitles = collect();
        $observedOrders = collect();

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $response = $this->get('/az/confession/'.$story->public_id);
            $response->assertOk()
                ->assertSee('Nigar Məmmədova')
                ->assertSee('Başqa etirafları oxu')
                ->assertSee(__('participation.share_confession'))
                ->assertSee('data-share-button', false)
                ->assertSee('share-2.svg', false)
                ->assertSee('<noscript>', false)
                ->assertSee('data-share-url="'.rtrim((string) config('app.url'), '/').'/az/confession/'.$story->public_id.'"', false)
                ->assertDontSee('AZ same-category story')
                ->assertDontSee('EN synthetic recommendation')
                ->assertDontSee('Recommendation excerpt must stay hidden.')
                ->assertSee('class="recommended-story-link"', false);

            $document = new \DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
            $xpath = new \DOMXPath($document);
            $recommendationSection = $xpath->query('//section[@aria-labelledby="recommended-heading"]')->item(0);
            $this->assertNotNull($recommendationSection);
            $links = $xpath->query('.//a[@class="recommended-story-link"]', $recommendationSection);
            $this->assertSame(3, $links->length);
            $this->assertSame(0, $xpath->query('.//*[@class="story-meta" or @class="story-excerpt" or @class="story-author" or @class="unique-read-count"]', $recommendationSection)->length);

            $publicIds = collect();
            $categories = collect();
            $titles = collect();
            foreach ($links as $link) {
                preg_match('~/confession/([0-9a-f-]{36})$~', $link->getAttribute('href'), $matches);
                $this->assertNotEmpty($matches[1] ?? null);
                $publicIds->push($matches[1]);
                $storyCandidate = $eligible->get($matches[1]);
                $this->assertNotNull($storyCandidate);
                $categories->push($storyCandidate->category_key);
                $titles->push(trim($link->textContent));
            }

            $this->assertCount(3, $publicIds->unique());
            $this->assertCount(3, $categories->unique());
            $this->assertNotContains('family', $categories->all());
            if ($attempt === 0) {
                $actions = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " confession-actions ")]')->item(0);
                $this->assertNotNull($actions);
                $this->assertSame(2, $xpath->query('./form[contains(@class, "support-action")] | ./div[@data-share-control]', $actions)->length);
                $this->assertSame(1, $xpath->query('./form[contains(@class, "support-action")]/button/span[@class="support-count"]', $actions)->length);
                $this->assertSame(0, $xpath->query('./a[contains(@href, "/account/login")]', $actions)->length);
            }
            $observedWorkTitles = $observedWorkTitles->merge($titles->filter(fn (string $title) => str_contains($title, 'work story')));
            $observedOrders->push($titles->implode('|'));
        }

        $this->assertGreaterThan(1, $observedWorkTitles->unique()->count(), 'Recommendations should vary among candidates in the same category.');
        $this->assertGreaterThan(1, $observedOrders->unique()->count(), 'Recommendation category order should be shuffled between requests.');

        $reader = User::factory()->create(['email_verified_at' => now()]);
        $response = $this->actingAs($reader)->get('/az/confession/'.$story->public_id)->assertOk()->assertSee(__('account.follow'));
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($document);
        $actions = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " confession-actions ")]')->item(0);
        $this->assertNotNull($actions);
        $this->assertSame(3, $xpath->query('./form[contains(@class, "support-action")] | ./form[contains(@class, "follow-action")] | ./div[@data-share-control]', $actions)->length);
        $this->assertSame(1, $xpath->query('./form[contains(@class, "follow-action")]/button', $actions)->length);
    }

    public function test_feed_sort_tabs_have_distinct_icons_and_equal_grid_columns(): void
    {
        $response = $this->get('/az')->assertOk()
            ->assertSee('icons/lucide/sparkles.svg')
            ->assertSee('icons/lucide/calendar-days.svg')
            ->assertSee('icons/lucide/flame.svg')
            ->assertSee(__('lavbe.sort_new'))
            ->assertSee(__('lavbe.sort_weekly'))
            ->assertSee(__('lavbe.sort_popular'));

        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($document);
        $tabs = $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " sort-link ")]');
        $this->assertSame(3, $tabs->length);
        foreach ($tabs as $tab) {
            $this->assertSame(1, $xpath->query('./span[1][contains(concat(" ", normalize-space(@class), " "), " icon ")]', $tab)->length);
            $this->assertSame(1, $xpath->query('./span[2]', $tab)->length);
        }
        $this->assertSame(3, substr_count($response->getContent(), 'class="sort-link'));
        $this->assertStringContainsString('grid-template-columns: repeat(3, minmax(0, 1fr))', file_get_contents(resource_path('css/app.css')));
        $this->assertStringContainsString('flex-direction: row', file_get_contents(resource_path('css/app.css')));
    }
}
