<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->string('bank_pengirim', 50)->nullable();
            $table->string('nama_pengirim', 100)->nullable();
            $table->string('nomor_referensi', 100)->nullable();
            $table->string('bukti_transfer', 255)->nullable();
            $table->string('status_pembayaran', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropColumn([
                'bank_pengirim',
                'nama_pengirim',
                'nomor_referensi',
                'bukti_transfer',
                'status_pembayaran',
            ]);
        });
    }
};
