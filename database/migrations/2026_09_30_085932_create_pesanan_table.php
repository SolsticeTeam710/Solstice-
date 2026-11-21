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
            $table->foreignId('id_users')->constrained('users', 'id_users')->restrictOnDelete();
            $table->string('order_id', 50)->unique();
            $table->timestamp('tanggal')->useCurrent();
            $table->decimal('total_harga', 12, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->string('status', 20)->default('pending');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};
