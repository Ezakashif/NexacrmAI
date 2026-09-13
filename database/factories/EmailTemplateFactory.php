<?php

namespace Database\Factories;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    public function definition(): array
    {
        $category = 'welcome';

        return [
            'category' => $category,
            'slug' => $category.'-'.fake()->unique()->numerify('###'),
            'locale' => 'en',
            'name' => 'Welcome',
            'subject' => 'Welcome to {{platform_name}}, {{user_name}}!',
            'html_body' => '<p>Hi {{user_name}},</p><p>Welcome to {{platform_name}}.</p>',
            'text_body' => 'Hi {{user_name}}, welcome to {{platform_name}}.',
            'placeholders' => config('email_templates.categories.welcome.placeholders', []),
            'is_active' => true,
            'use_branding' => true,
            'version' => 1,
        ];
    }
}
