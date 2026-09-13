<?php

namespace Tests\Unit\Services\Csv;

use App\Services\Csv\CsvImportResult;
use Tests\TestCase;

class CsvImportResultTest extends TestCase
{
    public function test_add_error_increments_skipped_invalid_and_records_row(): void
    {
        $result = new CsvImportResult;

        $result->addError(2, 'Name is required.');
        $result->addError(5, 'Invalid email.');

        $this->assertSame(2, $result->skippedInvalid);
        $this->assertSame(0, $result->skippedDuplicates);
        $this->assertSame([
            ['row' => 2, 'message' => 'Name is required.'],
            ['row' => 5, 'message' => 'Invalid email.'],
        ], $result->errors);
    }

    public function test_add_duplicate_increments_skipped_duplicates_and_records_row(): void
    {
        $result = new CsvImportResult;

        $result->addDuplicate(3, 'Duplicate email.');

        $this->assertSame(0, $result->skippedInvalid);
        $this->assertSame(1, $result->skippedDuplicates);
        $this->assertSame([
            ['row' => 3, 'message' => 'Duplicate email.'],
        ], $result->errors);
    }

    public function test_total_processed_sums_imported_skipped_duplicates_and_invalid(): void
    {
        $result = new CsvImportResult;
        $result->imported = 4;
        $result->addDuplicate(2, 'Duplicate.');
        $result->addError(3, 'Invalid.');
        $result->addError(4, 'Invalid again.');

        $this->assertSame(7, $result->totalProcessed());
    }

    public function test_total_processed_is_zero_for_fresh_result(): void
    {
        $result = new CsvImportResult;

        $this->assertSame(0, $result->totalProcessed());
    }

    public function test_summary_message_formats_counts(): void
    {
        $result = new CsvImportResult;
        $result->imported = 10;
        $result->skippedDuplicates = 2;
        $result->skippedInvalid = 3;

        $this->assertSame(
            'Lead import complete: 10 imported, 2 skipped (duplicates), 3 skipped (invalid).',
            $result->summaryMessage('Lead'),
        );
    }

    public function test_summary_message_uses_entity_label(): void
    {
        $result = new CsvImportResult;
        $result->imported = 1;

        $this->assertSame(
            'Customer import complete: 1 imported, 0 skipped (duplicates), 0 skipped (invalid).',
            $result->summaryMessage('Customer'),
        );
    }
}
