<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PesananController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'id_users' => 'required|integer',
            'total_harga' => 'required|numeric',
            'catatan' => 'nullable|string',
        ]);

        $orderId = 'ORD-' . date('Ymd-His');

        $idPesanan = DB::table('pesanan')->insertGetId([
            'id_users' => $request->id_users,
            'order_id' => $orderId,
            'tanggal' => now(),
            'total_harga' => $request->total_harga,
            'catatan' => $request->catatan,
            'status' => 'pending',
        ], 'id_pesanan');

        return response()->json([
            'message' => 'Pesanan berhasil dibuat',
            'order_id' => $orderId,
            'id_pesanan' => $idPesanan,
            'status' => 'pending'
        ], 201);
    }

    public function show($orderId)
    {
        $pesanan = DB::table('pesanan')
            ->where('order_id', $orderId)
            ->first();

        if (!$pesanan) {
            return response()->json([
                'message' => 'Pesanan tidak ditemukan'
            ], 404);
        }

        return response()->json($pesanan);
    }
}
