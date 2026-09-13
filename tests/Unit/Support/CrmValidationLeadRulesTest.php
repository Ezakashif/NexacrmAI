<?php

namespace Tests\Unit\Support;

use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use App\Support\CrmValidation;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CrmValidationLeadRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_lead_store_rules_require_name_and_allow_optional_fields(): void
    {
        $user = User::factory()->create();

        $validator = Validator::make([
            'name' => 'Valid Lead',
            'email' => 'lead@example.com',
            'source' => 'website',
            'estimated_value' => 1500,
            'follow_up_date' => today()->toDateString(),
        ], CrmValidation::leadStoreRules($user));

        $this->assertFalse($validator->fails());
    }

    public function test_lead_store_rules_reject_invalid_source_and_negative_value(): void
    {
        $user = User::factory()->create();

        $validator = Validator::make([
            'name' => 'Bad Lead',
            'source' => 'billboard',
            'estimated_value' => -5,
        ], CrmValidation::leadStoreRules($user));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('source', $validator->errors()->toArray());
        $this->assertArrayHasKey('estimated_value', $validator->errors()->toArray());
    }

    public function test_sales_user_rules_omit_assigned_to(): void
    {
        $user = User::factory()->create();
        $rules = CrmValidation::leadStoreRules($user);

        $this->assertArrayNotHasKey('assigned_to', $rules);
    }

    public function test_admin_rules_include_assigned_to_exists_in_company(): void
    {
        $admin = User::factory()->admin()->create();
        $assignee = User::factory()->create(['company_id' => $admin->company_id]);

        $rules = CrmValidation::leadStoreRules($admin);
        $this->assertArrayHasKey('assigned_to', $rules);

        $passes = Validator::make([
            'name' => 'Assigned Lead',
            'assigned_to' => $assignee->id,
        ], $rules);

        $this->assertFalse($passes->fails());

        $foreign = User::factory()->create(['company_id' => Company::factory()->create()->id]);

        $fails = Validator::make([
            'name' => 'Bad Assignee Lead',
            'assigned_to' => $foreign->id,
        ], $rules);

        $this->assertTrue($fails->fails());
        $this->assertArrayHasKey('assigned_to', $fails->errors()->toArray());
    }

    public function test_import_rules_validate_assigned_to_by_email_in_company(): void
    {
        $admin = User::factory()->admin()->create();
        $assignee = User::factory()->create([
            'company_id' => $admin->company_id,
            'email' => 'assignee@example.com',
        ]);

        $rules = CrmValidation::leadStoreRules($admin, forImport: true);

        $passes = Validator::make([
            'name' => 'Imported Lead',
            'assigned_to' => $assignee->email,
        ], $rules);

        $this->assertFalse($passes->fails());

        $fails = Validator::make([
            'name' => 'Imported Lead',
            'assigned_to' => 'missing@example.com',
        ], $rules);

        $this->assertTrue($fails->fails());
        $this->assertArrayHasKey('assigned_to', $fails->errors()->toArray());
    }

    public function test_lead_sources_match_model_constants(): void
    {
        $user = User::factory()->create();
        $rules = CrmValidation::leadStoreRules($user);

        $this->assertStringContainsString(implode(',', Lead::SOURCES), $rules['source']);
    }
}
