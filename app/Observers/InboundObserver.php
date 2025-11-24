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
        // AUTO-CREATE STOCK ENTRY IMMEDIATELY (Real-time monitoring)
        $this->createOrUpdateStock($inbound);

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

            // Send notification jika status berubah ke inspection_completed (pending approval)
            if ($newStatus === 'inspection_completed') {
                $this->sendPendingApprovalNotification($inbound);
            }
        }
    }

    /**
     * Send pending approval notification
     */
    protected function sendPendingApprovalNotification(Inbound $inbound): void
    {
        // Get all admin/approver users
        $admins = \App\Models\User::all(); // Adjust based on your role logic

        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\InboundPendingApprovalNotification($inbound));
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
        // AUTO-UPDATE STOCK ENTRY (Real-time sync)
        // ========================================
        // Update stock whenever inbound data changes
        $this->createOrUpdateStock($inbound);

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
     * Create or update stock entry dari inbound (Real-time sync)
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

            // Determine available_stock based on inbound status
            $availableStock = 0;
            $status = 'unavailable';

            if (in_array($inbound->status, ['approved', 'stored', 'completed'])) {
                // Stock available for outbound
                $existingStockOut = $stock ? $stock->stock_out : 0;
                $availableStock = $inbound->total_containers - $existingStockOut;
                $status = 'available';
            } elseif (in_array($inbound->status, ['rejected', 'cancelled'])) {
                // Stock locked
                $availableStock = 0;
                $status = 'out_of_stock';
            } else {
                // Pending status (inspection, etc) - registered but not available
                $availableStock = 0;
                $status = 'unavailable';
            }

            $stockData = [
                'name_product' => $inbound->name_product,
                'description_product' => $inbound->description_product,
                'initial_stock' => $inbound->total_containers,
                'available_stock' => $availableStock,
                'unit_type' => $inbound->unit_type,
                'container_type' => $inbound->container_type,
                'status' => $status,
            ];

            if ($stock) {
                // Update existing stock (preserve stock_out and reserved_stock)
                $stock->update($stockData);

                Log::info('Stock updated from inbound', [
                    'stock_id' => $stock->id,
                    'inbound_code' => $inbound->code,
                    'inbound_status' => $inbound->status,
                    'warehouse' => $inbound->warehouse,
                    'initial_stock' => $stock->initial_stock,
                    'available_stock' => $stock->available_stock,
                ]);
            } else {
                // Create new stock entry
                $stock = Stock::create(array_merge($stockData, [
                    'inbound_id' => $inbound->id,
                    'warehouse' => $inbound->warehouse,
                    'reserved_stock' => 0,
                    'stock_out' => 0,
                ]));

                Log::info('Stock created from inbound', [
                    'stock_id' => $stock->id,
                    'inbound_code' => $inbound->code,
                    'inbound_status' => $inbound->status,
                    'warehouse' => $inbound->warehouse,
                    'initial_stock' => $stock->initial_stock,
                ]);
            }

            // Clear stock cache
            CacheManager::invalidateTag('stocks');
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
