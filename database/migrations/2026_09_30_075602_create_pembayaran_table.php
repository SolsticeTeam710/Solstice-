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
    Schema::create('pembayaran', function (Blueprint $table) {
    $table->id('id_pembayaran');

    $table->unsignedBigInteger('id_pesanan')->unique();

    $table->timestamp('tanggal')->nullable();
    $table->string('metode_pembayaran', 20);
    $table->decimal('bayar', 12, 2);
    $table->decimal('kembalian', 12, 2)->default(0);
    $table->decimal('subtotal', 12, 2);
    $table->decimal('total', 12, 2);

    $table->foreign('id_pesanan')
        ->references('id_pesanan')
        ->on('pesanan')
        ->cascadeOnDelete();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
