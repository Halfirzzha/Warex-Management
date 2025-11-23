<?php

namespace App\Observers;

use App\Models\Stock;
use App\Support\CacheManager;
use Illuminate\Support\Facades\Log;

class StockObserver
{
    /**
     * Handle the Stock "creating" event.
     * Prevent manual creation
     */
    public function creating(Stock $stock): void
    {
        // Auto-update status berdasarkan available stock
        if ($stock->available_stock <= 0) {
            $stock->status = 'out_of_stock';
        } elseif ($stock->initial_stock > 0 && ($stock->available_stock / $stock->initial_stock) < 0.2) {
            $stock->status = 'low_stock';
        } else {
            $stock->status = 'available';
        }

        Log::info('Stock creating', [
            'inbound_id' => $stock->inbound_id,
            'warehouse' => $stock->warehouse,
            'product' => $stock->name_product,
            'initial' => $stock->initial_stock,
            'available' => $stock->available_stock,
        ]);
    }

    /**
     * Handle the Stock "created" event.
     */
    public function created(Stock $stock): void
    {
        // Clear cache
        CacheManager::invalidateTag('stocks');
        CacheManager::invalidateWarehouse($stock->warehouse);
        $stock->clearCache();

        Log::info('Stock created', [
            'id' => $stock->id,
            'inbound_id' => $stock->inbound_id,
            'warehouse' => $stock->warehouse,
            'product' => $stock->name_product,
            'initial_stock' => $stock->initial_stock,
        ]);
    }

    /**
     * Handle the Stock "updating" event.
     */
    public function updating(Stock $stock): void
    {
        // Auto-update status jika available stock berubah
        if ($stock->isDirty('available_stock')) {
            if ($stock->available_stock <= 0) {
                $stock->status = 'out_of_stock';
            } elseif ($stock->initial_stock > 0 && ($stock->available_stock / $stock->initial_stock) < 0.2) {
                $stock->status = 'low_stock';
            } else {
                $stock->status = 'available';
            }
        }

        Log::info('Stock updating', [
            'id' => $stock->id,
            'warehouse' => $stock->warehouse,
            'product' => $stock->name_product,
            'available_was' => $stock->getOriginal('available_stock'),
            'available_now' => $stock->available_stock,
            'stock_out_was' => $stock->getOriginal('stock_out'),
            'stock_out_now' => $stock->stock_out,
        ]);
    }

    /**
     * Handle the Stock "updated" event.
     */
    public function updated(Stock $stock): void
    {
        // Clear cache
        CacheManager::invalidateTag('stocks');
        CacheManager::invalidateWarehouse($stock->warehouse);
        $stock->clearCache();

        // Log jika stock habis
        if ($stock->status === 'out_of_stock') {
            Log::warning('Stock habis!', [
                'inbound_id' => $stock->inbound_id,
                'warehouse' => $stock->warehouse,
                'product' => $stock->name_product,
            ]);
        }

        // Log jika stock menipis
        if ($stock->status === 'low_stock') {
            Log::warning('Stock menipis!', [
                'inbound_id' => $stock->inbound_id,
                'warehouse' => $stock->warehouse,
                'product' => $stock->name_product,
                'available' => $stock->available_stock,
                'percentage' => $stock->stock_percentage.'%',
            ]);
        }
    }

    /**
     * Handle the Stock "deleted" event.
     */
    public function deleted(Stock $stock): void
    {
        // Clear cache
        CacheManager::invalidateTag('stocks');
        CacheManager::invalidateWarehouse($stock->warehouse);
        $stock->clearCache();

        Log::info('Stock deleted', [
            'id' => $stock->id,
            'inbound_id' => $stock->inbound_id,
            'warehouse' => $stock->warehouse,
            'product' => $stock->name_product,
        ]);
    }

    /**
     * Handle the Stock "restored" event.
     */
    public function restored(Stock $stock): void
    {
        CacheManager::invalidateTag('stocks');
        CacheManager::invalidateWarehouse($stock->warehouse);
        $stock->clearCache();
    }
}
