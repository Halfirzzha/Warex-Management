<?php

namespace App\Traits;

trait HasWeightCalculation
{
    /**
     * Auto-calculate weights dengan formula yang akurat
     * Support multiple calculation methods
     */
    public function calculateWeights(): void
    {
        // Step 1: Calculate total items
        if ($this->total_containers && $this->items_per_container) {
            $this->attributes['total_items'] = $this->total_containers * $this->items_per_container;
        }

        // Step 2: Calculate weight_per_container jika belum ada
        if (!$this->weight_per_container && $this->items_per_container && $this->weight_per_item) {
            $this->attributes['weight_per_container'] = $this->items_per_container * $this->weight_per_item;
        }

        // Step 3: Calculate total_weight_kg dengan priority
        // Priority 1: Dari total_containers × weight_per_container
        if ($this->total_containers && $this->weight_per_container) {
            $this->attributes['total_weight_kg'] = $this->total_containers * $this->weight_per_container;
        }
        // Priority 2: Dari total_items × weight_per_item
        elseif ($this->total_items && $this->weight_per_item) {
            $this->attributes['total_weight_kg'] = $this->total_items * $this->weight_per_item;
        }

        // Step 4: Auto-convert ke TON dan KWINTAL
        if (isset($this->attributes['total_weight_kg']) && $this->attributes['total_weight_kg']) {
            $kg = (float) $this->attributes['total_weight_kg'];
            $this->attributes['total_weight_ton'] = $kg / 1000;      // 1 ton = 1000 kg
            $this->attributes['total_weight_kwintal'] = $kg / 100;   // 1 kwintal = 100 kg
        }
    }

    /**
     * Get summary berat dalam format readable
     */
    public function getWeightSummary(): array
    {
        return [
            'kg' => number_format($this->total_weight_kg ?? 0, 2, ',', '.') . ' kg',
            'ton' => number_format($this->total_weight_ton ?? 0, 3, ',', '.') . ' ton',
            'kwintal' => number_format($this->total_weight_kwintal ?? 0, 2, ',', '.') . ' kwintal',
        ];
    }

    /**
     * Validate weight calculations
     */
    public function validateWeightCalculations(): bool
    {
        // Jika ada total_weight_kg, pastikan conversion benar
        if ($this->total_weight_kg) {
            $expectedTon = $this->total_weight_kg / 1000;
            $expectedKwintal = $this->total_weight_kg / 100;

            return abs(($this->total_weight_ton ?? 0) - $expectedTon) < 0.001
                && abs(($this->total_weight_kwintal ?? 0) - $expectedKwintal) < 0.01;
        }

        return true;
    }
}
