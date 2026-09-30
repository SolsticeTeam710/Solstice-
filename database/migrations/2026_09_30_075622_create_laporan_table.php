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
        Schema::create('laporan', function (Blueprint $table) {
    $table->id('id_laporan');

    $table->date('periode');
    $table->integer('total_transaksi')->default(0);
    $table->decimal('total_omzet', 12, 2)->default(0);
    $table->string('menu_terlaris', 150)->nullable();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan');
    }
};
