<?php

namespace App\Model;

use stdClass;

class Scenario
{
    public array $data;

    public function __construct(
        public readonly string $name,
    ) {
    }

    public static function normalizeName(
        string $name,
    ): string {
        return trim($name);
    }

    public function addData(
        array $data,
    ): void {
        $this->data[] = $data;
    }

    /**
     * Sanitize a value from the dataset so it is safe for numeric operations.
     *
     * Moodle's performance info can include non-breaking spaces (\u00A0) and
     * unit suffixes (e.g. "569\u00A0bytes", "4.4\u00A0KB"). This method
     * strips everything that isn't part of a valid number, returning a
     * clean numeric string or null if nothing useful remains.
     */
    private function sanitizeValue(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        // Strip non-breaking spaces (\xC2\xA0 in UTF-8) and regular whitespace.
        $cleaned = preg_replace('/[\x{00A0}\s]+/u', ' ', $value);
        // Extract the leading numeric portion (int or float).
        if (preg_match('/^[\s]*(-?\d+(?:\.\d+)?)/', $cleaned, $m)) {
            return (float) $m[1];
        }

        return null;
    }

    public function getAverage(
        string $key,
    ): float {
        $total = 0;
        $count = 0;

        foreach ($this->data as $data) {
            if (!isset($data[$key])) {
                continue;
            }

            $numericValue = $this->sanitizeValue($data[$key]);
            if ($numericValue === null) {
                continue;
            }

            $total += $numericValue;
            $count++;
        }

        if ($count === 0) {
            return 0.0;
        }

        return $total / $count;
    }

    public function getTotal(
        string $key,
    ): float {
        $total = 0;

        foreach ($this->data as $data) {
            if (!isset($data[$key])) {
                continue;
            }

            $numericValue = $this->sanitizeValue($data[$key]);
            if ($numericValue === null) {
                continue;
            }

            $total += $numericValue;
        }

        return $total;
    }
}
