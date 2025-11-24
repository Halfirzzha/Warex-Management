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
        Schema::table('outbounds', function (Blueprint $table) {
            // Tracking untuk berapa stok yang sudah dikurangi
            // Ini penting untuk adjust stok saat user edit total_containers setelah approved
            $table->decimal('stock_reduced_amount', 15, 2)->default(0)->after('total_containers')
                ->comment('Jumlah stok yang sudah dikurangi (untuk tracking adjustment saat edit)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outbounds', function (Blueprint $table) {
            $table->dropColumn('stock_reduced_amount');
        });
    }
};
