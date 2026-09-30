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
        Schema::create('meja', function (Blueprint $table) {
    $table->id('id_meja');

    $table->unsignedBigInteger('id_pesanan')->nullable();

    $table->integer('no_meja');

    $table->foreign('id_pesanan')
        ->references('id_pesanan')
        ->on('pesanan')
        ->nullOnDelete();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meja');
    }
};
