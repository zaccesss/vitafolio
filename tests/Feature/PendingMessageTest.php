<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Models\PendingMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Tests\TestCase;

// a failed email must never cost a visitor their message or show them an error page
class PendingMessageTest extends TestCase
{
    use RefreshDatabase;

    private const FORM = ['sender_name' => 'Ada', 'sender_email' => 'ada@example.com', 'message' => 'Hello there, a question about the site.'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['vitafolio.contact_email' => 'owner@example.com']);
        Mail::extend('broken', fn () => new class extends AbstractTransport
        {
            protected function doSend(SentMessage $message): void
            {
                throw new TransportException('mail service refused the message');
            }

            public function __toString(): string
            {
                return 'broken';
            }
        });
        config(['mail.mailers.broken' => ['transport' => 'broken'], 'mail.default' => 'broken']);
        Exceptions::fake();
    }

    private function useWorkingMail(): void
    {
        config(['mail.default' => 'array']);
        Mail::forgetMailers();
    }

    public function test_a_contact_message_is_kept_when_its_email_fails(): void
    {
        $this->post(route('contact'), self::FORM)->assertRedirect(route('contact.sent'));

        $pending = PendingMessage::sole();
        $this->assertNull($pending->cv_id);
        $this->assertSame('Ada', $pending->payload['sender_name']);
        // the stored copy is encrypted, so the database alone cannot read it
        $this->assertStringNotContainsString('ada@example.com', DB::table('pending_messages')->value('payload'));
        Exceptions::assertReported(TransportException::class);
    }

    public function test_a_cv_message_is_kept_when_its_email_fails(): void
    {
        $cv = Cv::factory()->create();

        $this->post(route('cv.message', $cv), self::FORM)->assertSessionHas('status');

        $this->assertSame($cv->id, PendingMessage::sole()->cv_id);
    }

    public function test_the_nightly_tidy_up_resends_and_then_forgets_held_messages(): void
    {
        $this->post(route('contact'), self::FORM);
        $this->useWorkingMail();

        Artisan::call('vitafolio:tidy');

        $this->assertSame(0, PendingMessage::count());
        $sent = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $sent);
        $this->assertSame('owner@example.com', $sent->first()->getOriginalMessage()->getTo()[0]->getAddress());
    }

    public function test_a_message_that_still_fails_is_counted_and_dropped_after_two_weeks(): void
    {
        $this->post(route('contact'), self::FORM);

        Artisan::call('vitafolio:tidy');
        $this->assertSame(2, PendingMessage::sole()->attempts);

        $this->travel(PendingMessage::KEEP_DAYS + 1)->days();
        Artisan::call('vitafolio:tidy');
        $this->assertSame(0, PendingMessage::count());
    }

    public function test_a_working_mail_service_stores_nothing(): void
    {
        $this->useWorkingMail();

        $this->post(route('contact'), self::FORM)->assertRedirect(route('contact.sent'));

        $this->assertSame(0, PendingMessage::count());
    }
}
