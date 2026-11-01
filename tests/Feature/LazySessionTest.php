<?php

namespace Tests\Feature;

use App\Support\LazyDatabaseSessionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class LazySessionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function handler(): LazyDatabaseSessionHandler
    {
        return new LazyDatabaseSessionHandler(DB::connection(), 'sessions', 120, $this->app);
    }

    private function payload(array $data): string
    {
        return encrypt(json_encode($data), false);
    }

    private function lastActivity(string $id): int
    {
        return (int) DB::table('sessions')->where('id', $id)->value('last_activity');
    }

    /** reads then writes, as one request does */
    private function request(string $id, array $data): void
    {
        $handler = $this->handler();
        $handler->read($id);
        $handler->write($id, $this->payload($data));
    }

    public function test_the_database_driver_uses_the_lazy_handler(): void
    {
        config(['session.driver' => 'database']);

        $this->assertInstanceOf(LazyDatabaseSessionHandler::class, Session::driver('database')->getHandler());
    }

    public function test_an_unchanged_session_is_not_rewritten_within_the_refresh_window(): void
    {
        config(['session.encrypt' => true]);
        Carbon::setTestNow('2026-11-01 10:00:00');
        $this->request('abc', ['_token' => 't', '_previous' => ['url' => 'https://example.test/a']]);
        $first = $this->lastActivity('abc');

        // another page a minute later only changes the previous URL
        Carbon::setTestNow('2026-11-01 10:01:00');
        $this->request('abc', ['_token' => 't', '_previous' => ['url' => 'https://example.test/b']]);

        $this->assertSame($first, $this->lastActivity('abc'));
    }

    public function test_an_unchanged_session_is_refreshed_once_the_window_passes(): void
    {
        config(['session.encrypt' => true]);
        Carbon::setTestNow('2026-11-01 10:00:00');
        $this->request('abc', ['_token' => 't']);

        Carbon::setTestNow(Carbon::parse('2026-11-01 10:00:00')->addSeconds(LazyDatabaseSessionHandler::REFRESH_SECONDS));
        $this->request('abc', ['_token' => 't']);

        $this->assertSame(Carbon::now()->getTimestamp(), $this->lastActivity('abc'));
    }

    public function test_a_changed_session_is_written_at_once(): void
    {
        config(['session.encrypt' => true]);
        Carbon::setTestNow('2026-11-01 10:00:00');
        $this->request('abc', ['_token' => 't']);

        Carbon::setTestNow('2026-11-01 10:01:00');
        $this->request('abc', ['_token' => 't', '_flash' => ['new' => ['status'], 'old' => []], 'status' => 'saved']);

        $this->assertSame(Carbon::now()->getTimestamp(), $this->lastActivity('abc'));
        $this->assertStringContainsString('saved', decrypt(base64_decode(DB::table('sessions')->where('id', 'abc')->value('payload')), false));
    }

    public function test_a_new_session_id_is_always_written(): void
    {
        Carbon::setTestNow('2026-11-01 10:00:00');
        $this->request('old', ['_token' => 't']);

        // sign-in moves the session to a new id inside the same request
        $handler = $this->handler();
        $handler->read('old');
        $handler->destroy('old');
        $handler->setExists(false);
        $handler->write('new', $this->payload(['_token' => 't']));

        $this->assertDatabaseMissing('sessions', ['id' => 'old']);
        $this->assertDatabaseHas('sessions', ['id' => 'new']);
    }

    public function test_requests_never_sweep_sessions(): void
    {
        $this->assertSame(0, config('session.lottery')[0]);
    }
}
