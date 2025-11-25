<?php

namespace App\Observers;

use App\Models\Stock;
use App\Support\CacheManager;
use Illuminate\Support\Facades\Log;

class StockObserver
{
    /**
     * Handle the Stock "creating" event.
     * Auto-set status based on logic
     */
    public function creating(Stock $stock): void
    {
        // Don't auto-calculate status if explicitly set (from InboundObserver)
        if (! $stock->status || $stock->status === 'available') {
            $this->updateStockStatus($stock);
        }

        Log::info('Stock creating', [
            'inbound_id' => $stock->inbound_id,
            'warehouse' => $stock->warehouse,
            'product' => $stock->name_product,
            'initial' => $stock->initial_stock,
            'available' => $stock->available_stock,
            'status' => $stock->status,
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
        // Only auto-update status if not locked or unavailable
        if ($stock->isDirty('available_stock') && ! in_array($stock->status, ['locked', 'unavailable'])) {
            $this->updateStockStatus($stock);
        }

        Log::info('Stock updating', [
            'id' => $stock->id,
            'warehouse' => $stock->warehouse,
            'product' => $stock->name_product,
            'available_was' => $stock->getOriginal('available_stock'),
            'available_now' => $stock->available_stock,
            'stock_out_was' => $stock->getOriginal('stock_out'),
            'stock_out_now' => $stock->stock_out,
            'status' => $stock->status,
        ]);
    }

    /**
     * Update stock status based on availability
     */
    protected function updateStockStatus(Stock $stock): void
    {
        if ($stock->available_stock <= 0) {
            $stock->status = 'out_of_stock';
        } elseif ($stock->initial_stock > 0 && ($stock->available_stock / $stock->initial_stock) < 0.2) {
            $stock->status = 'low_stock';
        } else {
            $stock->status = 'available';
        }
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

        // Send notification ONLY for available stocks that become low/out
        if ($stock->isDirty('status') &&
            in_array($stock->status, ['low_stock', 'out_of_stock']) &&
            $stock->getOriginal('status') === 'available') {
            $this->sendLowStockNotification($stock);
        }

        // Log jika stock habis
        if ($stock->status === 'out_of_stock' && $stock->isDirty('status')) {
            Log::warning('Stock habis!', [
                'inbound_id' => $stock->inbound_id,
                'warehouse' => $stock->warehouse,
                'product' => $stock->name_product,
            ]);
        }

        // Log jika stock menipis
        if ($stock->status === 'low_stock' && $stock->isDirty('status')) {
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
     * Send low stock notification to admins
     */
    protected function sendLowStockNotification(Stock $stock): void
    {
        // Get all admin users
        $admins = \App\Models\User::all(); // Adjust based on your admin role logic

        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\LowStockAlertNotification($stock));
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
