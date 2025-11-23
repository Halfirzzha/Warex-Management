<?php

namespace App\Observers;

use App\Models\Inbound;
use App\Models\Stock;
use App\Support\CacheManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InboundObserver
{
    /**
     * Handle the Inbound "creating" event.
     */
    public function creating(Inbound $inbound): void
    {
        // Auto-set created_by
        if (Auth::check() && !$inbound->created_by) {
            $inbound->created_by = Auth::id();
        }

        // Auto-generate code jika belum ada
        if (empty($inbound->code)) {
            $inbound->code = $inbound->generateTrackingCode('INB');
        }

        // Auto-calculate weights
        $inbound->calculateWeights();

        // Log activity
        Log::info('Inbound creating', [
            'code' => $inbound->code,
            'product' => $inbound->name_product,
            'warehouse' => $inbound->warehouse,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Handle the Inbound "created" event.
     */
    public function created(Inbound $inbound): void
    {
        // Clear cache dengan CacheManager (lebih powerful)
        CacheManager::invalidateTag('inbounds');
        CacheManager::invalidateWarehouse($inbound->warehouse);

        // Increment counter untuk statistics
        CacheManager::incrementCounter('inbounds.total');
        CacheManager::incrementCounter("inbounds.{$inbound->warehouse}.total");
        CacheManager::incrementCounter("inbounds.status.{$inbound->status}");

        // Log activity
        Log::info('Inbound created successfully', [
            'id' => $inbound->id,
            'code' => $inbound->code,
            'warehouse' => $inbound->warehouse,
        ]);
    }

    /**
     * Handle the Inbound "updating" event.
     */
    public function updating(Inbound $inbound): void
    {
        // Auto-set updated_by
        if (Auth::check()) {
            $inbound->updated_by = Auth::id();
        }

        // Re-calculate weights jika data berat berubah
        if ($inbound->isDirty([
            'total_containers', 'items_per_container',
            'weight_per_item', 'weight_per_container', 'quantity'
        ])) {
            $inbound->calculateWeights();
        }

        // Log status change dengan detail
        if ($inbound->isDirty('status')) {
            $oldStatus = $inbound->getOriginal('status');
            $newStatus = $inbound->status;

            Log::info('Inbound status changed', [
                'id' => $inbound->id,
                'code' => $inbound->code,
                'from' => $oldStatus,
                'to' => $newStatus,
                'user_id' => Auth::id(),
                'warehouse' => $inbound->warehouse,
            ]);

            // Update counters
            CacheManager::decrementCounter("inbounds.status.{$oldStatus}");
            CacheManager::incrementCounter("inbounds.status.{$newStatus}");
        }
    }

    /**
     * Handle the Inbound "updated" event.
     */
    public function updated(Inbound $inbound): void
    {
        // Clear cache dengan tag system
        CacheManager::invalidateTag('inbounds');
        CacheManager::invalidateWarehouse($inbound->warehouse);
        CacheManager::forget("inbound.{$inbound->id}");

        // Clear statistics cache untuk refresh dashboard
        CacheManager::forget('inbound.stats.all');
        CacheManager::forget("inbound.stats.{$inbound->warehouse}");

        // ========================================
        // AUTO-CREATE/UPDATE STOCK ENTRY
        // ========================================
        // Jika status berubah ke approved/stored, create/update stock entry
        if ($inbound->isDirty('status') && in_array($inbound->status, ['approved', 'stored', 'completed'])) {
            $this->createOrUpdateStock($inbound);
        }

        // Jika status berubah ke rejected/cancelled, lock stock
        if ($inbound->isDirty('status') && in_array($inbound->status, ['rejected', 'cancelled'])) {
            $this->lockStock($inbound);
        }

        // Log dengan warehouse context
        Log::info('Inbound updated', [
            'id' => $inbound->id,
            'code' => $inbound->code,
            'warehouse' => $inbound->warehouse,
            'changes' => $inbound->getChanges(),
        ]);
    }

    /**
     * Create or update stock entry dari inbound
     */
    protected function createOrUpdateStock(Inbound $inbound): void
    {
        if (!$inbound->warehouse || !$inbound->total_containers) {
            return;
        }

        DB::transaction(function () use ($inbound) {
            $stock = Stock::where('inbound_id', $inbound->id)
                ->where('warehouse', $inbound->warehouse)
                ->first();

            if ($stock) {
                // Update existing stock
                $stock->update([
                    'name_product' => $inbound->name_product,
                    'description_product' => $inbound->description_product,
                    'initial_stock' => $inbound->total_containers,
                    'available_stock' => $inbound->total_containers - $stock->stock_out,
                    'unit_type' => $inbound->unit_type,
                    'container_type' => $inbound->container_type,
                ]);

                Log::info('Stock updated from inbound', [
                    'stock_id' => $stock->id,
                    'inbound_code' => $inbound->code,
                    'warehouse' => $inbound->warehouse,
                    'initial_stock' => $stock->initial_stock,
                ]);
            } else {
                // Create new stock entry
                $stock = Stock::create([
                    'inbound_id' => $inbound->id,
                    'warehouse' => $inbound->warehouse,
                    'name_product' => $inbound->name_product,
                    'description_product' => $inbound->description_product,
                    'initial_stock' => $inbound->total_containers,
                    'available_stock' => $inbound->total_containers,
                    'reserved_stock' => 0,
                    'stock_out' => 0,
                    'unit_type' => $inbound->unit_type,
                    'container_type' => $inbound->container_type,
                    'status' => 'available',
                ]);

                Log::info('Stock created from inbound', [
                    'stock_id' => $stock->id,
                    'inbound_code' => $inbound->code,
                    'warehouse' => $inbound->warehouse,
                    'initial_stock' => $stock->initial_stock,
                ]);
            }
        });
    }

    /**
     * Lock stock jika inbound rejected/cancelled
     */
    protected function lockStock(Inbound $inbound): void
    {
        $stock = Stock::where('inbound_id', $inbound->id)
            ->where('warehouse', $inbound->warehouse)
            ->first();

        if ($stock) {
            $stock->lock();
            Log::warning('Stock locked due to inbound status change', [
                'stock_id' => $stock->id,
                'inbound_code' => $inbound->code,
                'status' => $inbound->status,
            ]);
        }
    }

    /**
     * Handle the Inbound "deleted" event.
     */
    public function deleted(Inbound $inbound): void
    {
        // Clear all related cache
        CacheManager::invalidateTag('inbounds');
        CacheManager::invalidateWarehouse($inbound->warehouse);
        CacheManager::forget("inbound.{$inbound->id}");

        // Decrement counters
        CacheManager::decrementCounter('inbounds.total');
        CacheManager::decrementCounter("inbounds.{$inbound->warehouse}.total");
        CacheManager::decrementCounter("inbounds.status.{$inbound->status}");

        Log::warning('Inbound deleted', [
            'id' => $inbound->id,
            'code' => $inbound->code,
            'warehouse' => $inbound->warehouse,
            'user_id' => Auth::id(),
        ]);
    }
}
