<?php

namespace App\Observers;

use App\Models\Outbound;
use App\Models\Stock;
use App\Support\CacheManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OutboundObserver
{
    /**
     * Handle the Outbound "creating" event.
     */
    public function creating(Outbound $outbound): void
    {
        // Auto-set created_by
        if (Auth::check() && !$outbound->created_by) {
            $outbound->created_by = Auth::id();
        }

        // Auto-generate code jika belum ada
        if (empty($outbound->code)) {
            $outbound->code = $outbound->generateTrackingCode('OUT');
        }

        // Auto-copy dari inbound jika ada
        if ($outbound->inbound_id && !$outbound->name_product) {
            $outbound->copyFromInbound();
        }

        // Auto-calculate weights
        $outbound->calculateWeights();

        // Validate stock jika ada inbound_id (CRITICAL VALIDATION)
        if ($outbound->inbound_id && !$outbound->validateStock()) {
            throw new \Exception('Stock tidak mencukupi untuk outbound ini. Silakan periksa ketersediaan stock.');
        }

        // Log activity
        Log::info('Outbound creating', [
            'code' => $outbound->code,
            'product' => $outbound->name_product,
            'warehouse' => $outbound->warehouse_from,
            'purpose' => $outbound->purpose_type,
            'department' => $outbound->department,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Handle the Outbound "created" event.
     */
    public function created(Outbound $outbound): void
    {
        // Clear cache dengan tag system
        CacheManager::invalidateTag('outbounds');
        CacheManager::invalidateWarehouse($outbound->warehouse_from);

        // Clear inbound cache untuk update available stock
        if ($outbound->inbound_id) {
            CacheManager::forget("inbound.{$outbound->inbound_id}");
            CacheManager::invalidateTag('inbounds');
        }

        // Increment counters
        CacheManager::incrementCounter('outbounds.total');
        CacheManager::incrementCounter("outbounds.{$outbound->warehouse_from}.total");
        CacheManager::incrementCounter("outbounds.purpose.{$outbound->purpose_type}");

        if ($outbound->department) {
            CacheManager::incrementCounter("outbounds.department.{$outbound->department}");
        }

        // Log activity
        Log::info('Outbound created successfully', [
            'id' => $outbound->id,
            'code' => $outbound->code,
            'warehouse' => $outbound->warehouse_from,
            'inbound_code' => $outbound->inbound?->code,
        ]);
    }

    /**
     * Handle the Outbound "updating" event.
     */
    public function updating(Outbound $outbound): void
    {
        // Auto-set updated_by
        if (Auth::check()) {
            $outbound->updated_by = Auth::id();
        }

        // Re-calculate weights jika data berat berubah
        if ($outbound->isDirty([
            'total_containers', 'items_per_container',
            'weight_per_item', 'weight_per_container', 'quantity'
        ])) {
            $outbound->calculateWeights();
        }

        // Validate stock jika total_containers berubah dan ada inbound_id (CRITICAL)
        if ($outbound->isDirty('total_containers') && $outbound->inbound_id) {
            if (!$outbound->validateStock()) {
                $stock = Stock::getByInbound($outbound->inbound_id, $outbound->warehouse_from);
                $available = $stock ? $stock->available_stock : 0;
                $unit = $stock ? $stock->unit_type : 'unit';
                throw new \Exception("Stock tidak mencukupi untuk perubahan ini. Available stock: {$available} {$unit}");
            }
        }

        // Log status change dengan detail lengkap
        if ($outbound->isDirty('status')) {
            $oldStatus = $outbound->getOriginal('status');
            $newStatus = $outbound->status;

            Log::info('Outbound status changed', [
                'id' => $outbound->id,
                'code' => $outbound->code,
                'from' => $oldStatus,
                'to' => $newStatus,
                'warehouse' => $outbound->warehouse_from,
                'purpose' => $outbound->purpose_type,
                'user_id' => Auth::id(),
            ]);

            // Update counters
            CacheManager::decrementCounter("outbounds.status.{$oldStatus}");
            CacheManager::incrementCounter("outbounds.status.{$newStatus}");
        }
    }

    /**
     * Handle the Outbound "updated" event.
     */
    public function updated(Outbound $outbound): void
    {
        // Clear cache dengan tag system
        CacheManager::invalidateTag('outbounds');
        CacheManager::invalidateWarehouse($outbound->warehouse_from);
        CacheManager::forget("outbound.{$outbound->id}");

        // Clear inbound cache untuk update available stock
        if ($outbound->inbound_id) {
            CacheManager::forget("inbound.{$outbound->inbound_id}");
        }

        // Clear statistics cache
        CacheManager::forget('outbound.stats.all');
        CacheManager::forget("outbound.stats.{$outbound->warehouse_from}");
        CacheManager::forget("outbound.stats.{$outbound->warehouse_from}.{$outbound->purpose_type}");

        // ========================================
        // AUTO-REDUCE STOCK
        // ========================================
        // Jika status berubah ke approved/completed, reduce stock
        if ($outbound->isDirty('status') && in_array($outbound->status, ['approved', 'picked_up', 'completed'])) {
            $this->reduceStock($outbound);
        }

        // Jika status berubah ke cancelled/rejected, restore stock
        if ($outbound->isDirty('status') && in_array($outbound->status, ['cancelled', 'rejected'])) {
            $this->restoreStock($outbound);
        }

        // Log dengan context lengkap
        Log::info('Outbound updated', [
            'id' => $outbound->id,
            'code' => $outbound->code,
            'warehouse' => $outbound->warehouse_from,
            'changes' => $outbound->getChanges(),
        ]);
    }

    /**
     * Reduce stock dari stocks table
     */
    protected function reduceStock(Outbound $outbound): void
    {
        if (!$outbound->inbound_id || !$outbound->warehouse_from || !$outbound->total_containers) {
            return;
        }

        DB::transaction(function () use ($outbound) {
            $stock = Stock::where('inbound_id', $outbound->inbound_id)
                ->where('warehouse', $outbound->warehouse_from)
                ->lockForUpdate() // Prevent race condition
                ->first();

            if (!$stock) {
                Log::error('Stock not found for outbound', [
                    'outbound_code' => $outbound->code,
                    'inbound_id' => $outbound->inbound_id,
                    'warehouse' => $outbound->warehouse_from,
                ]);
                return;
            }

            $success = $stock->reduceStock($outbound->total_containers, Auth::id());

            if ($success) {
                Log::info('Stock reduced for outbound', [
                    'outbound_code' => $outbound->code,
                    'stock_id' => $stock->id,
                    'amount' => $outbound->total_containers,
                    'available_before' => $stock->available_stock + $outbound->total_containers,
                    'available_after' => $stock->available_stock,
                ]);
            } else {
                Log::error('Failed to reduce stock', [
                    'outbound_code' => $outbound->code,
                    'stock_id' => $stock->id,
                    'requested' => $outbound->total_containers,
                    'available' => $stock->available_stock,
                ]);
            }
        });
    }

    /**
     * Restore stock jika outbound cancelled/rejected
     */
    protected function restoreStock(Outbound $outbound): void
    {
        if (!$outbound->inbound_id || !$outbound->warehouse_from || !$outbound->total_containers) {
            return;
        }

        // Cek apakah stock pernah dikurangi (status sebelumnya approved/completed)
        $oldStatus = $outbound->getOriginal('status');
        if (!in_array($oldStatus, ['approved', 'picked_up', 'completed'])) {
            return; // Stock belum pernah dikurangi, tidak perlu restore
        }

        $stock = Stock::where('inbound_id', $outbound->inbound_id)
            ->where('warehouse', $outbound->warehouse_from)
            ->first();

        if ($stock) {
            $success = $stock->restoreStock((float)$outbound->total_containers);

            if ($success) {
                Log::info('Stock restored for cancelled outbound', [
                    'outbound_code' => $outbound->code,
                    'stock_id' => $stock->id,
                    'amount' => $outbound->total_containers,
                    'available_after' => $stock->available_stock,
                ]);
            }
        }
    }

    /**
     * Handle the Outbound "deleted" event.
     */
    public function deleted(Outbound $outbound): void
    {
        // Clear all related cache
        CacheManager::invalidateTag('outbounds');
        CacheManager::invalidateWarehouse($outbound->warehouse_from);
        CacheManager::forget("outbound.{$outbound->id}");

        // Clear inbound cache
        if ($outbound->inbound_id) {
            CacheManager::forget("inbound.{$outbound->inbound_id}");
            CacheManager::invalidateTag('inbounds');
        }

        // Decrement counters
        CacheManager::decrementCounter('outbounds.total');
        CacheManager::decrementCounter("outbounds.{$outbound->warehouse_from}.total");
        CacheManager::decrementCounter("outbounds.purpose.{$outbound->purpose_type}");

        if ($outbound->department) {
            CacheManager::decrementCounter("outbounds.department.{$outbound->department}");
        }

        Log::warning('Outbound deleted', [
            'id' => $outbound->id,
            'code' => $outbound->code,
            'warehouse' => $outbound->warehouse_from,
            'user_id' => Auth::id(),
        ]);
    }
}
