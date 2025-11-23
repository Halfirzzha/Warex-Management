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
        Schema::create('outbounds', function (Blueprint $table) {
            $table->id();

            // ========================================
            // TRACKING CODE (NOMOR UNIK)
            // ========================================
            $table->string('code', 30)->unique()->comment('Kode unik outbound (contoh: OUT-2025-0001) - UNIQUE & INDEXED');

            // ========================================
            // REFERENSI BARANG (DARI INBOUND)
            // ========================================
            $table->foreignId('inbound_id')->nullable()->constrained('inbounds')->onDelete('set null')->comment('Referensi ke barang masuk (optional, bisa input manual)');

            // ========================================
            // DATA PRODUK
            // ========================================
            $table->json('photo')->nullable()->comment('Array foto produk yang keluar (hingga 30 foto)');
            $table->string('name_product')->index()->comment('Nama produk (indexed untuk pencarian cepat)');
            $table->text('description_product')->comment('Deskripsi produk');

            // ========================================
            // INFORMASI GUDANG ASAL
            // ========================================
            $table->enum('warehouse_from', ['Gudang Koperasi', 'Gudang BGN', 'Gudang Lainnya'])->index()->comment('Gudang asal barang keluar (indexed untuk filter)');
            $table->enum('warehouse_ownership', ['Koperasi', 'BGN', 'Lainnya'])->index()->comment('Kepemilikan gudang (indexed untuk filter)');
            $table->text('warehouse_description')->nullable()->comment('Keterangan gudang asal');

            // ========================================
            // TUJUAN INTERNAL (KEPERLUAN INTERNAL SAJA)
            // ========================================
            $table->enum('purpose_type', [
                'operasional',          // Untuk operasional koperasi
                'produksi',             // Untuk proses produksi
                'konsumsi',             // Untuk konsumsi internal
                'distribusi_anggota',   // Distribusi ke anggota koperasi
                'event',                // Untuk event/acara koperasi
                'donasi',               // Untuk donasi/sosial
                'maintenance',          // Untuk pemeliharaan/perbaikan
                'lainnya',              // Keperluan lainnya
            ])->index()->comment('Jenis keperluan internal (indexed untuk filter)');

            $table->string('department', 100)->nullable()->index()->comment('Departemen/divisi yang meminta (indexed untuk filter)');
            $table->string('pic_name')->comment('Nama penanggung jawab/PIC yang mengambil barang');
            $table->string('pic_phone', 20)->nullable()->comment('Nomor telepon PIC');
            $table->string('pic_position', 100)->nullable()->comment('Jabatan PIC');
            $table->text('purpose_description')->comment('Keterangan detail keperluan (wajib untuk transparansi)');

            // ========================================
            // KUANTITAS & SATUAN
            // ========================================
            $table->decimal('quantity', 15, 2)->comment('Jumlah barang keluar (WAJIB)');
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
            $table->string('unit_type_other', 50)->nullable()->comment('Satuan custom jika memilih "other"');

            // ========================================
            // DETAIL PERHITUNGAN BERAT (PROFESIONAL)
            // ========================================

            // Level 1: Kemasan Besar (Contoh: 15 kardus ikan keluar)
            $table->decimal('total_containers', 15, 2)->nullable()->comment('Total kemasan besar (contoh: 15 kardus, 30 karung)');
            $table->string('container_type', 50)->nullable()->comment('Jenis kemasan besar (kardus, karung, bal, dll)');

            // Level 2: Item per Kemasan (Contoh: 1 kardus berisi 20 ikan)
            $table->decimal('items_per_container', 15, 2)->nullable()->comment('Jumlah item per kemasan (contoh: 20 ikan per kardus)');

            // Level 3: Berat Detail
            $table->decimal('weight_per_item', 15, 3)->nullable()->comment('Berat per 1 item (dalam kg) - contoh: 1 ikan = 1.5 kg');
            $table->decimal('weight_per_container', 15, 3)->nullable()->comment('Berat per 1 kemasan besar (dalam kg) - contoh: 1 kardus = 30 kg');

            // Level 4: Total Keseluruhan
            $table->decimal('total_items', 15, 2)->nullable()->comment('Total keseluruhan item - contoh: 15 kardus × 20 ikan = 300 ikan');
            $table->decimal('total_weight_kg', 15, 3)->nullable()->index()->comment('Total berat dalam kilogram (kg) - indexed untuk sorting');
            $table->decimal('total_weight_ton', 15, 3)->nullable()->comment('Total berat dalam ton');
            $table->decimal('total_weight_kwintal', 15, 3)->nullable()->comment('Total berat dalam kwintal');

            $table->text('weight_calculation_notes')->nullable()->comment('Catatan detail perhitungan berat');

            // ========================================
            // PEMERIKSAAN SEBELUM KELUAR (INSPECTION)
            // ========================================
            $table->foreignId('inspected_by')->nullable()->constrained('users')->onDelete('set null')->comment('User yang memeriksa barang sebelum keluar (role: inspector)');
            $table->timestamp('inspection_started_at')->nullable()->comment('Waktu mulai pemeriksaan - format: Senin, 18 Nov 2025 10:00');
            $table->timestamp('inspection_completed_at')->nullable()->comment('Waktu selesai pemeriksaan - format: Senin, 18 Nov 2025 11:30');
            $table->text('inspection_notes')->nullable()->comment('Catatan hasil pemeriksaan');

            // ========================================
            // PERSETUJUAN KELUAR (APPROVAL) - ROLE TERTENTU SAJA
            // ========================================
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->comment('User yang menyetujui pengeluaran (role: manager/supervisor/admin)');
            $table->timestamp('approved_at')->nullable()->comment('Waktu persetujuan - format: Senin, 18 Nov 2025 14:00');
            $table->text('approval_notes')->nullable()->comment('Catatan persetujuan');

            // ========================================
            // PENYERAHAN BARANG INTERNAL
            // ========================================
            $table->foreignId('handed_by')->nullable()->constrained('users')->onDelete('set null')->comment('User gudang yang menyerahkan barang (role: warehouse staff)');
            $table->timestamp('handed_at')->nullable()->comment('Waktu barang diserahkan ke PIC');
            $table->text('handover_notes')->nullable()->comment('Catatan serah terima (kondisi barang, kelengkapan, dll)');

            $table->string('received_by_pic', 100)->nullable()->comment('Nama PIC yang menerima barang (konfirmasi)');
            $table->timestamp('received_at')->nullable()->comment('Waktu barang diterima oleh PIC');
            $table->text('receipt_notes')->nullable()->comment('Catatan penerimaan dari PIC');

            // ========================================
            // STATUS WORKFLOW OUTBOUND INTERNAL
            // ========================================
            $table->enum('status', [
                'draft',                    // Baru dibuat, belum submit
                'submitted',                // Sudah disubmit, menunggu pemeriksaan
                'inspecting',               // Sedang dalam proses pemeriksaan
                'inspection_completed',     // Pemeriksaan selesai, menunggu approval
                'approved',                 // Disetujui, siap diambil
                'rejected',                 // Ditolak
                'ready_to_pickup',          // Siap diambil oleh PIC
                'picked_up',                // Sudah diambil/diserahkan
                'completed',                // Proses selesai sempurna
                'cancelled',                // Dibatalkan
            ])->default('draft')->index()->comment('Status alur proses outbound internal (indexed untuk filter cepat)');

            $table->text('rejection_reason')->nullable()->comment('Alasan penolakan (jika status = rejected)');
            $table->text('cancellation_reason')->nullable()->comment('Alasan pembatalan (jika status = cancelled)');

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
            $table->index(['warehouse_from', 'status'], 'idx_warehouse_status'); // Filter by warehouse + status
            $table->index(['created_by', 'created_at'], 'idx_created_by_date'); // Data per user + tanggal
            $table->index(['approved_by', 'approved_at'], 'idx_approved_by_date'); // Approval tracking
            $table->index(['purpose_type', 'created_at'], 'idx_purpose_tracking'); // Tracking per jenis keperluan
            $table->index(['department', 'status'], 'idx_department_status'); // Filter per departemen
            $table->index(['inbound_id', 'status'], 'idx_inbound_tracking'); // Tracking dari inbound ke outbound
            $table->index(['pic_name', 'created_at'], 'idx_pic_history'); // History per PIC
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbounds');
    }
};
