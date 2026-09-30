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
        Schema::create('pesanan', function (Blueprint $table) {
    $table->id('id_pesanan');

    $table->unsignedBigInteger('id_users');

    $table->string('order_id', 50)->unique();
    $table->timestamp('tanggal')->nullable();
    $table->decimal('total_harga', 12, 2)->default(0);
    $table->text('catatan')->nullable();
    $table->string('status', 30);

    $table->foreign('id_users')
        ->references('id_users')
        ->on('users');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};
