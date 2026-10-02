<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bahan_baku', function (Blueprint $table) {
            $table->id('id_bahan');
            $table->string('nama_bahan')->unique();
            $table->string('kategori')->default('Lainnya');
            $table->string('satuan')->default('pcs');
            $table->decimal('stok', 10, 2)->default(0);
            $table->decimal('stok_minimum', 10, 2)->default(0);
            $table->timestamps();
        });

        foreach ([
            ['Biji Kopi Arabica Gayo', 'Kopi', 'Kg', 45, 10],
            ['Susu UHT Plain Premium', 'Susu & Sirup', 'L', 8, 15],
            ['Sirup Caramel Premium', 'Susu & Sirup', 'Botol', 12, 5],
            ['Cup Kopi Kertas 12oz', 'Kemasan', 'Pcs', 350, 500],
            ['Gula Aren Cair Lokal', 'Susu & Sirup', 'L', 0, 8],
        ] as [$nama, $kategori, $satuan, $stok, $minimum]) {
            DB::table('bahan_baku')->insert([
                'nama_bahan' => $nama, 'kategori' => $kategori, 'satuan' => $satuan,
                'stok' => $stok, 'stok_minimum' => $minimum,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bahan_baku');
    }
};
