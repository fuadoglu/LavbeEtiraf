<?php

namespace Tests\Feature;

use App\Models\Confession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeparateCommentFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_story_has_inline_comment_composer_and_keeps_private_form_fallback(): void
    {
        $story = Confession::factory()->create(['public_id' => (string) Str::uuid()]);
        $detail = '/az/confession/'.$story->public_id;
        $form = $detail.'/comment';
        $this->get($detail)->assertOk()->assertSee('id="comment-body-new"', false)->assertDontSee('id="comment-body"', false);
        $response = $this->get($form)->assertOk()->assertSee('id="comment-body"', false)
            ->assertSee('name="idempotency_key"', false);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame('noindex, nofollow, noarchive', $response->headers->get('X-Robots-Tag'));
        $story->update(['reviews_open' => false]);
        $this->get($form)->assertOk()->assertDontSee('id="comment-body"', false);
        $story->update(['status' => 'hidden']);
        $this->get($form)->assertNotFound();
    }
}
