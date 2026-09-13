<?php

namespace Tests\Unit\Services\Csv;

use App\Models\User;
use App\Services\Csv\CsvImportResult;
use App\Services\Csv\CsvImportService;
use App\Services\Csv\CsvStreamer;
use App\Services\Csv\CustomerCsvImporter;
use App\Services\Csv\LeadCsvImporter;
use App\Services\Csv\UserCsvImporter;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Mockery;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class CsvImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_import_delegates_to_leads_importer(): void
    {
        $actor = User::factory()->create();
        $file = UploadedFile::fake()->create('leads.csv');
        $expected = new CsvImportResult;
        $expected->imported = 2;

        $leads = Mockery::mock(LeadCsvImporter::class);
        $leads->shouldReceive('import')
            ->once()
            ->with($actor, $file)
            ->andReturn($expected);

        $service = new CsvImportService(
            $leads,
            Mockery::mock(CustomerCsvImporter::class),
            Mockery::mock(UserCsvImporter::class),
            new CsvStreamer,
        );

        $result = $service->import($actor, 'leads', $file);

        $this->assertSame(2, $result->imported);
    }

    public function test_import_delegates_to_customers_and_users_importers(): void
    {
        $actor = User::factory()->create();
        $file = UploadedFile::fake()->create('data.csv');
        $customerResult = new CsvImportResult;
        $customerResult->imported = 1;
        $userResult = new CsvImportResult;
        $userResult->imported = 3;

        $customers = Mockery::mock(CustomerCsvImporter::class);
        $customers->shouldReceive('import')->once()->andReturn($customerResult);

        $users = Mockery::mock(UserCsvImporter::class);
        $users->shouldReceive('import')->once()->andReturn($userResult);

        $service = new CsvImportService(
            Mockery::mock(LeadCsvImporter::class),
            $customers,
            $users,
            new CsvStreamer,
        );

        $this->assertSame(1, $service->import($actor, 'customers', $file)->imported);
        $this->assertSame(3, $service->import($actor, 'users', $file)->imported);
    }

    public function test_import_throws_for_unsupported_type(): void
    {
        $service = app(CsvImportService::class);
        $actor = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported import type [widgets].');

        $service->import($actor, 'widgets', UploadedFile::fake()->create('widgets.csv'));
    }

    public function test_headers_for_each_supported_type(): void
    {
        $service = app(CsvImportService::class);

        $this->assertSame(LeadCsvImporter::HEADERS, $service->headersFor('leads'));
        $this->assertSame(CustomerCsvImporter::HEADERS, $service->headersFor('customers'));
        $this->assertSame(UserCsvImporter::HEADERS, $service->headersFor('users'));
    }

    public function test_headers_for_throws_for_unsupported_type(): void
    {
        $service = app(CsvImportService::class);

        $this->expectException(InvalidArgumentException::class);

        $service->headersFor('unknown');
    }

    public function test_sample_rows_for_each_supported_type(): void
    {
        $service = app(CsvImportService::class);

        $this->assertCount(1, $service->sampleRowsFor('leads'));
        $this->assertSame('Jane Doe', $service->sampleRowsFor('leads')[0][0]);
        $this->assertSame('Acme Corp', $service->sampleRowsFor('customers')[0][0]);
        $this->assertSame('New Rep', $service->sampleRowsFor('users')[0][0]);
    }

    public function test_download_sample_returns_streamed_csv_response(): void
    {
        $service = app(CsvImportService::class);

        $response = $service->downloadSample('leads');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('leads-import-sample.csv', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_permission_and_route_helpers(): void
    {
        $service = app(CsvImportService::class);

        $this->assertSame('import.leads', $service->permissionSlug('leads'));
        $this->assertSame('create.customers', $service->createPermissionSlug('customers'));
        $this->assertSame('users.index', $service->indexRoute('users'));
    }

    public function test_label_returns_friendly_names(): void
    {
        $service = app(CsvImportService::class);

        $this->assertSame('Leads', $service->label('leads'));
        $this->assertSame('Customers', $service->label('customers'));
        $this->assertSame('Users', $service->label('users'));
        $this->assertSame('Widgets', $service->label('widgets'));
    }

    public function test_types_constant_lists_supported_imports(): void
    {
        $this->assertSame(['leads', 'customers', 'users'], CsvImportService::TYPES);
    }
}
