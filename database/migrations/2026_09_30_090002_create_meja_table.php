<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meja', function (Blueprint $table) {
            $table->id('id_meja');
            $table->foreignId('id_pesanan')->nullable()->constrained('pesanan', 'id_pesanan')->onDelete('set null');
            $table->integer('no_meja');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meja');
    }
};
