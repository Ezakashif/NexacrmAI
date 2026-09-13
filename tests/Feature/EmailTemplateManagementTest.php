<?php

namespace Tests\Feature;

use App\Mail\TemplatedMail;
use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\CompanyInvitationNotification;
use App\Notifications\LeadAssigned;
use App\Notifications\PasswordResetNotification;
use App\Notifications\TrialEndingNotification;
use App\Notifications\WelcomeNotification;
use App\Services\Email\EmailTemplateService;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->seed(EmailTemplateSeeder::class);
    }

    public function test_super_admin_can_list_and_preview_email_templates(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $template = EmailTemplate::query()->where('category', 'welcome')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('superadmin.email-templates.index'))
            ->assertOk()
            ->assertSee('Email Templates')
            ->assertSee('Welcome');

        $this->actingAs($admin)
            ->get(route('superadmin.email-templates.preview', $template))
            ->assertOk()
            ->assertSee('Preview');
    }

    public function test_super_admin_can_update_template_and_send_test_email(): void
    {
        Mail::fake();

        $admin = User::factory()->superAdmin()->create();
        $template = EmailTemplate::query()->where('category', 'welcome')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('superadmin.email-templates.update', $template), [
                'locale' => 'en',
                'name' => 'Welcome Updated',
                'subject' => 'Hello {{user_name}} from {{platform_name}}',
                'html_body' => '<p>Hi {{user_name}}</p>',
                'text_body' => 'Hi {{user_name}}',
                'is_active' => '1',
                'use_branding' => '1',
            ])
            ->assertRedirect();

        $this->assertSame('Welcome Updated', $template->fresh()->name);

        $this->actingAs($admin)
            ->post(route('superadmin.email-templates.test', $template), [
                'to_email' => 'qa@example.com',
            ])
            ->assertRedirect();

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) {
            return $mail->hasTo('qa@example.com');
        });
    }

    public function test_renderer_replaces_placeholders(): void
    {
        $service = app(EmailTemplateService::class);
        $rendered = $service->render('password_reset', [
            'user_name' => 'Alex',
            'user_email' => 'alex@example.com',
            'reset_url' => '/reset-password/token',
            'expires_minutes' => '60',
        ]);

        $this->assertStringContainsString('Alex', $rendered['html']);
        $this->assertStringContainsString('60', $rendered['html']);
        $this->assertNotSame('', $rendered['subject']);
    }

    public function test_password_reset_uses_templated_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, PasswordResetNotification::class);
    }

    public function test_account_activation_mailable_includes_recipient(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'activate-me@example.com',
            'name' => 'Activate Me',
        ]);

        $mail = (new \App\Notifications\AccountActivationNotification)->toMail($user);

        $this->assertInstanceOf(TemplatedMail::class, $mail);
        $this->assertTrue($mail->hasTo('activate-me@example.com'));
        $this->assertStringContainsString('Activate', $mail->envelope()->subject);
    }

    public function test_lead_assignment_can_send_mail_channel(): void
    {
        Notification::fake();

        $actor = User::factory()->admin()->create();
        $assignee = User::factory()->create(['company_id' => $actor->company_id]);
        $lead = Lead::factory()->create([
            'company_id' => $actor->company_id,
            'created_by' => $actor->id,
            'assigned_to' => $actor->id,
            'status' => 'new',
        ]);

        $this->actingAs($actor)->put(route('leads.update', $lead), [
            'name' => $lead->name,
            'status' => $lead->status,
            'assigned_to' => $assignee->id,
        ])->assertRedirect();

        Notification::assertSentTo($assignee, LeadAssigned::class, function (LeadAssigned $notification, array $channels) {
            return in_array('mail', $channels, true) && in_array('database', $channels, true);
        });
    }

    public function test_company_invitation_flow(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $roleId = $admin->roles()->first()->id;

        $this->actingAs($admin)
            ->post(route('users.invite.store'), [
                'name' => 'Jordan Lee',
                'email' => 'jordan@example.com',
                'roles' => [$roleId],
            ])
            ->assertRedirect(route('users.index'));

        $invitation = UserInvitation::query()->where('email', 'jordan@example.com')->firstOrFail();
        $this->assertTrue($invitation->isPending());

        Notification::assertSentOnDemand(CompanyInvitationNotification::class);

        $this->post(route('logout'));

        $this->post(route('invitations.accept.store', $invitation->token), [
            'password' => 'Secretpass1!',
            'password_confirmation' => 'Secretpass1!',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'jordan@example.com',
            'company_id' => $admin->company_id,
        ]);
        $this->assertSame(UserInvitation::STATUS_ACCEPTED, $invitation->fresh()->status);
    }

    public function test_trial_ending_command_notifies_owner(): void
    {
        Notification::fake();

        $company = Company::factory()->create([
            'subscription_status' => Company::SUBSCRIPTION_TRIAL,
            'trial_ends_at' => today()->addDays(3),
        ]);
        $owner = User::factory()->admin()->create(['company_id' => $company->id]);
        $company->update(['owner_id' => $owner->id]);

        $this->artisan('trials:send-ending-notifications --days=3')->assertSuccessful();

        Notification::assertSentTo($owner, TrialEndingNotification::class);
    }

    public function test_welcome_notification_after_registration_without_verification(): void
    {
        Notification::fake();

        app(\App\Services\SuperAdmin\PlatformSettingsService::class)->setMany([
            'registration_enabled' => true,
            'email_verification_required' => false,
        ]);

        $this->post(route('register'), [
            'company_name' => 'Northline',
            'name' => 'Alex Morgan',
            'email' => 'alex.welcome@example.com',
            'password' => 'Secretpass1!',
            'password_confirmation' => 'Secretpass1!',
        ])->assertRedirect(route('dashboard'));

        $user = User::withoutCompanyScope()->where('email', 'alex.welcome@example.com')->firstOrFail();
        Notification::assertSentTo($user, WelcomeNotification::class);
    }
}
