<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesanan', function (Blueprint $table) {
            $table->id('id_pesanan');
            $table->foreignId('id_users')->constrained('users', 'id_users')->onDelete('cascade');
            $table->timestamp('order_id');
            $table->dateTime('tanggal');
            $table->decimal('total_harga', 10, 2);
            $table->text('catatan')->nullable();
            $table->enum('status', ['pending', 'diproses', 'selesai', 'dibatalkan'])->default('pending');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};
