<?php

namespace App\Services\Csv;

class CsvImportResult
{
    public int $imported = 0;

    public int $skippedDuplicates = 0;

    public int $skippedInvalid = 0;

    /** @var list<array{row: int, message: string}> */
    public array $errors = [];

    public function addError(int $row, string $message): void
    {
        $this->skippedInvalid++;
        $this->errors[] = [
            'row' => $row,
            'message' => $message,
        ];
    }

    public function addDuplicate(int $row, string $message): void
    {
        $this->skippedDuplicates++;
        $this->errors[] = [
            'row' => $row,
            'message' => $message,
        ];
    }

    public function totalProcessed(): int
    {
        return $this->imported + $this->skippedDuplicates + $this->skippedInvalid;
    }

    public function summaryMessage(string $entityLabel): string
    {
        return sprintf(
            '%s import complete: %d imported, %d skipped (duplicates), %d skipped (invalid).',
            $entityLabel,
            $this->imported,
            $this->skippedDuplicates,
            $this->skippedInvalid,
        );
    }
}
