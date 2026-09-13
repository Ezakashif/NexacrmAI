<?php

namespace Tests\Feature;

use App\Mail\Marketing\ContactInquiryMail;
use App\Models\ContactInquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MarketingContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_renders_form_and_business_info(): void
    {
        $this->get(route('marketing.contact'))
            ->assertOk()
            ->assertSee('Talk with our team')
            ->assertSee('Name')
            ->assertSee('Email')
            ->assertSee('Company')
            ->assertSee('Phone')
            ->assertSee('Message')
            ->assertSee(config('marketing.contact.email'))
            ->assertSee(config('marketing.contact.phone'))
            ->assertSee('Answers before you reach out')
            ->assertSee('Is there a free trial?')
            ->assertSee('Can I switch between monthly and annual billing?');
    }

    public function test_demo_intent_prefills_contact_page(): void
    {
        $this->get(route('marketing.contact', ['intent' => 'demo']))
            ->assertOk()
            ->assertSee('Book a demo')
            ->assertSee('book a demo of Algos', false);
    }

    public function test_contact_form_validates_required_fields(): void
    {
        $this->from(route('marketing.contact'))
            ->post(route('marketing.contact.store'), [])
            ->assertRedirect(route('marketing.contact'))
            ->assertSessionHasErrors(['name', 'email', 'message']);
    }

    public function test_contact_form_submits_and_sends_mail(): void
    {
        Mail::fake();

        $payload = [
            'name' => 'Alex Morgan',
            'email' => 'alex@example.com',
            'company' => 'Northline',
            'phone' => '+1 555 010 2000',
            'message' => 'We would like a walkthrough of Algos.',
            'intent' => 'demo',
        ];

        $this->from(route('marketing.contact', ['intent' => 'demo']))
            ->post(route('marketing.contact.store'), $payload)
            ->assertRedirect(route('marketing.contact', ['intent' => 'demo']))
            ->assertSessionHas('status');

        Mail::assertQueued(ContactInquiryMail::class, function (ContactInquiryMail $mail) use ($payload) {
            return $mail->inquiry['email'] === $payload['email']
                && $mail->inquiry['intent'] === 'demo'
                && $mail->hasTo(config('marketing.contact.email'));
        });

        $this->assertDatabaseHas('contact_inquiries', [
            'email' => 'alex@example.com',
            'intent' => 'demo',
            'status' => ContactInquiry::STATUS_NEW,
        ]);
    }
}
