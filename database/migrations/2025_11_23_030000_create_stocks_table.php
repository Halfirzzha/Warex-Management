<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * TABEL STOCKS - TRACKING INVENTORY REAL-TIME
     * Tabel ini dikelola otomatis oleh system (StockObserver)
     * User TIDAK BISA manual create/update/delete
     */
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();

            // ========================================
            // TRACKING INBOUND (REFERENSI)
            // ========================================
            $table->foreignId('inbound_id')
                ->constrained('inbounds')
                ->onDelete('cascade')
                ->comment('Referensi ke inbound yang menjadi sumber stock');

            // ========================================
            // INFORMASI PRODUK & GUDANG
            // ========================================
            $table->string('warehouse', 100)
                ->comment('Nama gudang (Gudang Koperasi, Gudang BGN, Gudang Lainnya)');

            $table->string('name_product', 255)
                ->comment('Nama produk (copy dari inbound untuk performa query)');

            $table->text('description_product')
                ->nullable()
                ->comment('Deskripsi produk (copy dari inbound)');

            // ========================================
            // INVENTORY TRACKING (CORE SYSTEM)
            // ========================================
            $table->decimal('initial_stock', 12, 2)
                ->default(0)
                ->comment('Stock awal dari inbound (total_containers)');

            $table->decimal('available_stock', 12, 2)
                ->default(0)
                ->comment('Stock yang tersedia untuk outbound (initial - stock_out)');

            $table->decimal('reserved_stock', 12, 2)
                ->default(0)
                ->comment('Stock yang direservasi (draft outbound, future feature)');

            $table->decimal('stock_out', 12, 2)
                ->default(0)
                ->comment('Total stock yang sudah keluar (sum dari outbounds)');

            // ========================================
            // SATUAN & STATUS
            // ========================================
            $table->string('unit_type', 50)
                ->comment('Satuan (kg, pcs, box, dll - copy dari inbound)');

            $table->string('container_type', 100)
                ->nullable()
                ->comment('Jenis kontainer (Karung, Dus, Pallet, dll)');

            $table->enum('status', [
                'available',      // Stock tersedia untuk outbound
                'low_stock',      // Stock < 20% dari initial
                'out_of_stock',   // Stock habis (available = 0)
                'locked',         // Tidak bisa digunakan (inbound rejected/cancelled)
            ])->default('available')
                ->comment('Status ketersediaan stock');

            // ========================================
            // SYSTEM FIELDS
            // ========================================
            $table->timestamp('last_updated_at')
                ->nullable()
                ->comment('Terakhir kali stock diupdate (outbound created)');

            $table->foreignId('last_updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User yang terakhir update stock (via outbound)');

            $table->timestamps();

            // ========================================
            // INDEXES & CONSTRAINTS
            // ========================================

            // Unique: Satu inbound hanya punya satu stock entry per warehouse
            $table->unique(['inbound_id', 'warehouse'], 'stocks_inbound_warehouse_unique');

            // Index untuk query performa
            $table->index('warehouse', 'stocks_warehouse_index');
            $table->index('name_product', 'stocks_product_index');
            $table->index('status', 'stocks_status_index');
            $table->index('available_stock', 'stocks_available_index');

            // Index untuk reporting
            $table->index(['warehouse', 'status'], 'stocks_warehouse_status_index');
            $table->index(['warehouse', 'name_product'], 'stocks_warehouse_product_index');
        });

        // Add comment to table
        DB::statement("ALTER TABLE stocks COMMENT = 'Stock Tracking System - Auto-managed by StockObserver. READ-ONLY for users.'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
