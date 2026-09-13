<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class SearchTerm
{
    /**
     * Escape character for LIKE patterns. Avoid backslash — MySQL/MariaDB treat
     * \' inside string literals as an escaped quote and reject ESCAPE '\'.
     */
    public const ESCAPE = '!';

    /**
     * Build a LIKE pattern that treats user input as literals (escapes %, _, and the escape char).
     */
    public static function like(string $term): string
    {
        $escape = self::ESCAPE;

        $escaped = str_replace(
            [$escape, '%', '_'],
            [$escape.$escape, $escape.'%', $escape.'_'],
            trim($term),
        );

        return '%'.$escaped.'%';
    }

    /**
     * Apply an escaped LIKE comparison that works on MySQL/MariaDB and SQLite.
     */
    public static function whereEscaped(Builder $query, string $column, string $term, string $boolean = 'and'): Builder
    {
        $pattern = self::like($term);
        $escape = self::ESCAPE;

        $method = $boolean === 'or' ? 'orWhereRaw' : 'whereRaw';

        return $query->{$method}("{$column} LIKE ? ESCAPE '{$escape}'", [$pattern]);
    }
}
