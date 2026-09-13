<?php

namespace App\Services\Csv;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class CsvReader
{
    public const MAX_ROWS = 500;

    /**
     * @return array{headers: list<string>, rows: list<array{row: int, data: array<string, string>}>}
     */
    public function read(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if ($path === false) {
            throw new RuntimeException('Unable to open the uploaded CSV file.');
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('Unable to read the uploaded CSV file.');
        }

        // Excel sometimes saves UTF-16 CSV files.
        if (str_starts_with($contents, "\xFF\xFE") || str_starts_with($contents, "\xFE\xFF")) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'UTF-16');
        }

        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new RuntimeException('Unable to open a temporary stream for CSV parsing.');
        }

        try {
            fwrite($handle, $contents);
            rewind($handle);

            $firstLine = fgets($handle);
            if ($firstLine === false) {
                throw new RuntimeException('The CSV file is empty or missing a header row.');
            }

            $delimiter = $this->detectDelimiter($firstLine);
            rewind($handle);

            $headerRow = fgetcsv($handle, 0, $delimiter);

            if ($headerRow === false || $headerRow === [null] || $headerRow === []) {
                throw new RuntimeException('The CSV file is empty or missing a header row.');
            }

            $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $headerRow);

            if (in_array('', $headers, true) || count($headers) !== count(array_unique($headers))) {
                throw new RuntimeException('The CSV header row is invalid or contains duplicate columns.');
            }

            $rows = [];
            $rowNumber = 1;

            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNumber++;

                if ($this->rowIsEmpty($data)) {
                    continue;
                }

                if (count($rows) >= self::MAX_ROWS) {
                    throw new RuntimeException('CSV imports are limited to '.self::MAX_ROWS.' data rows.');
                }

                $assoc = [];
                foreach ($headers as $index => $header) {
                    $assoc[$header] = isset($data[$index]) ? trim((string) $data[$index]) : '';
                }

                $rows[] = [
                    'row' => $rowNumber,
                    'data' => $assoc,
                ];
            }

            return [
                'headers' => $headers,
                'rows' => $rows,
            ];
        } finally {
            fclose($handle);
        }
    }

    protected function detectDelimiter(string $line): string
    {
        $comma = substr_count($line, ',');
        $semicolon = substr_count($line, ';');
        $tab = substr_count($line, "\t");

        if ($semicolon > $comma && $semicolon >= $tab) {
            return ';';
        }

        if ($tab > $comma && $tab > $semicolon) {
            return "\t";
        }

        return ',';
    }

    protected function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
        $header = strtolower(trim($header));
        $header = str_replace([' ', '-'], '_', $header);

        return $header;
    }

    /**
     * @param  list<string|null>|false  $data
     */
    protected function rowIsEmpty(array|false $data): bool
    {
        if ($data === false) {
            return true;
        }

        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
