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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->comment('Unique identifier untuk user');
            $table->string('avatar')->nullable()->comment('Path avatar user');
            $table->string('name')->comment('Nama lengkap user');
            $table->string('username')->unique()->nullable()->comment('Username untuk login (opsional)');
            $table->string('position')->nullable()->comment('Posisi/jabatan user');
            $table->string('email')->unique()->comment('Email user untuk login');
            $table->timestamp('email_verified_at')->nullable()->comment('Waktu verifikasi email');
            $table->string('password')->comment('Password terenkripsi');

            // Password Management
            $table->timestamp('password_changed_at')->nullable()->comment('Waktu terakhir ubah password');
            $table->foreignId('password_changed_by')->nullable()->comment('ID user yang mengubah password')
                ->constrained('users')->nullOnDelete();

            // Login Activity Tracking
            $table->timestamp('first_login_at')->nullable()->comment('Waktu login pertama kali');
            $table->timestamp('last_login_at')->nullable()->comment('Waktu login terakhir');
            $table->string('last_login_ip', 45)->nullable()->comment('IP address login terakhir');

            // Security & Blocking System
            $table->integer('failed_login_attempts')->default(0)->comment('Jumlah percobaan login gagal');
            $table->timestamp('locked_at')->nullable()->comment('Waktu akun terkunci');
            $table->enum('account_status', ['active', 'blocked', 'suspended', 'terminated'])->default('active')
                ->comment('Status akun: active=aktif, blocked=diblokir sistem (3x gagal), suspended=dibekukan admin, terminated=dihentikan admin');
            $table->text('blocked_reason')->nullable()->comment('Alasan akun diblokir/suspended/terminated');
            $table->foreignId('blocked_by')->nullable()->comment('ID admin yang memblokir')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('blocked_until')->nullable()->comment('Waktu berakhir pemblokiran (untuk temporary block)');

            // Audit Trail
            $table->enum('created_by_type', ['system', 'admin'])->default('system')
                ->comment('Dibuat oleh: system atau admin');
            $table->foreignId('created_by_admin_id')->nullable()->comment('ID admin yang membuat (jika created_by_type = admin)')
                ->constrained('users')->nullOnDelete();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete untuk audit trail');

            // Indexes untuk performa
            $table->index('account_status');
            $table->index('last_login_at');
            $table->index('locked_at');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
