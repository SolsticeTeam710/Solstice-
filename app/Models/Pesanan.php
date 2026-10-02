<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pesanan extends Model
{
    use HasFactory;

    protected $table = 'pesanan';
    protected $primaryKey = 'id_pesanan';

public $timestamps = false;

    protected $fillable = [
        'id_meja',
        'id_users',
        'total_harga',
        'status_pesanan',
    ];

    public function detailPesanan()
    {
        return $this->hasMany(DetailPesanan::class, 'id_pesanan', 'id_pesanan');
    }

    public function meja()
    {
        return $this->belongsTo(Meja::class, 'id_meja', 'id_meja');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_users', 'id_users');
    }
}
