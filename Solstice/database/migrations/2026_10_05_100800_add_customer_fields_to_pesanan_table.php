<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->string('nama_pelanggan', 100)->nullable();
            $table->string('metode_pembayaran', 20)->nullable();
        });

        DB::statement('ALTER TABLE pesanan ALTER COLUMN id_users DROP NOT NULL');
    }

    public function down(): void
    {
        if (DB::table('pesanan')->whereNull('id_users')->exists()) {
            throw new RuntimeException(
                'Tidak bisa rollback: masih ada pesanan pelanggan tanpa id_users.'
            );
        }

        DB::statement('ALTER TABLE pesanan ALTER COLUMN id_users SET NOT NULL');

        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropColumn(['nama_pelanggan', 'metode_pembayaran']);
        });
    }
};
