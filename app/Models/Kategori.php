<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    use HasFactory;

    // Nama tabel di Supabase (karena singular/tidak ada huruf 's' di akhir)
    protected $table = 'kategori';

    // Primary key kustom
    protected $primaryKey = 'id_kategori';

    // Kolom yang diizinkan untuk diisi data (Mass Assignment)
    protected $fillable = [
        'nama_kategori',
    ];

    /**
     * Relasi: Satu Kategori memiliki banyak Menu (1 to Many)
     */
    public function menu()
    {
        return $this->hasMany(Menu::class, 'id_kategori', 'id_kategori');
    }
}
