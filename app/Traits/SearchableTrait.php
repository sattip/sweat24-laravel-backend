<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait SearchableTrait
{
    /**
     * Escape special characters for LIKE patterns.
     *
     * This prevents user input from being interpreted as LIKE wildcards,
     * which could allow pattern manipulation (not SQL injection, but
     * unintended query behavior).
     *
     * @param string $value The user input value to escape
     * @param string $escapeChar The escape character to use (default: \)
     * @return string The escaped value safe for use in LIKE patterns
     */
    protected function escapeLikePattern(string $value, string $escapeChar = '\\'): string
    {
        // Escape the escape character first, then special LIKE characters
        return str_replace(
            [$escapeChar, '%', '_'],
            [$escapeChar . $escapeChar, $escapeChar . '%', $escapeChar . '_'],
            $value
        );
    }

    /**
     * Apply a safe LIKE search to a query builder.
     *
     * @param Builder $query The query builder instance
     * @param string $column The column to search
     * @param string $value The search value (will be escaped)
     * @param string $position Where to add wildcards: 'both', 'start', 'end', or 'exact'
     * @return Builder
     */
    protected function addSafeLikeWhere(Builder $query, string $column, string $value, string $position = 'both'): Builder
    {
        $escaped = $this->escapeLikePattern($value);

        $pattern = match ($position) {
            'start' => '%' . $escaped,
            'end' => $escaped . '%',
            'exact' => $escaped,
            default => '%' . $escaped . '%',
        };

        return $query->where($column, 'LIKE', $pattern);
    }

    /**
     * Apply a safe OR LIKE search to a query builder.
     *
     * @param Builder $query The query builder instance
     * @param string $column The column to search
     * @param string $value The search value (will be escaped)
     * @param string $position Where to add wildcards: 'both', 'start', 'end', or 'exact'
     * @return Builder
     */
    protected function addSafeOrLikeWhere(Builder $query, string $column, string $value, string $position = 'both'): Builder
    {
        $escaped = $this->escapeLikePattern($value);

        $pattern = match ($position) {
            'start' => '%' . $escaped,
            'end' => $escaped . '%',
            'exact' => $escaped,
            default => '%' . $escaped . '%',
        };

        return $query->orWhere($column, 'LIKE', $pattern);
    }

    /**
     * Apply a safe search across multiple columns.
     *
     * @param Builder $query The query builder instance
     * @param array $columns The columns to search
     * @param string $value The search value (will be escaped)
     * @param string $position Where to add wildcards: 'both', 'start', 'end', or 'exact'
     * @return Builder
     */
    protected function addSafeMultiColumnSearch(Builder $query, array $columns, string $value, string $position = 'both'): Builder
    {
        $escaped = $this->escapeLikePattern($value);

        $pattern = match ($position) {
            'start' => '%' . $escaped,
            'end' => $escaped . '%',
            'exact' => $escaped,
            default => '%' . $escaped . '%',
        };

        return $query->where(function ($q) use ($columns, $pattern) {
            foreach ($columns as $index => $column) {
                if ($index === 0) {
                    $q->where($column, 'LIKE', $pattern);
                } else {
                    $q->orWhere($column, 'LIKE', $pattern);
                }
            }
        });
    }
}
