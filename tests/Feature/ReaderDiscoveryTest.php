<?php

namespace Tests\Feature;

use App\Models\Confession;
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

    public function test_public_story_shows_distinct_category_recommendations_in_same_locale_and_approved_optional_name(): void
    {
        $story = Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'az', 'category_key' => 'family']);
        $revision = $story->revisions()->create(['revision' => 2, 'title' => $story->title, 'author_display_name' => 'Nigar Məmmədova', 'body' => $story->body, 'category_key' => 'family', 'status' => 'approved']);
        $story->forceFill(['public_revision_id' => $revision->id])->save();
        Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'az', 'category_key' => 'family', 'title' => 'AZ same-category story']);
        Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'az', 'category_key' => 'work', 'title' => 'AZ lower-ranked work story', 'excerpt' => 'Lower ranked recommendation excerpt must stay hidden.', 'support_count' => 1]);
        Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'az', 'category_key' => 'work', 'title' => 'AZ leading work story', 'excerpt' => 'Recommendation excerpt must stay hidden.', 'support_count' => 12]);
        Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'az', 'category_key' => 'money', 'title' => 'AZ money story']);
        Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'az', 'category_key' => 'values', 'title' => 'AZ values story']);
        Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'en', 'category_key' => 'education', 'title' => 'EN synthetic recommendation']);

        $response = $this->get('/az/confession/'.$story->public_id)->assertOk()
            ->assertSee('Nigar Məmmədova')
            ->assertSee('AZ leading work story')
            ->assertSee('AZ money story')
            ->assertSee('AZ values story')
            ->assertDontSee('AZ lower-ranked work story')
            ->assertDontSee('AZ same-category story')
            ->assertDontSee('EN synthetic recommendation')
            ->assertDontSee('Recommendation excerpt must stay hidden.')
            ->assertSee('class="recommended-story-link"', false);

        $this->assertSame(3, substr_count($response->getContent(), 'class="recommended-story-link"'));

        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($document);
        $recommendationSection = $xpath->query('//section[@aria-labelledby="recommended-heading"]')->item(0);
        $this->assertNotNull($recommendationSection);
        $this->assertSame(3, $xpath->query('.//a[@class="recommended-story-link"]', $recommendationSection)->length);
        $this->assertSame(0, $xpath->query('.//*[@class="story-meta" or @class="story-excerpt" or @class="story-author" or @class="unique-read-count"]', $recommendationSection)->length);
    }
}
