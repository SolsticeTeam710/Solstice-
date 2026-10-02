<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembayaranController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'id_pesanan' => 'required|integer',
            'metode_pembayaran' => 'required|string',
            'bayar' => 'required|numeric',
            'total' => 'required|numeric',
        ]);

        $kembalian = $request->bayar - $request->total;

        $idPembayaran = DB::table('pembayaran')->insertGetId([
            'id_pesanan' => $request->id_pesanan,
            'tanggal' => now(),
            'metode_pembayaran' => $request->metode_pembayaran,
            'bayar' => $request->bayar,
            'kembalian' => $kembalian,
            'subtotal' => $request->total,
            'total' => $request->total,
        ], 'id_pembayaran');

        return response()->json([
            'message' => 'Pembayaran berhasil disimpan',
            'id_pembayaran' => $idPembayaran,
            'status' => 'MENUNGGU_VERIFIKASI'
        ], 201);
    }
}
