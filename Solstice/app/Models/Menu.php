<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $table = 'menu';
    protected $primaryKey = 'id_menu';

    public $timestamps = false;

    protected $fillable = [
        'id_kategori',
        'nama_menu',
        'harga',
        'stok',
        'foto',
    ];

    /**
     * Relasi: Menu dimiliki oleh satu Kategori (Belongs To)
     */
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'id_kategori', 'id_kategori');
    }
}
