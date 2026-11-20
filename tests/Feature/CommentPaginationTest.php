<?php

namespace Tests\Feature;

use App\Models\Confession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommentPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_comments_use_bounded_html_pages_and_recheck_visibility(): void
    {
        config(['lavbe.pagination.comments' => 2]);
        $story = Confession::factory()->create(['public_id' => (string) Str::uuid()]);
        $comments = [];
        for ($i = 0; $i < 5; $i++) {
            $comment = $story->comments()->create(['public_id' => (string) Str::uuid(), 'status' => 'public', 'author_alias' => 'Lavbe #AABBCCDD', 'body' => 'Synthetic comment '.$i, 'published_at' => now()]);
            $revision = $comment->revisions()->create(['revision' => 1, 'body' => $comment->body, 'status' => 'approved']);
            $comment->update(['public_revision_id' => $revision->id]);
            $comments[] = $comment;
        }
        $path = '/az/confession/'.$story->public_id;
        $first = $this->get($path)->assertOk()->assertSee('Synthetic comment 0')->assertSee('Synthetic comment 1')->assertDontSee('Synthetic comment 2');
        $first->assertSee('comments_page=2#comments-heading', false);
        $this->assertStringContainsString('no-store', $first->headers->get('Cache-Control'));
        $this->get($path.'?comments_page=2')->assertOk()->assertSee('Synthetic comment 2')->assertSee('Synthetic comment 3')->assertDontSee('Synthetic comment 4');
        $comments[2]->update(['status' => 'hidden']);
        $this->get($path.'?comments_page=2')->assertOk()->assertDontSee('Synthetic comment 2')->assertSee('Synthetic comment 4');
        $story->update(['status' => 'hidden']);
        $this->get($path.'?comments_page=2')->assertNotFound();
    }
}
