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
    Schema::create('menu', function (Blueprint $table) {
    $table->id('id_menu');

    $table->unsignedBigInteger('id_kategori')->nullable();

    $table->string('nama_menu', 150);
    $table->decimal('harga', 12, 2);
    $table->string('foto', 255)->nullable();
    $table->string('status', 20);
    $table->text('deskripsi')->nullable();
    $table->integer('stok')->default(0);
    $table->integer('batas_minimum')->default(0);

    $table->foreign('id_kategori')
        ->references('id_kategori')
        ->on('kategori')
        ->nullOnDelete();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu');
    }
};
