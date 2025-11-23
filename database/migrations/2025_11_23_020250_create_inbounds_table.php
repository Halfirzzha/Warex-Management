<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inbounds', function (Blueprint $table) {
            $table->id();

            // ========================================
            // TRACKING CODE (NOMOR UNIK)
            // ========================================
            $table->string('code', 30)->unique()->comment('Kode unik inbound (contoh: INB-2025-0001) - UNIQUE & INDEXED');

            // ========================================
            // DATA PRODUK
            // ========================================
            $table->json('photo')->nullable()->comment('Array foto produk (hingga 30 foto)');
            $table->string('name_product')->index()->comment('Nama produk (indexed untuk pencarian cepat)');
            $table->text('description_product')->comment('Deskripsi produk');

            // ========================================
            // INFORMASI GUDANG
            // ========================================
            $table->enum('warehouse', ['Gudang Koperasi', 'Gudang BGN', 'Gudang Lainnya'])->index()->comment('Lokasi gudang tujuan (indexed untuk filter)');
            $table->enum('warehouse_ownership', ['Koperasi', 'BGN', 'Lainnya'])->index()->comment('Kepemilikan gudang (indexed untuk filter)');
            $table->text('warehouse_description')->nullable()->comment('Keterangan tambahan gudang');

            // ========================================
            // KUANTITAS & SATUAN
            // ========================================
            $table->decimal('quantity', 15, 2)->comment('Jumlah barang masuk (WAJIB)');
            $table->enum('unit_type', [
                // Berat
                'kg',
                'gram',
                'ton',
                'kwintal',
                'ons',
                'pound',
                // Unit Kemasan
                'pcs',
                'unit',
                'box',
                'carton',
                'pack',
                'lusin',
                'gross',
                'dus',
                'karung',
                'bal',
                'roll',
                'lembar',
                // Lainnya
                'other',
            ])->comment('Satuan utama barang (WAJIB)');
            $table->string('unit_type_other')->nullable()->comment('Satuan custom jika memilih "other"');

            // ========================================
            // DETAIL PERHITUNGAN BERAT (PROFESIONAL)
            // ========================================

            // Level 1: Kemasan Besar (Contoh: 19 kardus ikan)
            $table->decimal('total_containers', 15, 2)->nullable()->comment('Total kemasan besar (contoh: 19 kardus, 50 karung)');
            $table->string('container_type', 50)->nullable()->comment('Jenis kemasan besar (kardus, karung, bal, dll)');

            // Level 2: Item per Kemasan (Contoh: 1 kardus berisi 20 ikan)
            $table->decimal('items_per_container', 15, 2)->nullable()->comment('Jumlah item per kemasan (contoh: 20 ikan per kardus)');

            // Level 3: Berat Detail
            $table->decimal('weight_per_item', 15, 3)->nullable()->comment('Berat per 1 item (dalam kg) - contoh: 1 ikan = 1.5 kg');
            $table->decimal('weight_per_container', 15, 3)->nullable()->comment('Berat per 1 kemasan besar (dalam kg) - contoh: 1 kardus = 30 kg');

            // Level 4: Total Keseluruhan
            $table->decimal('total_items', 15, 2)->nullable()->comment('Total keseluruhan item - contoh: 19 kardus × 20 ikan = 380 ikan');
            $table->decimal('total_weight_kg', 15, 3)->nullable()->index()->comment('Total berat dalam kilogram (kg) - indexed untuk sorting');
            $table->decimal('total_weight_ton', 15, 3)->nullable()->comment('Total berat dalam ton');
            $table->decimal('total_weight_kwintal', 15, 3)->nullable()->comment('Total berat dalam kwintal');

            $table->text('weight_calculation_notes')->nullable()->comment('Catatan detail perhitungan berat');

            // ========================================
            // PEMERIKSAAN (INSPECTION)
            // ========================================
            $table->foreignId('inspected_by')->nullable()->constrained('users')->onDelete('set null')->comment('User yang memeriksa barang (role: inspector)');
            $table->timestamp('inspection_started_at')->nullable()->comment('Waktu mulai pemeriksaan - format: Senin, 18 Nov 2025 10:00');
            $table->timestamp('inspection_completed_at')->nullable()->comment('Waktu selesai pemeriksaan - format: Senin, 18 Nov 2025 11:30');
            $table->text('inspection_notes')->nullable()->comment('Catatan hasil pemeriksaan');

            // ========================================
            // PERSETUJUAN (APPROVAL) - ROLE TERTENTU SAJA
            // ========================================
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->comment('User yang menyetujui (role: manager/supervisor/admin)');
            $table->timestamp('approved_at')->nullable()->comment('Waktu persetujuan - format: Senin, 18 Nov 2025 14:00');
            $table->text('approval_notes')->nullable()->comment('Catatan persetujuan');

            // ========================================
            // STATUS WORKFLOW
            // ========================================
            $table->enum('status', [
                'draft',                    // Baru dibuat, belum submit
                'submitted',                // Sudah disubmit, menunggu pemeriksaan
                'inspecting',               // Sedang dalam proses pemeriksaan
                'inspection_completed',     // Pemeriksaan selesai, menunggu approval
                'approved',                 // Disetujui, siap disimpan
                'rejected',                 // Ditolak
                'stored',                   // Sudah disimpan di gudang
                'completed',                // Proses selesai sempurna
                'cancelled',                // Dibatalkan
            ])->default('draft')->index()->comment('Status alur proses inbound (indexed untuk filter cepat)');

            $table->text('rejection_reason')->nullable()->comment('Alasan penolakan (jika status = rejected)');

            // ========================================
            // SYSTEM FIELDS (AUDIT TRAIL)
            // ========================================
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict')->comment('User yang pertama kali membuat data ini (auto dari login)');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null')->comment('User yang terakhir mengupdate data');
            $table->timestamps(); // Otomatis created_at & updated_at

            // ========================================
            // INDEXES (PERFORMA OPTIMAL)
            // ========================================

            // Composite Index untuk query kompleks yang sering digunakan
            $table->index(['status', 'created_at'], 'idx_status_created'); // Filter by status + sort by date
            $table->index(['warehouse', 'status'], 'idx_warehouse_status'); // Filter by warehouse + status
            $table->index(['created_by', 'created_at'], 'idx_created_by_date'); // Data per user + tanggal
            $table->index(['approved_by', 'approved_at'], 'idx_approved_by_date'); // Approval tracking
            $table->index(['warehouse_ownership', 'warehouse'], 'idx_warehouse_tracking'); // Tracking penerimaan gudang
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbounds');
    }
};
