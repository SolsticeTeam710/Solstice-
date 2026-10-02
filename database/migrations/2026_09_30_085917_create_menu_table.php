<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('menu', function (Blueprint $table) {
    $table->id('id_menu');
    $table->foreignId('id_kategori')->nullable()->constrained('kategori', 'id_kategori')->nullOnDelete();
    $table->string('nama_menu', 75);
    $table->decimal('harga', 12, 2)->default(0);
    $table->string('foto', 255)->nullable();
    $table->string('status', 20)->default('tersedia');
    $table->text('deskripsi')->nullable();
    $table->integer('stok')->default(0);
    $table->integer('batas_minimum')->default(0);
});
    }

    public function down(): void
    {
        Schema::dropIfExists('menu');
    }
};
