<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait HasTrackingCode
{
    /**
     * Generate tracking code dengan format profesional
     * Format: PREFIX-YYYY-NNNN
     *
     * @param string $prefix Contoh: 'INB', 'OUT'
     * @return string
     */
    public static function generateTrackingCode(string $prefix): string
    {
        $year = date('Y');
        $fullPrefix = $prefix . '-' . $year . '-';

        return DB::transaction(function () use ($fullPrefix, $prefix) {
            // Lock table untuk prevent race condition
            $lastRecord = static::where('code', 'like', $fullPrefix . '%')
                ->lockForUpdate()
                ->orderBy('code', 'desc')
                ->first();

            if ($lastRecord) {
                $lastNumber = (int) substr($lastRecord->code, -4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            return $fullPrefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Check apakah code sudah ada
     */
    public static function codeExists(string $code): bool
    {
        return static::where('code', $code)->exists();
    }
}
