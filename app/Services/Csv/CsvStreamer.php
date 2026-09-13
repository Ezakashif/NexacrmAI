<?php

namespace App\Services\Csv;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvStreamer
{
    /**
     * Stream a CSV download. Rows may be arrays or array-like values.
     *
     * @param  list<string>  $headers
     * @param  iterable<int, array<int|string, mixed>>  $rows
     */
    public function download(string $filename, array $headers, iterable $rows, bool $withBom = true): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows, $withBom) {
            $handle = fopen('php://output', 'w');

            if ($withBom) {
                fwrite($handle, "\xEF\xBB\xBF");
            }

            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, array_values((array) $row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
