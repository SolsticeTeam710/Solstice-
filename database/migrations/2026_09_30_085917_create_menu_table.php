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
            $table->foreignId('id_kategori')->constrained('kategori', 'id_kategori')->onDelete('cascade');
            $table->string('nama_menu');
            $table->decimal('harga', 10, 2);
            $table->string('foto')->nullable();
            $table->enum('status', ['tersedia', 'habis'])->default('tersedia');
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
