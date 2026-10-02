<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
    Schema::create('pembayaran', function (Blueprint $table) {
            $table->id('id_pembayaran');
            $table->foreignId('id_pesanan')
            ->unique()
            ->constrained('pesanan', 'id_pesanan')
            ->cascadeOnDelete();
            $table->timestamp('tanggal')->useCurrent();
            $table->string('metode_pembayaran', 20);
            $table->decimal('bayar', 12, 2);
            $table->decimal('kembalian', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
