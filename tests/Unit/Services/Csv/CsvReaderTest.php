<?php

namespace Tests\Unit\Services\Csv;

use App\Services\Csv\CsvReader;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\TestCase;

class CsvReaderTest extends TestCase
{
    private CsvReader $reader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reader = new CsvReader;
    }

    public function test_read_parses_comma_delimited_csv(): void
    {
        $file = $this->csvFile(
            "name,email,phone\nJane Doe,jane@example.com,555-0100\n"
        );

        $result = $this->reader->read($file);

        $this->assertSame(['name', 'email', 'phone'], $result['headers']);
        $this->assertCount(1, $result['rows']);
        $this->assertSame(2, $result['rows'][0]['row']);
        $this->assertSame([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '555-0100',
        ], $result['rows'][0]['data']);
    }

    public function test_read_normalizes_headers(): void
    {
        $file = $this->csvFile(
            "Full Name,E-Mail Address,Follow Up Date\nJane,jane@example.com,2026-08-01\n"
        );

        $result = $this->reader->read($file);

        $this->assertSame(['full_name', 'e_mail_address', 'follow_up_date'], $result['headers']);
    }

    public function test_read_detects_semicolon_delimiter(): void
    {
        $file = $this->csvFile(
            "name;email;phone\nJane Doe;jane@example.com;555-0100\n"
        );

        $result = $this->reader->read($file);

        $this->assertSame('Jane Doe', $result['rows'][0]['data']['name']);
        $this->assertSame('jane@example.com', $result['rows'][0]['data']['email']);
    }

    public function test_read_detects_tab_delimiter(): void
    {
        $file = $this->csvFile(
            "name\temail\tphone\nJane Doe\tjane@example.com\t555-0100\n"
        );

        $result = $this->reader->read($file);

        $this->assertSame('Jane Doe', $result['rows'][0]['data']['name']);
    }

    public function test_read_strips_utf8_bom_from_headers(): void
    {
        $file = $this->csvFile(
            "\xEF\xBB\xBFname,email\nJane,jane@example.com\n"
        );

        $result = $this->reader->read($file);

        $this->assertSame(['name', 'email'], $result['headers']);
    }

    public function test_read_converts_utf16_le_csv(): void
    {
        $content = "\xFF\xFE".mb_convert_encoding("name,email\nJane,jane@example.com\n", 'UTF-16LE', 'UTF-8');
        $file = UploadedFile::fake()->createWithContent('utf16.csv', $content);

        $result = $this->reader->read($file);

        $this->assertSame(['name', 'email'], $result['headers']);
        $this->assertSame('Jane', $result['rows'][0]['data']['name']);
    }

    public function test_read_skips_empty_rows(): void
    {
        $file = $this->csvFile(
            "name,email\nJane,jane@example.com\n,,\nJohn,john@example.com\n"
        );

        $result = $this->reader->read($file);

        $this->assertCount(2, $result['rows']);
        $this->assertSame('Jane', $result['rows'][0]['data']['name']);
        $this->assertSame('John', $result['rows'][1]['data']['name']);
    }

    public function test_read_trims_cell_values_and_fills_missing_columns(): void
    {
        $file = $this->csvFile(
            "name,email,phone\n  Jane  ,jane@example.com\n"
        );

        $result = $this->reader->read($file);

        $this->assertSame('Jane', $result['rows'][0]['data']['name']);
        $this->assertSame('', $result['rows'][0]['data']['phone']);
    }

    public function test_read_throws_when_file_is_empty(): void
    {
        $file = UploadedFile::fake()->createWithContent('empty.csv', '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The CSV file is empty or missing a header row.');

        $this->reader->read($file);
    }

    public function test_read_throws_when_headers_are_duplicate(): void
    {
        $file = $this->csvFile(
            "name,name,email\nJane,Jane,jane@example.com\n"
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The CSV header row is invalid or contains duplicate columns.');

        $this->reader->read($file);
    }

    public function test_read_throws_when_header_is_blank(): void
    {
        $file = $this->csvFile(
            "name,,email\nJane,x,jane@example.com\n"
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The CSV header row is invalid or contains duplicate columns.');

        $this->reader->read($file);
    }

    public function test_read_throws_when_row_limit_exceeded(): void
    {
        $lines = ['name,email'];
        for ($i = 1; $i <= CsvReader::MAX_ROWS + 1; $i++) {
            $lines[] = "User {$i},user{$i}@example.com";
        }

        $file = $this->csvFile(implode("\n", $lines)."\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CSV imports are limited to '.CsvReader::MAX_ROWS.' data rows.');

        $this->reader->read($file);
    }

    public function test_read_allows_exactly_max_rows(): void
    {
        $lines = ['name,email'];
        for ($i = 1; $i <= CsvReader::MAX_ROWS; $i++) {
            $lines[] = "User {$i},user{$i}@example.com";
        }

        $file = $this->csvFile(implode("\n", $lines)."\n");
        $result = $this->reader->read($file);

        $this->assertCount(CsvReader::MAX_ROWS, $result['rows']);
    }

    private function csvFile(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('import.csv', $content);
    }
}
