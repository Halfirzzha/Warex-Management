<?php

namespace App\Models;

use App\Traits\HasTrackingCode;
use App\Traits\HasWeightCalculation;
use App\Traits\HasWorkflowStatus;
use App\Observers\OutboundObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

#[ObservedBy([OutboundObserver::class])]
class Outbound extends Model
{
    use HasTrackingCode, HasWeightCalculation, HasWorkflowStatus;

    protected $table = 'outbounds';

    // ========================================
    // FILLABLE (Mass Assignment Protection)
    // ========================================
    protected $fillable = [
        // Tracking
        'code',
        'inbound_id',

        // Data Produk
        'photo',
        'name_product',
        'description_product',

        // Informasi Gudang Asal
        'warehouse_from',
        'warehouse_ownership',
        'warehouse_description',

        // Tujuan Internal
        'purpose_type',
        'department',
        'pic_name',
        'pic_phone',
        'pic_position',
        'purpose_description',

        // Kuantitas & Satuan
        'quantity',
        'unit_type',
        'unit_type_other',

        // Detail Perhitungan Berat
        'total_containers',
        'container_type',
        'items_per_container',
        'weight_per_item',
        'weight_per_container',
        'total_items',
        'total_weight_kg',
        'total_weight_ton',
        'total_weight_kwintal',
        'weight_calculation_notes',

        // Pemeriksaan
        'inspected_by',
        'inspection_started_at',
        'inspection_completed_at',
        'inspection_notes',

        // Persetujuan
        'approved_by',
        'approved_at',
        'approval_notes',

        // Penyerahan Barang
        'handed_by',
        'handed_at',
        'handover_notes',
        'received_by_pic',
        'received_at',
        'receipt_notes',

        // Status
        'status',
        'rejection_reason',
        'cancellation_reason',

        // System Fields
        'created_by',
        'updated_by',
    ];

    // ========================================
    // CASTS (Automatic Type Conversion)
    // ========================================
    protected $casts = [
        // Array/JSON
        'photo' => 'array',

        // Decimals
        'quantity' => 'decimal:2',
        'total_containers' => 'decimal:2',
        'items_per_container' => 'decimal:2',
        'weight_per_item' => 'decimal:3',
        'weight_per_container' => 'decimal:3',
        'total_items' => 'decimal:2',
        'total_weight_kg' => 'decimal:3',
        'total_weight_ton' => 'decimal:3',
        'total_weight_kwintal' => 'decimal:3',

        // Timestamps
        'inspection_started_at' => 'datetime',
        'inspection_completed_at' => 'datetime',
        'approved_at' => 'datetime',
        'handed_at' => 'datetime',
        'received_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========================================
    // WORKFLOW TRANSITIONS (ADVANCED)
    // ========================================
    protected function getAllowedTransitions(): array
    {
        return [
            'draft' => ['submitted', 'cancelled'],
            'submitted' => ['inspecting', 'cancelled'],
            'inspecting' => ['inspection_completed', 'rejected'],
            'inspection_completed' => ['approved', 'rejected'],
            'approved' => ['ready_to_pickup'],
            'rejected' => ['draft'], // Bisa revisi
            'ready_to_pickup' => ['picked_up'],
            'picked_up' => ['completed'],
            'completed' => [], // Final status
            'cancelled' => [], // Final status
        ];
    }

    // ========================================
    // AUTO-COPY FROM INBOUND (SINKRON OTOMATIS)
    // ========================================
    public function copyFromInbound(): void
    {
        if (!$this->inbound_id) {
            return;
        }

        $inbound = Inbound::find($this->inbound_id);
        if (!$inbound) {
            return;
        }

        // Copy data produk
        $this->name_product = $this->name_product ?? $inbound->name_product;
        $this->description_product = $this->description_product ?? $inbound->description_product;
        $this->photo = $this->photo ?? $inbound->photo;

        // Copy data gudang
        $this->warehouse_from = $this->warehouse_from ?? $inbound->warehouse;
        $this->warehouse_ownership = $this->warehouse_ownership ?? $inbound->warehouse_ownership;

        // Copy satuan
        $this->unit_type = $this->unit_type ?? $inbound->unit_type;
        $this->unit_type_other = $this->unit_type_other ?? $inbound->unit_type_other;

        // Copy detail berat (untuk referensi)
        $this->weight_per_item = $this->weight_per_item ?? $inbound->weight_per_item;
        $this->weight_per_container = $this->weight_per_container ?? $inbound->weight_per_container;
        $this->items_per_container = $this->items_per_container ?? $inbound->items_per_container;
        $this->container_type = $this->container_type ?? $inbound->container_type;
    }

    // ========================================
    // AUTO-CALCULATE WEIGHTS (AKURAT & OTOMATIS)
    // ========================================
    public function calculateWeights(): void
    {
        // Calculate total items
        if ($this->total_containers && $this->items_per_container) {
            $this->total_items = (float)($this->total_containers * $this->items_per_container);
        }

        // Calculate total weight in KG
        if ($this->total_containers && $this->weight_per_container) {
            $this->total_weight_kg = (float)($this->total_containers * $this->weight_per_container);
        } elseif ($this->total_items && $this->weight_per_item) {
            $this->total_weight_kg = (float)($this->total_items * $this->weight_per_item);
        } elseif ($this->weight_per_container && $this->items_per_container && $this->weight_per_item) {
            $this->weight_per_container = (float)($this->items_per_container * $this->weight_per_item);
            $this->total_weight_kg = (float)($this->total_containers * $this->weight_per_container);
        }

        // Auto-convert ke TON dan KWINTAL (SINKRON OTOMATIS)
        if ($this->total_weight_kg) {
            $this->total_weight_ton = (float)($this->total_weight_kg / 1000);
            $this->total_weight_kwintal = (float)($this->total_weight_kg / 100);
        }
    }

    // ========================================
    // VALIDASI STOCK (AKURAT & REAL-TIME dari STOCKS TABLE)
    // ========================================
    public function validateStock(): bool
    {
        if (!$this->inbound_id || !$this->warehouse_from) {
            return true; // Manual input atau belum ada warehouse, tidak perlu validasi
        }

        // Query stock dari stocks table
        $stock = Stock::getByInbound($this->inbound_id, $this->warehouse_from);

        if (!$stock) {
            // Belum ada stock entry (inbound belum approved)
            return false;
        }

        $availableStock = $stock->available_stock;

        // Jika update existing outbound, tambah kembali total_containers lama
        if ($this->exists) {
            $availableStock += $this->getOriginal('total_containers') ?? 0;
        }

        // Validasi: available_stock >= total_containers yang akan dikeluarkan
        return $this->total_containers <= $availableStock;
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    // Inbound reference
    public function inbound(): BelongsTo
    {
        return $this->belongsTo(Inbound::class, 'inbound_id');
    }

    // Stock reference (direct access to stock table)
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'inbound_id', 'inbound_id')
            ->where('warehouse', $this->warehouse_from);
    }

    // User yang membuat
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // User yang update
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // User yang inspect
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    // User yang approve
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // User yang menyerahkan
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_by');
    }

    // ========================================
    // SCOPES (Query Helper - PERFORMA OPTIMAL)
    // ========================================

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeWarehouse($query, string $warehouse)
    {
        return $query->where('warehouse_from', $warehouse);
    }

    public function scopePurposeType($query, string $purposeType)
    {
        return $query->where('purpose_type', $purposeType);
    }

    public function scopeDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'inspection_completed');
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('name_product', 'like', "%{$search}%")
              ->orWhere('pic_name', 'like', "%{$search}%");
        });
    }

    // ========================================
    // ACCESSORS (Get Formatted Data)
    // ========================================

    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at?->locale('id')->isoFormat('dddd, DD MMM YYYY HH:mm') ?? '-';
    }

    public function getFormattedApprovedAtAttribute(): ?string
    {
        return $this->approved_at?->locale('id')->isoFormat('dddd, DD MMM YYYY HH:mm');
    }

    public function getFormattedHandedAtAttribute(): ?string
    {
        return $this->handed_at?->locale('id')->isoFormat('dddd, DD MMM YYYY HH:mm');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'submitted' => 'blue',
            'inspecting' => 'yellow',
            'inspection_completed' => 'orange',
            'approved' => 'green',
            'rejected' => 'red',
            'ready_to_pickup' => 'purple',
            'picked_up' => 'indigo',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Disubmit',
            'inspecting' => 'Sedang Diperiksa',
            'inspection_completed' => 'Pemeriksaan Selesai',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'ready_to_pickup' => 'Siap Diambil',
            'picked_up' => 'Sudah Diambil',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => $this->status,
        };
    }

    // ========================================
    // METHODS (Business Logic)
    // ========================================

    public function submit(): bool
    {
        if ($this->status === 'draft' && $this->validateStock()) {
            $this->status = 'submitted';
            return $this->save();
        }
        return false;
    }

    public function startInspection(int $inspectorId): bool
    {
        if ($this->status === 'submitted') {
            $this->status = 'inspecting';
            $this->inspected_by = $inspectorId;
            $this->inspection_started_at = now();
            return $this->save();
        }
        return false;
    }

    public function completeInspection(?string $notes = null): bool
    {
        if ($this->status === 'inspecting') {
            $this->status = 'inspection_completed';
            $this->inspection_completed_at = now();
            if ($notes) {
                $this->inspection_notes = $notes;
            }
            return $this->save();
        }
        return false;
    }

    public function approve(int $approverId, ?string $notes = null): bool
    {
        if ($this->status === 'inspection_completed' && $this->validateStock()) {
            $this->status = 'approved';
            $this->approved_by = $approverId;
            $this->approved_at = now();
            if ($notes) {
                $this->approval_notes = $notes;
            }
            return $this->save();
        }
        return false;
    }

    public function reject(string $reason): bool
    {
        if (in_array($this->status, ['submitted', 'inspecting', 'inspection_completed'])) {
            $this->status = 'rejected';
            $this->rejection_reason = $reason;
            return $this->save();
        }
        return false;
    }

    public function readyForPickup(): bool
    {
        if ($this->status === 'approved') {
            $this->status = 'ready_to_pickup';
            return $this->save();
        }
        return false;
    }

    public function handover(int $handlerId, ?string $notes = null): bool
    {
        if ($this->status === 'ready_to_pickup') {
            $this->status = 'picked_up';
            $this->handed_by = $handlerId;
            $this->handed_at = now();
            if ($notes) {
                $this->handover_notes = $notes;
            }
            return $this->save();
        }
        return false;
    }

    public function complete(): bool
    {
        if ($this->status === 'picked_up') {
            $this->status = 'completed';
            $this->received_at = $this->received_at ?? now();
            return $this->save();
        }
        return false;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'inspection_completed';
    }

    // ========================================
    // ADVANCED METHODS (CACHE & OPTIMIZATION)
    // ========================================

    /**
     * Get cached outbound by ID (SUPER FAST)
     */
    public static function getCached(int $id): ?self
    {
        return Cache::tags(['outbounds'])->remember(
            "outbound.{$id}",
            now()->addHours(24),
            fn() => self::with(['creator', 'inspector', 'approver', 'inbound'])->find($id)
        );
    }

    /**
     * Get statistics untuk dashboard (CACHED)
     */
    public static function getStatistics(?string $warehouse = null, ?string $purposeType = null): array
    {
        $cacheKey = "outbound.stats.{$warehouse}.{$purposeType}";

        return Cache::tags(['outbounds'])->remember($cacheKey, now()->addMinutes(15), function () use ($warehouse, $purposeType) {
            $query = self::query();

            if ($warehouse) {
                $query->where('warehouse_from', $warehouse);
            }

            if ($purposeType) {
                $query->where('purpose_type', $purposeType);
            }

            return [
                'total' => $query->count(),
                'draft' => (clone $query)->where('status', 'draft')->count(),
                'submitted' => (clone $query)->where('status', 'submitted')->count(),
                'inspecting' => (clone $query)->where('status', 'inspecting')->count(),
                'pending_approval' => (clone $query)->where('status', 'inspection_completed')->count(),
                'approved' => (clone $query)->where('status', 'approved')->count(),
                'ready_to_pickup' => (clone $query)->where('status', 'ready_to_pickup')->count(),
                'picked_up' => (clone $query)->where('status', 'picked_up')->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'rejected' => (clone $query)->where('status', 'rejected')->count(),
                'total_weight_kg' => (clone $query)
                    ->whereIn('status', ['approved', 'picked_up', 'completed'])
                    ->sum('total_weight_kg'),
            ];
        });
    }

    /**
     * Get per department statistics
     */
    public static function getDepartmentStatistics(): array
    {
        return Cache::tags(['outbounds'])->remember('outbound.stats.departments', now()->addHours(1), function () {
            return self::query()
                ->selectRaw('department, COUNT(*) as total, SUM(quantity) as total_quantity, SUM(total_weight_kg) as total_weight')
                ->whereNotNull('department')
                ->whereIn('status', ['approved', 'picked_up', 'completed'])
                ->groupBy('department')
                ->get()
                ->toArray();
        });
    }

    /**
     * Bulk approve (EFFICIENT)
     */
    public static function bulkApprove(array $ids, int $approverId, ?string $notes = null): int
    {
        return DB::transaction(function () use ($ids, $approverId, $notes) {
            // Validate stock untuk semua outbounds
            $outbounds = self::whereIn('id', $ids)
                ->where('status', 'inspection_completed')
                ->get();

            foreach ($outbounds as $outbound) {
                if (!$outbound->validateStock()) {
                    throw new \Exception("Stock tidak mencukupi untuk outbound {$outbound->code}");
                }
            }

            // Approve semua jika validasi berhasil
            return self::whereIn('id', $ids)
                ->where('status', 'inspection_completed')
                ->update([
                    'status' => 'approved',
                    'approved_by' => $approverId,
                    'approved_at' => now(),
                    'approval_notes' => $notes,
                    'updated_at' => now(),
                ]);
        });
    }

    /**
     * Get summary untuk reporting
     */
    public function getSummary(): array
    {
        return [
            'code' => $this->code,
            'product' => $this->name_product,
            'warehouse' => $this->warehouse_from,
            'purpose' => $this->purpose_type,
            'department' => $this->department,
            'pic' => $this->pic_name,
            'quantity' => $this->quantity . ' ' . $this->unit_type,
            'weight' => $this->getWeightSummary(),
            'status' => $this->status_label,
            'created_at' => $this->formatted_created_at,
            'approved_at' => $this->formatted_approved_at,
            'handed_at' => $this->formatted_handed_at,
            'creator' => $this->creator?->name,
            'approver' => $this->approver?->name,
            'inbound_code' => $this->inbound?->code,
        ];
    }
}
