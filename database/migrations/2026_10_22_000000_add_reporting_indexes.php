<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('pembayaran', ['tanggal'])) {
            Schema::table('pembayaran', function (Blueprint $table) {
                $table->index('tanggal', 'pembayaran_tanggal_idx');
            });
        }

        if (! Schema::hasIndex('pesanan', ['tanggal'])) {
            Schema::table('pesanan', function (Blueprint $table) {
                $table->index('tanggal', 'pesanan_tanggal_idx');
            });
        }

        if (! Schema::hasIndex('detail_pesanan', ['id_pesanan'])) {
            Schema::table('detail_pesanan', function (Blueprint $table) {
                $table->index('id_pesanan', 'detail_pesanan_order_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('pembayaran', 'pembayaran_tanggal_idx')) {
            Schema::table('pembayaran', fn (Blueprint $table) => $table->dropIndex('pembayaran_tanggal_idx'));
        }

        if (Schema::hasIndex('pesanan', 'pesanan_tanggal_idx')) {
            Schema::table('pesanan', fn (Blueprint $table) => $table->dropIndex('pesanan_tanggal_idx'));
        }

        if (Schema::hasIndex('detail_pesanan', 'detail_pesanan_order_idx')) {
            Schema::table('detail_pesanan', fn (Blueprint $table) => $table->dropIndex('detail_pesanan_order_idx'));
        }
    }
};
