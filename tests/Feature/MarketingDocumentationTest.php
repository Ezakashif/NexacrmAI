<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketingDocumentationTest extends TestCase
{
    public function test_guest_can_view_documentation_home(): void
    {
        $this->get(route('marketing.documentation'))
            ->assertOk()
            ->assertSee('CRM Documentation', false)
            ->assertSee('Contents', false)
            ->assertSee('Getting started', false)
            ->assertSee(route('marketing.documentation.show', ['path' => 'getting-started/installation'], false), false);
    }

    public function test_guest_can_view_nested_documentation_page(): void
    {
        $this->get(route('marketing.documentation.show', ['path' => 'getting-started/installation']))
            ->assertOk()
            ->assertSee('Installation', false)
            ->assertSee('composer install', false)
            ->assertSee(route('marketing.documentation.show', ['path' => 'getting-started/configuration'], false), false);
    }

    public function test_documentation_links_appear_on_home_pricing_and_footer(): void
    {
        $docsUrl = route('marketing.documentation');

        $this->get(route('marketing.home'))
            ->assertOk()
            ->assertSee($docsUrl, false);

        $this->get(route('marketing.pricing'))
            ->assertOk()
            ->assertSee($docsUrl, false);

        $this->get(route('marketing.about'))
            ->assertOk()
            ->assertSee($docsUrl, false);
    }

    public function test_guest_can_download_documentation_pdf(): void
    {
        $this->get(route('marketing.documentation.pdf.page', ['path' => 'README']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
