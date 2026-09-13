<?php

namespace Tests\Unit\Support;

use App\Models\Company;
use App\Support\CurrentCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CurrentCompanyTest extends TestCase
{
    use RefreshDatabase;

    private CurrentCompany $currentCompany;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currentCompany = app(CurrentCompany::class);
        $this->currentCompany->clear();
    }

    protected function tearDown(): void
    {
        $this->currentCompany->clear();

        parent::tearDown();
    }

    public function test_starts_unset(): void
    {
        $this->assertFalse($this->currentCompany->check());
        $this->assertNull($this->currentCompany->id());
        $this->assertNull($this->currentCompany->get());
    }

    public function test_set_with_company_instance(): void
    {
        $company = Company::factory()->make(['id' => 99]);

        $this->currentCompany->set($company);

        $this->assertTrue($this->currentCompany->check());
        $this->assertSame(99, $this->currentCompany->id());
        $this->assertSame($company, $this->currentCompany->get());
        $this->assertSame(99, $this->currentCompany->require());
    }

    public function test_set_with_company_id_loads_from_database(): void
    {
        $company = Company::factory()->create();

        $this->currentCompany->set($company->id);

        $this->assertSame($company->id, $this->currentCompany->id());
        $this->assertTrue($company->is($this->currentCompany->get()));
    }

    public function test_set_with_null_clears_context(): void
    {
        $company = Company::factory()->make(['id' => 1]);
        $this->currentCompany->set($company);

        $this->currentCompany->set(null);

        $this->assertFalse($this->currentCompany->check());
        $this->assertNull($this->currentCompany->id());
        $this->assertNull($this->currentCompany->get());
    }

    public function test_clear_resets_id_and_cached_company(): void
    {
        $company = Company::factory()->make(['id' => 5]);
        $this->currentCompany->set($company);

        $this->currentCompany->clear();

        $this->assertFalse($this->currentCompany->check());
        $this->assertNull($this->currentCompany->get());
    }

    public function test_require_throws_when_unset(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No current company is set.');

        $this->currentCompany->require();
    }

    public function test_is_singleton_within_container(): void
    {
        $first = app(CurrentCompany::class);
        $second = app(CurrentCompany::class);

        $this->assertSame($first, $second);
    }
}
