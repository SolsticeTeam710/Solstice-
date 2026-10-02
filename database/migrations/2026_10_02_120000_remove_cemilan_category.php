<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $makanan = DB::table('kategori')->whereRaw('LOWER(nama_kategori) = ?', ['makanan'])->first();
        if (! $makanan) {
            $id = DB::table('kategori')->insertGetId(['nama_kategori' => 'Makanan'], 'id_kategori');
            $makanan = (object) ['id_kategori' => $id];
        }

        $cemilanIds = DB::table('kategori')->whereRaw('LOWER(nama_kategori) = ?', ['cemilan'])->pluck('id_kategori');
        if ($cemilanIds->isNotEmpty()) {
            DB::table('menu')->whereIn('id_kategori', $cemilanIds)->update(['id_kategori' => $makanan->id_kategori]);
            DB::table('kategori')->whereIn('id_kategori', $cemilanIds)->delete();
        }
    }

    public function down(): void
    {
        if (! DB::table('kategori')->whereRaw('LOWER(nama_kategori) = ?', ['cemilan'])->exists()) {
            DB::table('kategori')->insert(['nama_kategori' => 'Cemilan']);
        }
    }
};
