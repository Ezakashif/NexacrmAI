<?php

namespace App\Services\Csv;

class CsvValueNormalizer
{
    /**
     * Normalize CSV email cells from Excel/Google Sheets quirks.
     */
    public static function email(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // =HYPERLINK("mailto:target@x.com","display@x.com") — prefer display text.
        if (preg_match('/HYPERLINK\s*\(\s*"mailto:[^"]*"\s*,\s*"([^"]+)"\s*\)/i', $value, $matches)) {
            $value = $matches[1];
        } elseif (preg_match('/mailto:([^\s"?]+)/i', $value, $matches)) {
            $value = rawurldecode($matches[1]);
        }

        $value = trim($value, " \t\"'");

        // If the cell still contains junk, extract the first email-looking token.
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $value, $matches)) {
            $value = $matches[0];
        }

        $value = mb_strtolower(trim($value));

        return $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, string>  $data
     * @param  array<string, string>  $aliases  alias => canonical
     * @return array<string, string>
     */
    public static function applyHeaderAliases(array $data, array $aliases): array
    {
        foreach ($aliases as $alias => $canonical) {
            if (! array_key_exists($canonical, $data) || trim((string) $data[$canonical]) === '') {
                if (array_key_exists($alias, $data) && trim((string) $data[$alias]) !== '') {
                    $data[$canonical] = $data[$alias];
                }
            }
        }

        return $data;
    }
}
