<?php

namespace Tests\Feature;

use App\Models\Confession;
use App\Models\ModeratorGrant;
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
            'public_id', 'author_alias', 'age_confirmed_at', 'submission_key_hash', 'payload_fingerprint',
        ]));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('confession_revisions'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('confession_ownerships'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('confession_ownerships', 'session_token_hash'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('confessions', 'public_revision_id'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('moderator_grants'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('moderator_mfas'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('moderation_audits'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('moderation_outbox'));

        $uniqueIndexes = collect(DB::select('SHOW INDEX FROM confessions'))
            ->filter(fn (object $index): bool => (int) $index->Non_unique === 0)
            ->pluck('Key_name')->all();
        $this->assertContains('confessions_public_id_unique', $uniqueIndexes);
        $this->assertContains('confessions_submission_key_hash_unique', $uniqueIndexes);
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
}
