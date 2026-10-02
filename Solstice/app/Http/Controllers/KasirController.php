<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KasirController extends Controller
{
    // Dashboard kasir
    public function dashboard()
    {
        $jumlahPesanan = DB::table('pesanan')->count();

        $pending = DB::table('pesanan')
            ->where('status', 'pending')
            ->count();

        $processing = DB::table('pesanan')
            ->where('status', 'diproses')
            ->count();

        return response()->json([
            'jumlah_pesanan' => $jumlahPesanan,
            'pending_payment' => $pending,
            'processing' => $processing,
        ]);
    }

    // Daftar pesanan
    public function orders()
    {
        $pesanan = DB::table('pesanan')
            ->orderBy('tanggal', 'desc')
            ->get();

        return response()->json($pesanan);
    }

    // Cari pesanan berdasarkan Order ID
    public function search(string $orderId)
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

    // Verifikasi pembayaran
    public function verifyPayment(string $orderId)
    {
        $pesanan = DB::table('pesanan')
            ->where('order_id', $orderId)
            ->first();

        if (!$pesanan) {
            return response()->json([
                'message' => 'Pesanan tidak ditemukan'
            ], 404);
        }

        DB::table('pesanan')
            ->where('order_id', $orderId)
            ->update([
                'status' => 'diproses'
            ]);

        return response()->json([
            'message' => 'Pembayaran berhasil diverifikasi',
            'order_id' => $orderId,
            'status' => 'diproses'
        ]);
    }
}
