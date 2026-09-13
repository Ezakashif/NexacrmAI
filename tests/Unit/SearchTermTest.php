<?php

namespace Tests\Unit;

use App\Models\Lead;
use App\Models\User;
use App\Support\SearchTerm;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTermTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_like_escapes_wildcards_and_escape_character(): void
    {
        $this->assertSame('%100!%%', SearchTerm::like('100%'));
        $this->assertSame('%a!_b%', SearchTerm::like('a_b'));
        $this->assertSame('%hi!!there%', SearchTerm::like('hi!there'));
    }

    public function test_where_escaped_sql_uses_portable_escape_clause(): void
    {
        $sql = Lead::query()
            ->where(function ($query) {
                SearchTerm::whereEscaped($query, 'name', 'maxime');
            })
            ->toSql();

        $this->assertStringContainsString("LIKE ? ESCAPE '!'", $sql);
        $this->assertStringNotContainsString("ESCAPE '\\'", $sql);
    }

    public function test_search_matches_literal_percent_and_normal_terms(): void
    {
        $admin = User::factory()->admin()->create();

        Lead::factory()->assignedTo($admin)->create([
            'created_by' => $admin->id,
            'name' => '100% Closed Deal',
            'company' => 'Maxime Corp',
        ]);

        Lead::factory()->assignedTo($admin)->create([
            'created_by' => $admin->id,
            'name' => 'Other Lead',
            'company' => 'Elsewhere',
        ]);

        $percentHits = Lead::query()->search('100%')->pluck('name');
        $this->assertTrue($percentHits->contains('100% Closed Deal'));
        $this->assertFalse($percentHits->contains('Other Lead'));

        $nameHits = Lead::query()->search('maxime')->pluck('company');
        $this->assertTrue($nameHits->contains('Maxime Corp'));
    }
}
