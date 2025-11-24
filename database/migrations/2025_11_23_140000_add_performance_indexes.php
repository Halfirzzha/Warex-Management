<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes to inbounds table for better dashboard query performance
        DB::statement('CREATE INDEX IF NOT EXISTS idx_inbounds_warehouse ON inbounds(warehouse)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_inbounds_status ON inbounds(status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_inbounds_created_at ON inbounds(created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_inbounds_warehouse_status ON inbounds(warehouse, status)');

        // Add indexes to outbounds table
        DB::statement('CREATE INDEX IF NOT EXISTS idx_outbounds_warehouse_from ON outbounds(warehouse_from)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_outbounds_status ON outbounds(status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_outbounds_created_at ON outbounds(created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_outbounds_warehouse_status ON outbounds(warehouse_from, status)');

        // Add indexes to stocks table
        DB::statement('CREATE INDEX IF NOT EXISTS idx_stocks_warehouse_status ON stocks(warehouse, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_stocks_status_available ON stocks(status, available_stock)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_inbounds_warehouse');
        DB::statement('DROP INDEX IF EXISTS idx_inbounds_status');
        DB::statement('DROP INDEX IF EXISTS idx_inbounds_created_at');
        DB::statement('DROP INDEX IF EXISTS idx_inbounds_warehouse_status');

        DB::statement('DROP INDEX IF EXISTS idx_outbounds_warehouse_from');
        DB::statement('DROP INDEX IF EXISTS idx_outbounds_status');
        DB::statement('DROP INDEX IF EXISTS idx_outbounds_created_at');
        DB::statement('DROP INDEX IF EXISTS idx_outbounds_warehouse_status');

        DB::statement('DROP INDEX IF EXISTS idx_stocks_warehouse_status');
        DB::statement('DROP INDEX IF EXISTS idx_stocks_status_available');
    }
};
