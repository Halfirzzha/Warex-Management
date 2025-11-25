<?php

namespace App\Models;

use App\Observers\InboundObserver;
use App\Traits\HasTrackingCode;
use App\Traits\HasWeightCalculation;
use App\Traits\HasWorkflowStatus;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([InboundObserver::class])]
class Inbound extends Model
{
    use HasTrackingCode, HasWeightCalculation, HasWorkflowStatus, LogsActivity;

    protected $table = 'inbounds';

    // ========================================
    // FILLABLE (Mass Assignment Protection)
    // ========================================
    protected $fillable = [
        // Tracking
        'code',

        // Data Produk
        'photo',
        'name_product',
        'description_product',

        // Informasi Gudang
        'warehouse',
        'warehouse_ownership',
        'warehouse_description',

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

        // Status
        'status',
        'rejection_reason',

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
            'approved' => ['stored'],
            'rejected' => ['draft'], // Bisa revisi
            'stored' => ['completed'],
            'completed' => [], // Final status
            'cancelled' => [], // Final status
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

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

    // Outbounds yang menggunakan inbound ini
    public function outbounds(): HasMany
    {
        return $this->hasMany(Outbound::class, 'inbound_id');
    }

    // ========================================
    // SCOPES (Query Helper - PERFORMA OPTIMAL)
    // ========================================

    // Filter by status
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // Filter by warehouse
    public function scopeWarehouse($query, string $warehouse)
    {
        return $query->where('warehouse', $warehouse);
    }

    // Filter by date range
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Sudah approved
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Pending approval
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'inspection_completed');
    }

    // Search by code or product name
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
                ->orWhere('name_product', 'like', "%{$search}%");
        });
    }

    // ========================================
    // ACCESSORS (Get Formatted Data)
    // ========================================

    // Format created_at: Senin, 18 Nov 2025 10:00
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at?->locale('id')->isoFormat('dddd, DD MMM YYYY HH:mm') ?? '-';
    }

    // Format approved_at
    public function getFormattedApprovedAtAttribute(): ?string
    {
        return $this->approved_at?->locale('id')->isoFormat('dddd, DD MMM YYYY HH:mm');
    }

    // Status badge color (untuk UI)
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'submitted' => 'blue',
            'inspecting' => 'yellow',
            'inspection_completed' => 'orange',
            'approved' => 'green',
            'rejected' => 'red',
            'stored' => 'purple',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    // Status label (Indonesian)
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Disubmit',
            'inspecting' => 'Sedang Diperiksa',
            'inspection_completed' => 'Pemeriksaan Selesai',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'stored' => 'Tersimpan',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => $this->status,
        };
    }

    // ========================================
    // METHODS (Business Logic)
    // ========================================

    // Submit untuk pemeriksaan
    public function submit(): bool
    {
        if ($this->status === 'draft') {
            $this->status = 'submitted';

            return $this->save();
        }

        return false;
    }

    // Mulai pemeriksaan
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

    // Selesai pemeriksaan
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

    // Approve
    public function approve(int $approverId, ?string $notes = null): bool
    {
        if ($this->status === 'inspection_completed') {
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

    // Reject
    public function reject(string $reason): bool
    {
        if (in_array($this->status, ['submitted', 'inspecting', 'inspection_completed'])) {
            $this->status = 'rejected';
            $this->rejection_reason = $reason;

            return $this->save();
        }

        return false;
    }

    // Check apakah bisa diedit
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    // Check apakah bisa di-approve
    public function canBeApproved(): bool
    {
        return $this->status === 'inspection_completed';
    }

    // Get available stock (untuk outbound)
    public function getAvailableStock(): float
    {
        if ($this->status !== 'approved' && $this->status !== 'stored' && $this->status !== 'completed') {
            return 0;
        }

        // Total masuk - total keluar
        $totalOut = $this->outbounds()
            ->whereIn('status', ['approved', 'picked_up', 'completed'])
            ->sum('quantity');

        return max(0, $this->quantity - $totalOut);
    }

    // ========================================
    // ADVANCED METHODS (CACHE & OPTIMIZATION)
    // ========================================

    /**
     * Get cached inbound by ID (SUPER FAST)
     */
    public static function getCached(int $id): ?self
    {
        return Cache::tags(['inbounds'])->remember(
            "inbound.{$id}",
            now()->addHours(24),
            fn () => self::with(['creator', 'inspector', 'approver'])->find($id)
        );
    }

    /**
     * Get statistics untuk dashboard (CACHED)
     */
    public static function getStatistics(?string $warehouse = null): array
    {
        $cacheKey = $warehouse ? "inbound.stats.{$warehouse}" : 'inbound.stats.all';

        return Cache::tags(['inbounds'])->remember($cacheKey, now()->addMinutes(15), function () use ($warehouse) {
            $query = self::query();

            if ($warehouse) {
                $query->where('warehouse', $warehouse);
            }

            return [
                'total' => $query->count(),
                'draft' => (clone $query)->where('status', 'draft')->count(),
                'submitted' => (clone $query)->where('status', 'submitted')->count(),
                'inspecting' => (clone $query)->where('status', 'inspecting')->count(),
                'pending_approval' => (clone $query)->where('status', 'inspection_completed')->count(),
                'approved' => (clone $query)->where('status', 'approved')->count(),
                'rejected' => (clone $query)->where('status', 'rejected')->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'total_weight_kg' => (clone $query)
                    ->whereIn('status', ['approved', 'stored', 'completed'])
                    ->sum('total_weight_kg'),
            ];
        });
    }

    /**
     * Bulk approve (EFFICIENT)
     */
    public static function bulkApprove(array $ids, int $approverId, ?string $notes = null): int
    {
        return DB::transaction(function () use ($ids, $approverId, $notes) {
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
            'warehouse' => $this->warehouse,
            'quantity' => $this->quantity.' '.$this->unit_type,
            'weight' => $this->getWeightSummary(),
            'status' => $this->status_label,
            'created_at' => $this->formatted_created_at,
            'approved_at' => $this->formatted_approved_at,
            'creator' => $this->creator?->name,
            'approver' => $this->approver?->name,
            'available_stock' => $this->getAvailableStock(),
        ];
    }

    /**
     * Configure activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name_product', 'warehouse', 'status', 'total_containers', 'quantity'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Inbound {$eventName}: {$this->code}");
    }
}
