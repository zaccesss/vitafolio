<?php

namespace Tests\Feature;

use App\Mail\VisitorMessage;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Address;
use Tests\TestCase;

class ReplyToTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.default' => 'array', 'vitafolio.reply_to' => 'support@example.com']);
    }

    /** @return list<string> */
    private function lastReplyTo(): array
    {
        $sent = Mail::mailer('array')->getSymfonyTransport()->messages()->last();

        return array_map(fn (Address $a) => $a->getAddress(), $sent->getOriginalMessage()->getReplyTo());
    }

    public function test_automated_mail_replies_to_the_configured_inbox(): void
    {
        Mail::raw('Hello', fn ($m) => $m->to('someone@example.com')->subject('Hi'));

        $this->assertSame(['support@example.com'], $this->lastReplyTo());
    }

    public function test_a_contact_message_keeps_the_visitor_as_its_only_reply_to(): void
    {
        Mail::to('owner@example.com')->send(new VisitorMessage(
            ['sender_name' => 'Ada', 'sender_email' => 'ada@example.com', 'message' => 'Hello there, a question.'],
            ':app enquiry',
        ));

        $this->assertSame(['ada@example.com'], $this->lastReplyTo());
    }
}
