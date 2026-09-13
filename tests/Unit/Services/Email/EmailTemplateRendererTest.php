<?php

namespace Tests\Unit\Services\Email;

use App\Services\Email\EmailTemplateRenderer;
use App\Services\SuperAdmin\PlatformSettingsService;
use Tests\TestCase;

class EmailTemplateRendererTest extends TestCase
{
    private EmailTemplateRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(PlatformSettingsService::class, function ($mock): void {
            $mock->shouldReceive('platformName')->andReturn('Acme CRM');
            $mock->shouldReceive('logoUrl')->andReturn(null);
            $mock->shouldReceive('get')
                ->with('mail_from_address')
                ->andReturn('support@acme.test');
        });

        $this->renderer = app(EmailTemplateRenderer::class);
    }

    public function test_replace_substitutes_tight_placeholder_syntax(): void
    {
        $content = 'Hello {{user_name}}, welcome to {{platform_name}}.';

        $result = $this->renderer->replace($content, [
            'user_name' => 'Ada',
            'platform_name' => 'Acme CRM',
        ]);

        $this->assertSame('Hello Ada, welcome to Acme CRM.', $result);
    }

    public function test_replace_substitutes_spaced_placeholder_syntax(): void
    {
        $content = 'Hi {{ user_name }}!';

        $result = $this->renderer->replace($content, [
            'user_name' => 'Grace',
        ]);

        $this->assertSame('Hi Grace!', $result);
    }

    public function test_replace_stringifies_null_as_empty_string(): void
    {
        $result = $this->renderer->replace('Value: {{missing}}', [
            'missing' => null,
        ]);

        $this->assertSame('Value: ', $result);
    }

    public function test_replace_stringifies_booleans_as_one_and_zero(): void
    {
        $result = $this->renderer->replace('{{enabled}} / {{disabled}}', [
            'enabled' => true,
            'disabled' => false,
        ]);

        $this->assertSame('1 / 0', $result);
    }

    public function test_replace_stringifies_numeric_values(): void
    {
        $result = $this->renderer->replace('Count: {{count}}', [
            'count' => 42,
        ]);

        $this->assertSame('Count: 42', $result);
    }

    public function test_wrap_branded_renders_subject_body_and_platform_name(): void
    {
        $html = $this->renderer->wrapBranded(
            '<p>Welcome aboard.</p>',
            'Welcome to Acme',
            true,
        );

        $this->assertStringContainsString('<title>Welcome to Acme</title>', $html);
        $this->assertStringContainsString('<p>Welcome aboard.</p>', $html);
        $this->assertStringContainsString('Acme CRM', $html);
        $this->assertStringContainsString('support@acme.test', $html);
    }

    public function test_wrap_branded_can_disable_branding_shell(): void
    {
        $html = $this->renderer->wrapBranded(
            '<p>Plain body.</p>',
            'Plain subject',
            false,
        );

        $this->assertStringContainsString('<p>Plain body.</p>', $html);
        $this->assertStringNotContainsString('support@acme.test', $html);
    }
}
