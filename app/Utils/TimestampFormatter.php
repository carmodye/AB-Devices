<?php

namespace App\Utils;

use Carbon\Carbon;

class TimestampFormatter
{
    /**
     * Format a Unix timestamp (seconds or milliseconds) into a localized datetime string.
     *
     * @param int|string $timestamp  Unix timestamp in seconds or milliseconds
     * @param string     $timezone   Valid IANA timezone (e.g. 'America/New_York')
     * @param string     $format     Output format (default: 'Y/m/d H:i:s')
     * @return string
     */
    public static function format($timestamp, string $timezone, string $format = 'y/m/d H:i:s'): string
    {
        $normalized = strlen((string) $timestamp) > 10
            ? intval($timestamp / 1000)
            : intval($timestamp);

        return Carbon::createFromTimestampUTC($normalized)
            ->setTimezone($timezone)
            ->format($format);
    }

    /**
     * Format and return a Carbon instance (for chaining or diagnostics).
     *
     * @param int|string $timestamp
     * @param string     $timezone
     * @return \Carbon\Carbon
     */
    public static function carbon($timestamp, string $timezone): Carbon
    {
        $normalized = strlen((string) $timestamp) > 10
            ? intval($timestamp / 1000)
            : intval($timestamp);

        return Carbon::createFromTimestampUTC($normalized)
            ->setTimezone($timezone);
    }
}