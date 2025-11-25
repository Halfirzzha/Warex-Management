<?php

namespace App\Models;

use App\Observers\StockObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[ObservedBy([StockObserver::class])]
class Stock extends Model
{
    use LogsActivity;
    protected $table = 'stocks';

    // ========================================
    // FILLABLE (System-managed only)
    // ========================================
    protected $fillable = [
        'inbound_id',
        'warehouse',
        'name_product',
        'description_product',
        'initial_stock',
        'available_stock',
        'reserved_stock',
        'stock_out',
        'unit_type',
        'container_type',
        'status',
        'last_updated_at',
        'last_updated_by',
    ];

    // ========================================
    // CASTS
    // ========================================
    protected $casts = [
        'initial_stock' => 'decimal:2',
        'available_stock' => 'decimal:2',
        'reserved_stock' => 'decimal:2',
        'stock_out' => 'decimal:2',
        'last_updated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function inbound(): BelongsTo
    {
        return $this->belongsTo(Inbound::class, 'inbound_id');
    }

    public function lastUpdater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    // ========================================
    // SCOPES (Query Helpers)
    // ========================================

    public function scopeWarehouse($query, string $warehouse)
    {
        return $query->where('warehouse', $warehouse);
    }

    public function scopeProduct($query, string $product)
    {
        return $query->where('name_product', 'like', "%{$product}%");
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')
            ->where('available_stock', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->where('status', 'low_stock');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('status', 'out_of_stock');
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name_product', 'like', "%{$search}%")
                ->orWhere('warehouse', 'like', "%{$search}%")
                ->orWhereHas('inbound', function ($iq) use ($search) {
                    $iq->where('code', 'like', "%{$search}%");
                });
        });
    }

    // ========================================
    // ACCESSORS (Formatted Data)
    // ========================================

    public function getStockPercentageAttribute(): float
    {
        if ($this->initial_stock == 0) {
            return 0;
        }

        return round(($this->available_stock / $this->initial_stock) * 100, 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'available' => 'Tersedia',
            'low_stock' => 'Stock Menipis',
            'out_of_stock' => 'Habis',
            'locked' => 'Terkunci',
            'unavailable' => 'Belum Tersedia',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'available' => 'success',
            'low_stock' => 'warning',
            'out_of_stock' => 'danger',
            'locked' => 'gray',
            'unavailable' => 'info',
            default => 'secondary',
        };
    }

    public function getFormattedLastUpdatedAttribute(): ?string
    {
        return $this->last_updated_at?->locale('id')->diffForHumans();
    }

    // ========================================
    // METHODS (Stock Operations - AUTO ONLY)
    // ========================================

    /**
     * Reduce stock saat outbound (Called by StockObserver)
     */
    public function reduceStock(float $amount, ?int $userId = null): bool
    {
        if ((float)$this->available_stock < $amount) {
            return false;
        }

        $this->setAttribute('available_stock', (float)$this->available_stock - $amount);
        $this->setAttribute('stock_out', (float)$this->stock_out + $amount);
        $this->last_updated_at = now();
        $this->last_updated_by = $userId;

        // Update status based on percentage
        $this->updateStatus();

        return $this->save();
    }

    /**
     * Restore stock saat outbound cancelled/rejected
     */
    public function restoreStock(float $amount): bool
    {
        $this->setAttribute('available_stock', (float)$this->available_stock + $amount);
        $this->setAttribute('stock_out', max(0.0, (float)$this->stock_out - $amount));
        $this->last_updated_at = now();

        // Update status
        $this->updateStatus();

        return $this->save();
    }

    /**
     * Reserve stock untuk future feature (draft outbound)
     */
    public function reserveStock(float $amount): bool
    {
        if ((float)$this->available_stock < $amount) {
            return false;
        }

        $this->setAttribute('available_stock', (float)$this->available_stock - $amount);
        $this->setAttribute('reserved_stock', (float)$this->reserved_stock + $amount);
        $this->last_updated_at = now();

        return $this->save();
    }

    /**
     * Release reservation
     */
    public function releaseReservation(float $amount): bool
    {
        if ((float)$this->reserved_stock < $amount) {
            return false;
        }

        $this->setAttribute('available_stock', (float)$this->available_stock + $amount);
        $this->setAttribute('reserved_stock', (float)$this->reserved_stock - $amount);
        $this->last_updated_at = now();

        return $this->save();
    }

    /**
     * Update status berdasarkan percentage
     */
    protected function updateStatus(): void
    {
        if ($this->available_stock <= 0) {
            $this->status = 'out_of_stock';
        } elseif ($this->stock_percentage < 20) {
            $this->status = 'low_stock';
        } else {
            $this->status = 'available';
        }
    }

    /**
     * Lock stock (inbound rejected/cancelled)
     */
    public function lock(): bool
    {
        $this->status = 'locked';
        $this->setAttribute('available_stock', 0);

        return $this->save();
    }

    // ========================================
    // STATIC METHODS (Query & Cache)
    // ========================================

    /**
     * Get stock by inbound and warehouse
     */
    public static function getByInbound(int $inboundId, string $warehouse): ?self
    {
        return Cache::tags(['stocks'])->remember(
            "stock.inbound.{$inboundId}.{$warehouse}",
            now()->addHours(1),
            fn () => self::where('inbound_id', $inboundId)
                ->where('warehouse', $warehouse)
                ->first()
        );
    }

    /**
     * Get available stock untuk product di warehouse
     */
    public static function getAvailableForProduct(string $product, string $warehouse): float
    {
        return self::where('name_product', $product)
            ->where('warehouse', $warehouse)
            ->where('status', 'available')
            ->sum('available_stock');
    }

    /**
     * Get statistics per warehouse
     */
    public static function getWarehouseStats(string $warehouse): array
    {
        return Cache::tags(['stocks'])->remember(
            "stock.stats.{$warehouse}",
            now()->addMinutes(15),
            function () use ($warehouse) {
                $query = self::where('warehouse', $warehouse);

                return [
                    'total_products' => (clone $query)->count(),
                    'available_products' => (clone $query)->available()->count(),
                    'low_stock_products' => (clone $query)->lowStock()->count(),
                    'out_of_stock_products' => (clone $query)->outOfStock()->count(),
                    'total_initial' => (clone $query)->sum('initial_stock'),
                    'total_available' => (clone $query)->sum('available_stock'),
                    'total_stock_out' => (clone $query)->sum('stock_out'),
                    'stock_percentage' => (clone $query)->sum('initial_stock') > 0
                        ? round(((clone $query)->sum('available_stock') / (clone $query)->sum('initial_stock')) * 100, 2)
                        : 0,
                ];
            }
        );
    }

    /**
     * Get low stock alerts
     */
    public static function getLowStockAlerts(?string $warehouse = null): array
    {
        $query = self::lowStock()
            ->with(['inbound:id,code', 'lastUpdater:id,name'])
            ->orderBy('stock_percentage', 'asc');

        if ($warehouse) {
            $query->where('warehouse', $warehouse);
        }

        return $query->get()->map(fn ($stock) => [
            'inbound_code' => $stock->inbound->code,
            'product' => $stock->name_product,
            'warehouse' => $stock->warehouse,
            'available' => $stock->available_stock,
            'initial' => $stock->initial_stock,
            'percentage' => $stock->stock_percentage,
            'unit' => $stock->unit_type,
        ])->toArray();
    }

    /**
     * Clear cache for this stock
     */
    public function clearCache(): void
    {
        Cache::tags(['stocks'])->forget("stock.inbound.{$this->inbound_id}.{$this->warehouse}");
        Cache::tags(['stocks'])->forget("stock.stats.{$this->warehouse}");
    }

    /**
     * Configure activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['warehouse', 'name_product', 'initial_stock', 'available_stock', 'stock_out', 'reserved_stock', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Stock {$eventName}: {$this->name_product} at {$this->warehouse}");
    }
}
