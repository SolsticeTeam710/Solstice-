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
            $table->foreignId('id_pesanan')->constrained('pesanan', 'id_pesanan')->onDelete('cascade');
            $table->dateTime('tanggal');
            $table->enum('metode_pembayaran', ['cash', 'qris', 'transfer', 'debit']);
            $table->decimal('bayar', 10, 2);
            $table->decimal('kembalian', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total', 10, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
