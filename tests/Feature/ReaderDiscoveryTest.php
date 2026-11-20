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

    public function test_public_story_shows_same_locale_recommendations_and_approved_optional_name(): void
    {
        $story = Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'az', 'category_key' => 'family']);
        $revision = $story->revisions()->create(['revision' => 2, 'title' => $story->title, 'author_display_name' => 'Nigar Məmmədova', 'body' => $story->body, 'category_key' => 'family', 'status' => 'approved']);
        $story->forceFill(['public_revision_id' => $revision->id])->save();
        Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'az', 'category_key' => 'family', 'title' => 'AZ synthetic recommendation']);
        Confession::factory()->create(['public_id' => (string) Str::uuid(), 'locale' => 'en', 'category_key' => 'family', 'title' => 'EN synthetic recommendation']);
        $this->get('/az/confession/'.$story->public_id)->assertOk()->assertSee('Nigar Məmmədova')->assertSee('AZ synthetic recommendation')->assertDontSee('EN synthetic recommendation');
    }
}
