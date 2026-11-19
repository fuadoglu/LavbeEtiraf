<?php

namespace Tests\Feature;

use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Models\ConfessionOwnership;
use App\Models\ModeratorGrant;
use App\Models\ReportGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('mysql')]
class MySqlSmokeTest extends TestCase
{
    public function test_configured_mysql_database_is_reachable_after_migrations(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame(1, DB::selectOne('select 1 as connected')->connected);
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('migrations'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumns('confessions', [
            'public_id', 'author_alias', 'age_confirmed_at', 'submission_key_hash', 'payload_fingerprint', 'public_updated_at', 'reviews_open', 'support_count',
        ]));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('confession_revisions'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('confession_ownerships'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('confession_ownerships', 'session_token_hash'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('confessions', 'public_revision_id'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('moderator_grants'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('moderator_mfas'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('moderation_audits'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('moderation_outbox'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('push_subscriptions'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('web_push_deliveries'));
        foreach (['report_groups', 'report_submissions', 'moderation_appeals'] as $table) {
            $this->assertTrue(DB::getSchemaBuilder()->hasTable($table), 'Missing moderation report table: '.$table);
        }
        foreach (['confession_follows', 'user_notifications'] as $table) {
            $this->assertTrue(DB::getSchemaBuilder()->hasTable($table), 'Missing account table: '.$table);
        }
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('confession_ownerships', 'user_id'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('comment_ownerships', 'user_id'));
        foreach ([
            'confession_comments', 'confession_comment_revisions', 'comment_ownerships', 'comment_responses',
            'comment_response_revisions', 'confession_continuations', 'confession_continuation_revisions',
            'confession_participants', 'confession_support_reactions', 'participation_idempotencies', 'participation_outbox',
        ] as $table) {
            $this->assertTrue(DB::getSchemaBuilder()->hasTable($table), 'Missing participation table: '.$table);
        }

        $uniqueIndexes = collect(DB::select('SHOW INDEX FROM confessions'))
            ->filter(fn (object $index): bool => (int) $index->Non_unique === 0)
            ->pluck('Key_name')->all();
        $this->assertContains('confessions_public_id_unique', $uniqueIndexes);
        $this->assertContains('confessions_submission_key_hash_unique', $uniqueIndexes);
    }

    public function test_parallel_same_owner_account_claims_revoke_guest_credentials_once(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $publicId = (string) Str::uuid();
        $ownerToken = 'synthetic-parallel-owner-'.Str::random(20);
        $confession = Confession::query()->create([
            'locale' => 'en', 'status' => 'unpublished', 'title' => '', 'body' => '', 'public_id' => $publicId,
            'author_alias' => 'Lavbe #A1B2C3D4',
        ]);
        $originalCookieHash = hash_hmac('sha256', $ownerToken, (string) config('app.key'));
        $confession->ownership()->create([
            'session_token_hash' => hash_hmac('sha256', 'synthetic-guest-session', (string) config('app.key')),
            'owner_token_hash' => $originalCookieHash,
            'recovery_token_hash' => hash_hmac('sha256', 'synthetic-recovery-code', (string) config('app.key')),
        ]);
        $barrier = tempnam(sys_get_temp_dir(), 'lavbe-account-claim-race-');
        unlink($barrier);
        $worker = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
while (! is_file($argv[4])) { usleep(1000); }
$request = Illuminate\Http\Request::create('/en/my/'.$argv[2].'/claim', 'POST');
$request->cookies->set('lavbe_owner_'.$argv[2], $argv[3]);
$session = new Illuminate\Session\Store('claim-test', new Illuminate\Session\ArraySessionHandler(120));
$session->start();
$request->setLaravelSession($session);
$request->setUserResolver(fn () => App\Models\User::query()->findOrFail((int) $argv[1]));
$app->make(App\Support\Accounts\AccountOwnershipService::class)->claimConfession($request, $request->user(), $argv[2]);
echo 'claimed';
PHP;
        $environment = array_merge(getenv(), [
            'APP_ENV' => 'testing', 'APP_KEY' => (string) config('app.key'), 'APP_URL' => 'http://localhost',
            'DB_CONNECTION' => 'mysql', 'DB_HOST' => (string) config('database.connections.mysql.host'),
            'DB_PORT' => (string) config('database.connections.mysql.port'), 'DB_DATABASE' => (string) config('database.connections.mysql.database'),
            'DB_USERNAME' => (string) config('database.connections.mysql.username'), 'DB_PASSWORD' => (string) config('database.connections.mysql.password'),
        ]);
        try {
            $statuses = $this->runParallelWorkers($worker, [
                [(string) $user->id, $publicId, $ownerToken, $barrier],
                [(string) $user->id, $publicId, $ownerToken, $barrier],
            ], $environment, $barrier);
            $this->assertSame(['claimed', 'claimed'], $statuses);
            $ownership = ConfessionOwnership::query()->where('confession_id', $confession->id)->firstOrFail();
            $this->assertSame($user->id, $ownership->user_id);
            $this->assertNull($ownership->session_token_hash);
            $this->assertNotSame($originalCookieHash, $ownership->owner_token_hash);
        } finally {
            @unlink($barrier);
            $confession->delete();
            $user->delete();
        }
    }

    public function test_parallel_duplicate_reports_serialize_to_one_submission_for_the_same_account(): void
    {
        $user = User::factory()->create();
        $confession = Confession::query()->create([
            'locale' => 'en', 'status' => 'public', 'title' => 'Synthetic report target', 'body' => 'Synthetic report body',
            'excerpt' => 'Synthetic report body', 'public_id' => (string) Str::uuid(), 'author_alias' => 'Lavbe #A1B2C3D4',
            'published_at' => now(), 'public_updated_at' => now(), 'ever_public' => true,
        ]);
        $revision = $confession->revisions()->create(['revision' => 1, 'title' => $confession->title, 'body' => $confession->body, 'category_key' => 'family', 'status' => 'approved']);
        $confession->forceFill(['public_revision_id' => $revision->id])->save();
        $barrier = tempnam(sys_get_temp_dir(), 'lavbe-report-race-');
        unlink($barrier);
        $worker = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
while (! is_file($argv[4])) { usleep(1000); }
$request = Illuminate\Http\Request::create('/en/report/community/confession/race', 'POST');
$request->setUserResolver(fn () => App\Models\User::query()->findOrFail((int) $argv[2]));
$group = $app->make(App\Support\Moderation\ReportService::class)->submit($request, 'confession', (int) $argv[1], 'community', 'spam', 'Synthetic report detail', bin2hex(random_bytes(16)));
echo (string) $group->id;
PHP;
        $environment = array_merge(getenv(), [
            'APP_ENV' => 'testing', 'APP_KEY' => (string) config('app.key'), 'APP_URL' => 'http://localhost',
            'DB_CONNECTION' => 'mysql', 'DB_HOST' => (string) config('database.connections.mysql.host'),
            'DB_PORT' => (string) config('database.connections.mysql.port'), 'DB_DATABASE' => (string) config('database.connections.mysql.database'),
            'DB_USERNAME' => (string) config('database.connections.mysql.username'), 'DB_PASSWORD' => (string) config('database.connections.mysql.password'),
        ]);
        try {
            $statuses = $this->runParallelWorkers($worker, [
                [(string) $confession->id, (string) $user->id, bin2hex(random_bytes(16)), $barrier],
                [(string) $confession->id, (string) $user->id, bin2hex(random_bytes(16)), $barrier],
            ], $environment, $barrier);
            $this->assertCount(2, $statuses);
            $this->assertSame($statuses[0], $statuses[1]);
            $this->assertSame(1, ReportGroup::query()->where('confession_id', $confession->id)->value('report_count'));
            $this->assertDatabaseCount('report_submissions', 1);
        } finally {
            @unlink($barrier);
            $confession->delete();
            $user->delete();
        }
    }

    public function test_two_parallel_recovery_requests_have_only_one_winner(): void
    {
        $publicId = (string) Str::uuid();
        $recoveryCode = 'synthetic-recovery-'.str_repeat('R', 32);
        $confession = Confession::query()->create([
            'locale' => 'en',
            'status' => 'unpublished',
            'title' => '',
            'body' => '',
            'public_id' => $publicId,
            'author_alias' => 'Lavbe #A1B2C3D4',
        ]);
        $confession->ownership()->create([
            'owner_token_hash' => hash_hmac('sha256', 'synthetic-owner-'.str_repeat('O', 32), (string) config('app.key')),
            'recovery_token_hash' => hash_hmac('sha256', $recoveryCode, (string) config('app.key')),
        ]);

        $barrier = tempnam(sys_get_temp_dir(), 'lavbe-recovery-race-');
        unlink($barrier);
        $worker = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
while (! is_file($argv[4])) { usleep(1000); }
$request = Illuminate\Http\Request::create('/'.$argv[1].'/my/'.$argv[2].'/recover', 'POST', ['recovery_code' => $argv[3]]);
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request);
echo $response->getStatusCode();
$kernel->terminate($request, $response);
PHP;
        $environment = array_merge(getenv(), [
            'APP_ENV' => 'testing',
            'APP_KEY' => (string) config('app.key'),
            'APP_URL' => 'http://localhost',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => (string) config('database.connections.mysql.host'),
            'DB_PORT' => (string) config('database.connections.mysql.port'),
            'DB_DATABASE' => (string) config('database.connections.mysql.database'),
            'DB_USERNAME' => (string) config('database.connections.mysql.username'),
            'DB_PASSWORD' => (string) config('database.connections.mysql.password'),
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
        ]);

        try {
            $processes = [];
            for ($i = 0; $i < 2; $i++) {
                $pipes = [];
                $process = proc_open(
                    [PHP_BINARY, '-r', $worker, 'en', $publicId, $recoveryCode, $barrier],
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                    $pipes,
                    base_path(),
                    $environment,
                );
                $this->assertIsResource($process);
                fclose($pipes[0]);
                $processes[] = [$process, $pipes];
            }
            file_put_contents($barrier, 'go');
            $statuses = [];
            foreach ($processes as [$process, $pipes]) {
                $statuses[] = trim(stream_get_contents($pipes[1]));
                $errorOutput = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exitCode = proc_close($process);
                $this->assertSame(0, $exitCode, 'Parallel recovery worker failed without exposing its input. '.($errorOutput === '' ? '' : 'See test process stderr.'));
            }

            sort($statuses);
            $this->assertSame(['200', '404'], $statuses);
            $this->assertSame(2, (int) $confession->ownership()->value('credential_version'));
        } finally {
            @unlink($barrier);
            $confession->delete();
        }
    }

    public function test_parallel_approval_and_owner_withdrawal_always_leave_withdrawn_visibility(): void
    {
        $user = User::factory()->create();
        ModeratorGrant::query()->create([
            'user_id' => $user->id,
            'role' => 'moderator',
            'locale' => 'en',
            'reason' => 'synthetic MySQL race test grant',
        ]);
        $confession = Confession::query()->create([
            'locale' => 'en',
            'status' => 'unpublished',
            'title' => '',
            'body' => '',
            'public_id' => (string) Str::uuid(),
            'author_alias' => 'Lavbe #A1B2C3D4',
        ]);
        $revision = $confession->revisions()->create([
            'revision' => 1,
            'title' => 'Synthetic race title',
            'body' => 'Synthetic race confession',
            'category_key' => 'family',
            'status' => 'pending',
        ]);
        $ownerSessionId = 'synthetic-owner-session-'.str_repeat('S', 32);
        $ownerCookieToken = 'synthetic-owner-cookie-'.str_repeat('C', 32);
        $confession->ownership()->create([
            'session_token_hash' => hash_hmac('sha256', $ownerSessionId, (string) config('app.key')),
            'owner_token_hash' => hash_hmac('sha256', $ownerCookieToken, (string) config('app.key')),
            'recovery_token_hash' => hash_hmac('sha256', 'synthetic-recovery-'.str_repeat('R', 32), (string) config('app.key')),
        ]);

        $barrier = tempnam(sys_get_temp_dir(), 'lavbe-moderation-race-');
        unlink($barrier);
        $worker = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
while (! is_file($argv[7])) { usleep(1000); }
try {
    if ($argv[1] === 'approve') {
        $app->make(App\Support\Moderation\ModerationService::class)->decide(
            App\Models\User::query()->findOrFail((int) $argv[4]),
            (int) $argv[2], (int) $argv[3], 1, 'approve', 'acceptable', null, null,
            'parallel-approve-'.str_repeat('A', 32), false, (string) Illuminate\Support\Str::uuid(),
        );
        echo '200';
        } else {
        $request = Illuminate\Http\Request::create('/en/my/'.$argv[5].'/withdraw', 'POST', [
            'expected_lock_version' => 1,
            'idempotency_key' => str_repeat('b', 32),
            'confirm_withdraw' => '1',
        ], ['lavbe_owner_'.$argv[5] => $argv[8]]);
        $session = new Illuminate\Session\Store('moderation-race-owner', new Illuminate\Session\ArraySessionHandler(120));
        $session->setId($argv[6]);
        $session->start();
        $request->setLaravelSession($session);
        $response = $app->make(App\Http\Controllers\GuestConfessionController::class)->withdraw($request, 'en', $argv[5]);
        echo (string) $response->getStatusCode();
    }
} catch (Symfony\Component\HttpKernel\Exception\HttpException $exception) {
    echo (string) $exception->getStatusCode();
}
PHP;
        $environment = array_merge(getenv(), [
            'APP_ENV' => 'testing',
            'APP_KEY' => (string) config('app.key'),
            'APP_URL' => 'http://localhost',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => (string) config('database.connections.mysql.host'),
            'DB_PORT' => (string) config('database.connections.mysql.port'),
            'DB_DATABASE' => (string) config('database.connections.mysql.database'),
            'DB_USERNAME' => (string) config('database.connections.mysql.username'),
            'DB_PASSWORD' => (string) config('database.connections.mysql.password'),
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
        ]);

        try {
            $processes = [];
            foreach (['approve', 'withdraw'] as $action) {
                $pipes = [];
                $process = proc_open(
                    [PHP_BINARY, '-r', $worker, $action, (string) $confession->id, (string) $revision->id, (string) $user->id, $confession->public_id, $ownerSessionId, $barrier, $ownerCookieToken],
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                    $pipes,
                    base_path(),
                    $environment,
                );
                $this->assertIsResource($process);
                fclose($pipes[0]);
                $processes[] = [$process, $pipes];
            }
            file_put_contents($barrier, 'go');
            $statuses = [];
            foreach ($processes as [$process, $pipes]) {
                $statuses[] = trim(stream_get_contents($pipes[1]));
                stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $this->assertSame(0, proc_close($process));
            }

            $this->assertSame('withdrawn', $confession->fresh()->status);
            $this->assertGreaterThanOrEqual(2, (int) $confession->fresh()->visibility_version);
            $this->assertCount(2, $statuses);
            $this->assertContains('302', $statuses);
            $approvalStatus = collect($statuses)->first(fn (string $status): bool => $status !== '302');
            $this->assertContains($approvalStatus, ['200', '409']);
            $events = DB::table('moderation_outbox')->where('target_public_id', $confession->public_id)->orderBy('visibility_version')->get();
            $this->assertSame('withdraw', $events->last()->event_type);
            $this->assertGreaterThan(1, $events->last()->visibility_version);
        } finally {
            @unlink($barrier);
            DB::table('moderation_audits')->where('target_public_id', $confession->public_id)->delete();
            DB::table('moderation_outbox')->where('target_public_id', $confession->public_id)->delete();
            $confession->delete();
            $user->delete();
        }
    }

    public function test_mysql_decisions_serialize_double_approve_and_approve_hide_races(): void
    {
        $user = User::factory()->create();
        ModeratorGrant::query()->create([
            'user_id' => $user->id,
            'role' => 'moderator',
            'locale' => 'en',
            'reason' => 'synthetic MySQL decision race grant',
        ]);

        $doubleApprove = Confession::query()->create([
            'locale' => 'en',
            'status' => 'unpublished',
            'title' => '',
            'body' => '',
            'public_id' => (string) Str::uuid(),
            'author_alias' => 'Lavbe #D0B1E001',
            'lock_version' => 1,
            'visibility_version' => 1,
        ]);
        $pending = $doubleApprove->revisions()->create([
            'revision' => 1,
            'title' => 'Synthetic double approve title',
            'body' => 'Synthetic double approve body',
            'category_key' => 'family',
            'status' => 'pending',
        ]);
        $this->runParallelDecisions($user, $doubleApprove, [
            ['approve', $pending->id, 1],
            ['approve', $pending->id, 1],
        ]);
        $this->assertSame('public', $doubleApprove->fresh()->status);
        $this->assertSame(1, DB::table('moderation_audits')->where('target_public_id', $doubleApprove->public_id)->count());
        $this->assertSame(1, DB::table('moderation_outbox')->where('target_public_id', $doubleApprove->public_id)->count());

        $approveHide = Confession::query()->create([
            'locale' => 'en',
            'status' => 'public',
            'title' => 'Synthetic current title',
            'body' => 'Synthetic current body',
            'category_key' => 'family',
            'public_id' => (string) Str::uuid(),
            'author_alias' => 'Lavbe #A990B11C',
            'published_at' => now(),
            'lock_version' => 2,
            'visibility_version' => 2,
        ]);
        $publicRevision = $approveHide->revisions()->create([
            'revision' => 1,
            'title' => 'Synthetic current title',
            'body' => 'Synthetic current body',
            'category_key' => 'family',
            'status' => 'approved',
        ]);
        $nextRevision = $approveHide->revisions()->create([
            'revision' => 2,
            'title' => 'Synthetic next title',
            'body' => 'Synthetic next body',
            'category_key' => 'family',
            'status' => 'pending',
        ]);
        $approveHide->forceFill(['public_revision_id' => $publicRevision->id])->save();

        $this->runParallelDecisions($user, $approveHide, [
            ['approve', $nextRevision->id, 2],
            ['hide', $publicRevision->id, 2],
        ]);
        $this->assertContains($approveHide->fresh()->status, ['public', 'hidden']);
        $events = DB::table('moderation_outbox')->where('target_public_id', $approveHide->public_id)->get();
        $this->assertCount(1, $events);
        $this->assertSame(3, (int) $events->first()->visibility_version);

        DB::table('moderation_audits')->whereIn('target_public_id', [$doubleApprove->public_id, $approveHide->public_id])->delete();
        DB::table('moderation_outbox')->whereIn('target_public_id', [$doubleApprove->public_id, $approveHide->public_id])->delete();
        $doubleApprove->delete();
        $approveHide->delete();
        $user->delete();
    }

    public function test_parallel_close_reviews_and_comment_submission_never_accepts_after_close(): void
    {
        $confession = Confession::query()->create([
            'locale' => 'en', 'status' => 'public', 'published_at' => now(), 'public_updated_at' => now(),
            'reviews_open' => true, 'title' => 'Synthetic participation race', 'body' => 'Synthetic public story',
            'category_key' => 'family', 'public_id' => (string) Str::uuid(), 'author_alias' => 'Lavbe #A1B2C3D4',
        ]);
        $revision = $confession->revisions()->create([
            'revision' => 1, 'title' => 'Synthetic participation race', 'body' => 'Synthetic public story',
            'category_key' => 'family', 'status' => 'approved',
        ]);
        $confession->forceFill(['public_revision_id' => $revision->id])->save();
        $ownerToken = 'synthetic-review-cookie-'.str_repeat('C', 16);
        $confession->ownership()->create([
            'owner_token_hash' => hash_hmac('sha256', $ownerToken, (string) config('app.key')),
            'recovery_token_hash' => hash_hmac('sha256', 'synthetic-review-recovery-'.str_repeat('R', 16), (string) config('app.key')),
        ]);
        $barrier = tempnam(sys_get_temp_dir(), 'lavbe-review-race-');
        unlink($barrier);
        $worker = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
while (! is_file($argv[4])) { usleep(1000); }
try {
    $request = Illuminate\Http\Request::create('/en/confession/'.$argv[2], 'POST');
    $session = new Illuminate\Session\Store('review-race', new Illuminate\Session\ArraySessionHandler(120));
    $session->setId($argv[3]);
    $session->start();
    $request->setLaravelSession($session);
    if ($argv[1] === 'close') {
        $request->cookies->set('lavbe_owner_'.$argv[2], $argv[5]);
        $app->make(App\Support\Participation\ParticipationService::class)->setReviewsOpen($request, $argv[2], false, 'parallel-close-'.str_repeat('K', 20));
        echo 'closed';
    } else {
        $app->make(App\Support\Participation\ParticipationService::class)->submitComment($request, $argv[2], 'Synthetic parallel response', 'parallel-submit-'.str_repeat('K', 20));
        echo 'submitted';
    }
} catch (Symfony\Component\HttpKernel\Exception\HttpException $exception) {
    echo (string) $exception->getStatusCode();
}
PHP;
        $environment = array_merge(getenv(), [
            'APP_ENV' => 'testing', 'APP_KEY' => (string) config('app.key'), 'APP_URL' => 'http://localhost',
            'DB_CONNECTION' => 'mysql', 'DB_HOST' => (string) config('database.connections.mysql.host'),
            'DB_PORT' => (string) config('database.connections.mysql.port'), 'DB_DATABASE' => (string) config('database.connections.mysql.database'),
            'DB_USERNAME' => (string) config('database.connections.mysql.username'), 'DB_PASSWORD' => (string) config('database.connections.mysql.password'),
            'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array',
        ]);
        try {
            $statuses = $this->runParallelWorkers($worker, [
                ['close', $confession->public_id, 'stable-owner-session', $barrier, $ownerToken],
                ['submit', $confession->public_id, 'stable-participant-session', $barrier, $ownerToken],
            ], $environment, $barrier);
            $this->assertContains('closed', $statuses);
            $this->assertFalse((bool) $confession->fresh()->reviews_open);
            $hasComment = ConfessionComment::query()->where('confession_id', $confession->id)->exists();
            $this->assertSame($hasComment, in_array('submitted', $statuses, true));
            $this->assertSame(! $hasComment, in_array('409', $statuses, true));
        } finally {
            @unlink($barrier);
            $confession->delete();
        }
    }

    public function test_parallel_parent_hide_and_comment_publish_never_leave_public_child_under_hidden_parent(): void
    {
        $user = User::factory()->create();
        ModeratorGrant::query()->create(['user_id' => $user->id, 'role' => 'moderator', 'locale' => 'en', 'reason' => 'synthetic parent hide race']);
        $confession = Confession::query()->create([
            'locale' => 'en', 'status' => 'public', 'published_at' => now(), 'public_updated_at' => now(),
            'title' => 'Synthetic parent visibility race', 'body' => 'Synthetic story', 'category_key' => 'family',
            'public_id' => (string) Str::uuid(), 'author_alias' => 'Lavbe #A1B2C3D4',
        ]);
        $parentRevision = $confession->revisions()->create([
            'revision' => 1, 'title' => 'Synthetic parent visibility race', 'body' => 'Synthetic story',
            'category_key' => 'family', 'status' => 'approved',
        ]);
        $confession->forceFill(['public_revision_id' => $parentRevision->id])->save();
        $comment = $confession->comments()->create([
            'public_id' => (string) Str::uuid(), 'status' => 'unpublished', 'author_alias' => 'Lavbe #D4C3B2A1', 'body' => '',
        ]);
        $commentRevision = $comment->revisions()->create(['revision' => 1, 'body' => 'Synthetic pending response', 'status' => 'pending']);
        $barrier = tempnam(sys_get_temp_dir(), 'lavbe-parent-hide-race-');
        unlink($barrier);
        $worker = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
while (! is_file($argv[7])) { usleep(1000); }
try {
    $actor = App\Models\User::query()->findOrFail((int) $argv[6]);
    if ($argv[1] === 'hide') {
        $app->make(App\Support\Moderation\ModerationService::class)->decide(
            $actor, (int) $argv[2], (int) $argv[3], 1, 'hide', 'privacy', null, null,
            'parallel-parent-hide-'.str_repeat('H', 16), false, (string) Illuminate\Support\Str::uuid(),
        );
    } else {
        $app->make(App\Support\Moderation\ModerationService::class)->decideParticipation(
            $actor, 'comment', (int) $argv[4], (int) $argv[5], 1, 1, 'approve', 'acceptable',
            'parallel-comment-publish-'.str_repeat('P', 12), (string) Illuminate\Support\Str::uuid(),
        );
    }
    echo '200';
} catch (Symfony\Component\HttpKernel\Exception\HttpException $exception) {
    echo (string) $exception->getStatusCode();
}
PHP;
        $environment = array_merge(getenv(), [
            'APP_ENV' => 'testing', 'APP_KEY' => (string) config('app.key'), 'APP_URL' => 'http://localhost',
            'DB_CONNECTION' => 'mysql', 'DB_HOST' => (string) config('database.connections.mysql.host'),
            'DB_PORT' => (string) config('database.connections.mysql.port'), 'DB_DATABASE' => (string) config('database.connections.mysql.database'),
            'DB_USERNAME' => (string) config('database.connections.mysql.username'), 'DB_PASSWORD' => (string) config('database.connections.mysql.password'),
        ]);
        try {
            $statuses = $this->runParallelWorkers($worker, [
                ['hide', (string) $confession->id, (string) $parentRevision->id, (string) $comment->id, (string) $commentRevision->id, (string) $user->id, $barrier],
                ['publish', (string) $confession->id, (string) $parentRevision->id, (string) $comment->id, (string) $commentRevision->id, (string) $user->id, $barrier],
            ], $environment, $barrier);
            sort($statuses);
            $this->assertSame(['200', '409'], $statuses);
            $confession->refresh();
            $comment->refresh();
            $this->assertFalse($confession->status === 'hidden' && $comment->status === 'public');
            if ($confession->status === 'hidden') {
                $this->assertSame('unpublished', $comment->status);
            } else {
                $this->assertSame('public', $confession->status);
                $this->assertSame('public', $comment->status);
            }
        } finally {
            @unlink($barrier);
            DB::table('moderation_audits')->where('target_public_id', $confession->public_id)->delete();
            DB::table('moderation_outbox')->where('target_public_id', $confession->public_id)->delete();
            $confession->delete();
            $user->delete();
        }
    }

    /** @param list<array<string, string>> $arguments @return list<string> */
    private function runParallelWorkers(string $worker, array $arguments, array $environment, string $barrier): array
    {
        $processes = [];
        foreach ($arguments as $argumentList) {
            $pipes = [];
            $process = proc_open(
                array_merge([PHP_BINARY, '-r', $worker], $argumentList),
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                base_path(),
                $environment,
            );
            $this->assertIsResource($process);
            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }
        file_put_contents($barrier, 'go');
        $statuses = [];
        foreach ($processes as [$process, $pipes]) {
            $statuses[] = trim(stream_get_contents($pipes[1]));
            $errorOutput = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $this->assertSame(0, proc_close($process), $errorOutput === '' ? '' : 'Parallel participation worker failed.');
        }

        return $statuses;
    }

    /** @param list<array{string, int, int}> $decisions */
    private function runParallelDecisions(User $user, Confession $confession, array $decisions): void
    {
        $barrier = tempnam(sys_get_temp_dir(), 'lavbe-decisions-race-');
        unlink($barrier);
        $worker = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
while (! is_file($argv[6])) { usleep(1000); }
try {
    $app->make(App\Support\Moderation\ModerationService::class)->decide(
        App\Models\User::query()->findOrFail((int) $argv[4]),
        (int) $argv[2], (int) $argv[3], (int) $argv[5], $argv[1],
        $argv[1] === 'approve' ? 'acceptable' : 'privacy', null, null,
        $argv[7], false, (string) Illuminate\Support\Str::uuid(),
    );
    echo '200';
} catch (Symfony\Component\HttpKernel\Exception\HttpException $exception) {
    echo (string) $exception->getStatusCode();
}
PHP;
        $environment = array_merge(getenv(), [
            'APP_ENV' => 'testing',
            'APP_KEY' => (string) config('app.key'),
            'APP_URL' => 'http://localhost',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => (string) config('database.connections.mysql.host'),
            'DB_PORT' => (string) config('database.connections.mysql.port'),
            'DB_DATABASE' => (string) config('database.connections.mysql.database'),
            'DB_USERNAME' => (string) config('database.connections.mysql.username'),
            'DB_PASSWORD' => (string) config('database.connections.mysql.password'),
        ]);

        try {
            $processes = [];
            foreach ($decisions as [$decision, $revisionId, $version]) {
                $pipes = [];
                $process = proc_open(
                    [PHP_BINARY, '-r', $worker, $decision, (string) $confession->id, (string) $revisionId, (string) $user->id, (string) $version, $barrier, bin2hex(random_bytes(16))],
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                    $pipes,
                    base_path(),
                    $environment,
                );
                $this->assertIsResource($process);
                fclose($pipes[0]);
                $processes[] = [$process, $pipes];
            }
            file_put_contents($barrier, 'go');
            $statuses = [];
            foreach ($processes as [$process, $pipes]) {
                $statuses[] = trim(stream_get_contents($pipes[1]));
                $errorOutput = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $this->assertSame(0, proc_close($process), $errorOutput === '' ? '' : 'Decision worker failed.');
            }
            sort($statuses);
            $this->assertSame(['200', '409'], $statuses);
        } finally {
            @unlink($barrier);
        }
    }
}
