<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CsvImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_sales_user_can_download_leads_sample_and_import_valid_rows(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('imports.sample', 'leads'))
            ->assertOk()
            ->assertHeader('content-disposition');

        $csv = $this->csvFile([
            ['name', 'email', 'phone', 'company', 'source', 'estimated_value', 'notes', 'follow_up_date', 'assigned_to'],
            ['Import Lead One', 'import-lead-1@example.com', '555-1000', 'Acme', 'website', '1000', 'Note', '2026-08-01', ''],
            ['Import Lead Two', 'import-lead-2@example.com', '555-1001', 'Beta', 'referral', '', '', '', ''],
        ]);

        $this->actingAs($user)
            ->post(route('imports.store', 'leads'), ['csv_file' => $csv])
            ->assertRedirect(route('leads.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('leads', [
            'email' => 'import-lead-1@example.com',
            'assigned_to' => $user->id,
            'status' => 'new',
        ]);
        $this->assertDatabaseHas('leads', [
            'email' => 'import-lead-2@example.com',
            'assigned_to' => $user->id,
        ]);
    }

    public function test_import_skips_invalid_and_duplicate_leads(): void
    {
        $user = User::factory()->create();

        Lead::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'email' => 'existing-lead@example.com',
            'name' => 'Existing',
        ]);

        $csv = $this->csvFile([
            ['name', 'email', 'phone', 'company', 'source', 'estimated_value', 'notes', 'follow_up_date', 'assigned_to'],
            ['', 'bad@example.com', '', '', '', '', '', '', ''],
            ['Dup Lead', 'existing-lead@example.com', '', '', '', '', '', '', ''],
            ['Good Lead', 'good-lead@example.com', '', '', 'website', '', '', '', ''],
        ]);

        $this->actingAs($user)
            ->post(route('imports.store', 'leads'), ['csv_file' => $csv])
            ->assertRedirect(route('leads.index'))
            ->assertSessionHas('success')
            ->assertSessionHas('import_errors');

        $this->assertSame(1, Lead::query()->where('email', 'good-lead@example.com')->count());
        $this->assertSame(1, Lead::query()->where('email', 'existing-lead@example.com')->count());
        $this->assertDatabaseMissing('leads', ['email' => 'bad@example.com']);
    }

    public function test_sales_user_can_import_customers(): void
    {
        $user = User::factory()->create();

        $csv = $this->csvFile([
            ['name', 'email', 'phone', 'company_name', 'address', 'notes'],
            ['Import Customer', 'import-customer@example.com', '555-2000', 'Acme', '123 St', 'VIP'],
        ]);

        $this->actingAs($user)
            ->post(route('imports.store', 'customers'), ['csv_file' => $csv])
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'email' => 'import-customer@example.com',
            'company_name' => 'Acme',
            'status' => 'active',
            'created_by' => $user->id,
        ]);
    }

    public function test_admin_can_import_users_and_sales_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $sales = User::factory()->create();

        $csv = $this->csvFile([
            ['name', 'email', 'password', 'roles', 'status'],
            ['Imported Rep', 'imported-rep@example.com', 'Password123!', 'sales', 'active'],
        ]);

        $this->actingAs($sales)
            ->post(route('imports.store', 'users'), ['csv_file' => $csv])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('imports.store', 'users'), ['csv_file' => $csv])
            ->assertRedirect(route('users.index'));

        $imported = User::query()->where('email', 'imported-rep@example.com')->first();
        $this->assertNotNull($imported);
        $this->assertTrue($imported->hasRole('sales'));
        $this->assertTrue(Hash::check('Password123!', $imported->password));
    }

    public function test_customer_import_skips_duplicate_email(): void
    {
        $user = User::factory()->create();

        Customer::factory()->create([
            'created_by' => $user->id,
            'email' => 'dup-customer@example.com',
            'name' => 'Existing Customer',
        ]);

        $csv = $this->csvFile([
            ['name', 'email', 'phone', 'company_name', 'address', 'notes'],
            ['Another', 'dup-customer@example.com', '', '', '', ''],
            ['Fresh', 'fresh-customer@example.com', '', '', '', ''],
        ]);

        $this->actingAs($user)
            ->post(route('imports.store', 'customers'), ['csv_file' => $csv])
            ->assertRedirect(route('customers.index'));

        $this->assertSame(1, Customer::query()->where('email', 'dup-customer@example.com')->count());
        $this->assertDatabaseHas('customers', ['email' => 'fresh-customer@example.com']);
    }

    public function test_customer_import_allows_different_emails_with_same_name(): void
    {
        $user = User::factory()->create();

        $csv = $this->csvFile([
            ['name', 'email', 'phone', 'company_name', 'address', 'notes'],
            ['Acme Corp', 'billing@acme.com', '-654', 'Acme Inc', '123 Main', 'VIP account'],
            ['Acme Corp', 'bolling@acme.com', '-634', 'Acme Inc', '123 Main', 'VIP account'],
            ['Acme Corp', 'biing@acme.com', '-954', 'Acme Inc', '123 Main', 'VIP account'],
        ]);

        $this->actingAs($user)
            ->post(route('imports.store', 'customers'), ['csv_file' => $csv])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('success');

        $this->assertSame(3, Customer::query()->where('name', 'Acme Corp')->count());
        $this->assertDatabaseHas('customers', ['email' => 'billing@acme.com']);
        $this->assertDatabaseHas('customers', ['email' => 'bolling@acme.com']);
        $this->assertDatabaseHas('customers', ['email' => 'biing@acme.com']);
    }

    public function test_customer_import_uses_hyperlink_display_email_and_semicolon_delimiter(): void
    {
        $user = User::factory()->create();

        $content = "name;email;phone;company_name;address;notes\n"
            ."Acme Corp;\"=HYPERLINK(\"\"mailto:billing@acme.com\"\",\"\"billing@acme.com\"\")\";-654;Acme Inc;123 Main;VIP\n"
            ."Acme Corp;\"=HYPERLINK(\"\"mailto:billing@acme.com\"\",\"\"bolling@acme.com\"\")\";-634;Acme Inc;123 Main;VIP\n"
            ."Acme Corp;mailto:biing@acme.com;-954;Acme Inc;123 Main;VIP\n";

        $csv = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->actingAs($user)
            ->post(route('imports.store', 'customers'), ['csv_file' => $csv])
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', ['email' => 'billing@acme.com']);
        $this->assertDatabaseHas('customers', ['email' => 'bolling@acme.com']);
        $this->assertDatabaseHas('customers', ['email' => 'biing@acme.com']);
        $this->assertSame(3, Customer::count());
    }

    public function test_import_form_is_available_for_permitted_types(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('imports.create', 'leads'))
            ->assertOk()
            ->assertSee('Download sample CSV');

        $this->actingAs($user)
            ->get(route('leads.index'))
            ->assertOk()
            ->assertSee('Import CSV');

        $this->actingAs($user)
            ->get(route('imports.create', 'users'))
            ->assertForbidden();
    }

    /**
     * @param  list<list<string>>  $rows
     */
    protected function csvFile(array $rows): UploadedFile
    {
        $content = collect($rows)
            ->map(fn (array $row) => collect($row)->map(function ($value) {
                $value = (string) $value;

                return str_contains($value, ',') ? '"'.$value.'"' : $value;
            })->implode(','))
            ->implode("\n");

        return UploadedFile::fake()->createWithContent('import.csv', $content."\n");
    }
}
