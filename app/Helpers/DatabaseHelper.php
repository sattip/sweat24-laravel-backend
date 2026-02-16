<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class DatabaseHelper
{
    /**
     * Get SQL expression for formatting a date column by the given group.
     *
     * @param string $column  The date/datetime column name
     * @param string $groupBy One of: day, week, month, year
     * @param string $alias   The SQL alias for the result
     */
    public static function dateFormat(string $column, string $groupBy = 'month', string $alias = 'period'): string
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        return match ($groupBy) {
            'day' => $isSqlite
                ? "strftime('%Y-%m-%d', {$column}) as {$alias}"
                : "DATE_FORMAT({$column}, '%Y-%m-%d') as {$alias}",
            'week' => $isSqlite
                ? "strftime('%Y-%W', {$column}) as {$alias}"
                : "DATE_FORMAT({$column}, '%x-%v') as {$alias}",
            'month' => $isSqlite
                ? "strftime('%Y-%m', {$column}) as {$alias}"
                : "DATE_FORMAT({$column}, '%Y-%m') as {$alias}",
            'year' => $isSqlite
                ? "strftime('%Y', {$column}) as {$alias}"
                : "DATE_FORMAT({$column}, '%Y') as {$alias}",
            'quarterly' => $isSqlite
                ? "strftime('%Y', {$column}) || '-Q' || ((strftime('%m', {$column}) - 1) / 3 + 1) as {$alias}"
                : "CONCAT(YEAR({$column}), '-Q', QUARTER({$column})) as {$alias}",
            default => $isSqlite
                ? "strftime('%Y-%m', {$column}) as {$alias}"
                : "DATE_FORMAT({$column}, '%Y-%m') as {$alias}",
        };
    }

    /**
     * Get SQL expression for date format without alias (for use in selectRaw with other columns).
     */
    public static function dateFormatExpr(string $column, string $groupBy = 'month'): string
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        return match ($groupBy) {
            'day' => $isSqlite ? "strftime('%Y-%m-%d', {$column})" : "DATE_FORMAT({$column}, '%Y-%m-%d')",
            'week' => $isSqlite ? "strftime('%Y-%W', {$column})" : "DATE_FORMAT({$column}, '%x-%v')",
            'month' => $isSqlite ? "strftime('%Y-%m', {$column})" : "DATE_FORMAT({$column}, '%Y-%m')",
            'year' => $isSqlite ? "strftime('%Y', {$column})" : "DATE_FORMAT({$column}, '%Y')",
            'quarterly' => $isSqlite
                ? "strftime('%Y', {$column}) || '-Q' || ((strftime('%m', {$column}) - 1) / 3 + 1)"
                : "CONCAT(YEAR({$column}), '-Q', QUARTER({$column}))",
            default => $isSqlite ? "strftime('%Y-%m', {$column})" : "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    /**
     * Get SQL expression for calculating age from a date_of_birth column.
     */
    public static function ageBetween(string $column, int $min, int $max): array
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        if ($isSqlite) {
            return [
                "(strftime('%Y', 'now') - strftime('%Y', {$column})) BETWEEN ? AND ?",
                [$min, $max],
            ];
        }

        return [
            "TIMESTAMPDIFF(YEAR, {$column}, CURDATE()) BETWEEN ? AND ?",
            [$min, $max],
        ];
    }
}
