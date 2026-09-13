<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $locale = config('email_templates.default_locale', 'en');
        $platform = config('app.name', 'Algos');

        $templates = [
            'welcome' => [
                'name' => 'Welcome',
                'subject' => 'Welcome to {{platform_name}}, {{user_name}}!',
                'html_body' => '<p>Hi {{user_name}},</p><p>Your workspace <strong>{{company_name}}</strong> is ready on {{platform_name}}.</p><p><a href="{{login_url}}">Sign in to get started</a></p><p>Need help? Contact {{support_email}}.</p>',
                'text_body' => "Hi {{user_name}},\n\nYour workspace {{company_name}} is ready on {{platform_name}}.\n\nSign in: {{login_url}}\n\nNeed help? Contact {{support_email}}.",
            ],
            'password_reset' => [
                'name' => 'Password Reset',
                'subject' => 'Reset your {{platform_name}} password',
                'html_body' => '<p>Hi {{user_name}},</p><p>We received a request to reset the password for {{user_email}}.</p><p><a href="{{reset_url}}">Reset password</a></p><p>This link expires in {{expires_minutes}} minutes. If you did not request this, you can ignore this email.</p>',
                'text_body' => "Hi {{user_name}},\n\nReset your password: {{reset_url}}\n\nThis link expires in {{expires_minutes}} minutes.",
            ],
            'account_activation' => [
                'name' => 'Account Activation',
                'subject' => 'Activate your {{platform_name}} account',
                'html_body' => '<p>Hi {{user_name}},</p><p>Please confirm {{user_email}} to activate your account.</p><p><a href="{{activation_url}}">Activate account</a></p><p>This link expires in {{expires_minutes}} minutes.</p>',
                'text_body' => "Hi {{user_name}},\n\nActivate your account: {{activation_url}}\n\nThis link expires in {{expires_minutes}} minutes.",
            ],
            'company_invitation' => [
                'name' => 'Company Invitation',
                'subject' => '{{inviter_name}} invited you to {{company_name}} on {{platform_name}}',
                'html_body' => '<p>Hi {{invitee_name}},</p><p>{{inviter_name}} invited you to join <strong>{{company_name}}</strong> as {{role_names}}.</p><p><a href="{{invitation_url}}">Accept invitation</a></p><p>This invitation expires on {{expires_at}}.</p>',
                'text_body' => "Hi {{invitee_name}},\n\n{{inviter_name}} invited you to join {{company_name}} as {{role_names}}.\n\nAccept: {{invitation_url}}\n\nExpires: {{expires_at}}",
            ],
            'user_credentials' => [
                'name' => 'User Credentials',
                'subject' => 'Your {{company_name}} account on {{platform_name}}',
                'html_body' => '<p>Hi {{user_name}},</p><p>An account was created for you on <strong>{{company_name}}</strong> with the role(s): {{role_names}}.</p><p>Email: <strong>{{user_email}}</strong><br>Temporary password: <strong>{{temporary_password}}</strong></p><p><a href="{{login_url}}">Sign in</a></p><p>Please change your password after signing in. Need help? Contact {{support_email}}.</p>',
                'text_body' => "Hi {{user_name}},\n\nAn account was created for you on {{company_name}} with the role(s): {{role_names}}.\n\nEmail: {{user_email}}\nTemporary password: {{temporary_password}}\n\nSign in: {{login_url}}\n\nPlease change your password after signing in.",
            ],
            'lead_assigned' => [
                'name' => 'Lead Assigned',
                'subject' => 'Lead assigned: {{lead_name}}',
                'html_body' => '<p>Hi {{user_name}},</p><p>You have been assigned the lead <strong>{{lead_name}}</strong>.</p><p>Company: {{lead_company}}<br>Email: {{lead_email}}<br>Phone: {{lead_phone}}</p><p><a href="{{lead_url}}">View lead</a></p>',
                'text_body' => "Hi {{user_name}},\n\nYou have been assigned the lead {{lead_name}}.\nCompany: {{lead_company}}\nEmail: {{lead_email}}\nPhone: {{lead_phone}}\n\nView: {{lead_url}}",
            ],
            'task_reminder' => [
                'name' => 'Task Reminder',
                'subject' => '{{tier_label}}: {{task_title}}',
                'html_body' => '<p>Hi {{user_name}},</p><p>{{tier_label}} for task <strong>{{task_title}}</strong> (due {{due_date}}).</p><p><a href="{{task_url}}">View task</a></p>',
                'text_body' => "Hi {{user_name}},\n\n{{tier_label}} for task {{task_title}} (due {{due_date}}).\n\nView: {{task_url}}",
            ],
            'trial_ending' => [
                'name' => 'Trial Ending',
                'subject' => 'Your {{platform_name}} trial ends in {{days_remaining}} days',
                'html_body' => '<p>Hi {{user_name}},</p><p>The free trial for <strong>{{company_name}}</strong> ends on {{trial_ends_at}} ({{days_remaining}} days remaining).</p><p><a href="{{billing_url}}">Review your workspace</a></p>',
                'text_body' => "Hi {{user_name}},\n\nThe free trial for {{company_name}} ends on {{trial_ends_at}} ({{days_remaining}} days remaining).\n\nReview: {{billing_url}}",
            ],
            'lead_follow_up' => [
                'name' => 'Lead Follow-Up',
                'subject' => '{{subject_line}}: {{lead_name}}',
                'html_body' => '<p>Hi {{user_name}},</p><p>{{body_line}}</p><p>Lead: {{lead_name}}<br>Company: {{lead_company}}<br>Phone: {{lead_phone}}<br>Email: {{lead_email}}<br>Follow-up: {{follow_up_date}}</p><p><a href="{{lead_url}}">View lead</a></p>',
                'text_body' => "Hi {{user_name}},\n\n{{body_line}}\n\nLead: {{lead_name}}\nCompany: {{lead_company}}\nPhone: {{lead_phone}}\nEmail: {{lead_email}}\nFollow-up: {{follow_up_date}}\n\nView: {{lead_url}}",
            ],
            'user_status_changed' => [
                'name' => 'User Status Changed',
                'subject' => 'Your {{platform_name}} account status was updated',
                'html_body' => '<p>Hi {{user_name}},</p><p>Your account status was changed from <strong>{{old_status}}</strong> to <strong>{{new_status}}</strong> by {{changed_by}}.</p><p>If you have questions, contact your administrator.</p>',
                'text_body' => "Hi {{user_name}},\n\nYour account status was changed from {{old_status}} to {{new_status}} by {{changed_by}}.\n\nIf you have questions, contact your administrator.",
            ],
            'contact_inquiry' => [
                'name' => 'Contact Inquiry',
                'subject' => '{{platform_name}} contact inquiry from {{inquiry_name}}',
                'html_body' => '<p>New contact inquiry from the marketing website.</p><p>Name: {{inquiry_name}}<br>Email: {{inquiry_email}}<br>Company: {{inquiry_company}}<br>Phone: {{inquiry_phone}}<br>Intent: {{inquiry_intent}}</p><p>Message:</p><p>{{inquiry_message}}</p>',
                'text_body' => "New contact inquiry from {{platform_name}}\n\nName: {{inquiry_name}}\nEmail: {{inquiry_email}}\nCompany: {{inquiry_company}}\nPhone: {{inquiry_phone}}\nIntent: {{inquiry_intent}}\n\nMessage:\n{{inquiry_message}}",
            ],
        ];

        foreach ($templates as $category => $data) {
            EmailTemplate::query()->updateOrCreate(
                [
                    'category' => $category,
                    'locale' => $locale,
                ],
                [
                    'slug' => $category,
                    'name' => $data['name'],
                    'subject' => $data['subject'],
                    'html_body' => $data['html_body'],
                    'text_body' => $data['text_body'],
                    'placeholders' => config("email_templates.categories.{$category}.placeholders", []),
                    'is_active' => true,
                    'use_branding' => $category !== 'contact_inquiry',
                    'version' => 1,
                ]
            );
        }

        unset($platform);
    }
}
