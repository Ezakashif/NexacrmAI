<?php

namespace Tests\Feature;

use App\Mail\Marketing\ContactInquiryMail;
use App\Models\ContactInquiry;
use App\Models\User;
use App\Notifications\ContactInquiryReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContactInquirySuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_persists_inquiry_and_notifies_super_admins(): void
    {
        Mail::fake();
        Notification::fake();

        $superAdmin = User::factory()->superAdmin()->create();
        $otherAdmin = User::factory()->superAdmin()->create([
            'email' => 'ops@example.com',
        ]);

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

        $inquiry = ContactInquiry::query()->where('email', 'alex@example.com')->first();

        $this->assertNotNull($inquiry);
        $this->assertSame('demo', $inquiry->intent);
        $this->assertSame(ContactInquiry::STATUS_NEW, $inquiry->status);
        $this->assertSame('Northline', $inquiry->company);

        Mail::assertQueued(ContactInquiryMail::class);

        Notification::assertSentTo($superAdmin, ContactInquiryReceived::class);
        Notification::assertSentTo($otherAdmin, ContactInquiryReceived::class);
    }

    public function test_super_admin_can_view_contact_inquiries_index_and_show(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $inquiry = ContactInquiry::factory()->demo()->create([
            'name' => 'Jordan Lee',
            'email' => 'jordan@example.com',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('superadmin.contact-inquiries.index'))
            ->assertOk()
            ->assertSee('Contact inquiries')
            ->assertSee('Jordan Lee')
            ->assertSee('jordan@example.com');

        $this->actingAs($superAdmin)
            ->get(route('superadmin.contact-inquiries.show', $inquiry))
            ->assertOk()
            ->assertSee('Demo request')
            ->assertSee('Jordan Lee')
            ->assertSee('We would like to book a demo of Algos.');

        $this->assertSame(ContactInquiry::STATUS_REVIEWED, $inquiry->fresh()->status);
        $this->assertSame($superAdmin->id, $inquiry->fresh()->reviewed_by);
    }

    public function test_super_admin_can_update_inquiry_status(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $inquiry = ContactInquiry::factory()->demo()->create();

        $this->actingAs($superAdmin)
            ->patch(route('superadmin.contact-inquiries.status', $inquiry), [
                'status' => ContactInquiry::STATUS_CLOSED,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(ContactInquiry::STATUS_CLOSED, $inquiry->fresh()->status);
    }

    public function test_tenant_admin_cannot_view_contact_inquiries(): void
    {
        $admin = User::factory()->admin()->create();
        $inquiry = ContactInquiry::factory()->create();

        $this->actingAs($admin)
            ->get(route('superadmin.contact-inquiries.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('superadmin.contact-inquiries.show', $inquiry))
            ->assertForbidden();
    }

    public function test_super_admin_notifications_page_lists_inquiry_notification(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $inquiry = ContactInquiry::factory()->demo()->create([
            'name' => 'Sam Rivera',
            'email' => 'sam@example.com',
        ]);

        $superAdmin->notifyNow(new ContactInquiryReceived($inquiry));

        $this->actingAs($superAdmin)
            ->get(route('superadmin.notifications.index'))
            ->assertOk()
            ->assertSee('New demo request')
            ->assertSee('sam@example.com');

        $this->actingAs($superAdmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Contact inquiries')
            ->assertSee('Sam Rivera');
    }
}
