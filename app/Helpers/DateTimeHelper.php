<?php

if (! function_exists('format_datetime')) {
    /**
     * Format datetime to Indonesian timezone with English language
     *
     * @param  string|Carbon\Carbon|\Illuminate\Support\Carbon|null  $date
     * @param  string  $format  Default: 'd M Y H:i:s' (e.g., 22 Nov 2025 14:30:45)
     */
    function format_datetime($date, string $format = 'd M Y H:i:s'): string
    {
        if (empty($date)) {
            return '-';
        }

        return \Carbon\Carbon::parse($date)
            ->timezone('Asia/Jakarta')
            ->format($format);
    }
}

if (! function_exists('format_date')) {
    /**
     * Format date only (no time)
     *
     * @param  string|Carbon\Carbon|\Illuminate\Support\Carbon|null  $date
     * @param  string  $format  Default: 'd M Y' (e.g., 22 Nov 2025)
     */
    function format_date($date, string $format = 'd M Y'): string
    {
        if (empty($date)) {
            return '-';
        }

        return \Carbon\Carbon::parse($date)
            ->timezone('Asia/Jakarta')
            ->format($format);
    }
}

if (! function_exists('format_time')) {
    /**
     * Format time only (no date)
     *
     * @param  string|Carbon\Carbon|\Illuminate\Support\Carbon|null  $date
     * @param  string  $format  Default: 'H:i:s' (e.g., 14:30:45)
     */
    function format_time($date, string $format = 'H:i:s'): string
    {
        if (empty($date)) {
            return '-';
        }

        return \Carbon\Carbon::parse($date)
            ->timezone('Asia/Jakarta')
            ->format($format);
    }
}

if (! function_exists('format_datetime_human')) {
    /**
     * Format datetime in human readable format
     *
     * @param  string|Carbon\Carbon|\Illuminate\Support\Carbon|null  $date
     * @return string (e.g., "2 hours ago", "in 3 days")
     */
    function format_datetime_human($date): string
    {
        if (empty($date)) {
            return '-';
        }

        return \Carbon\Carbon::parse($date)
            ->timezone('Asia/Jakarta')
            ->diffForHumans();
    }
}

if (! function_exists('now_jakarta')) {
    /**
     * Get current datetime in Jakarta timezone
     */
    function now_jakarta(): \Carbon\Carbon
    {
        return \Carbon\Carbon::now('Asia/Jakarta');
    }
}

if (! function_exists('today_jakarta')) {
    /**
     * Get today's date in Jakarta timezone
     */
    function today_jakarta(): \Carbon\Carbon
    {
        return \Carbon\Carbon::today('Asia/Jakarta');
    }
}
