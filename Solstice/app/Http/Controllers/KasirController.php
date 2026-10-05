<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
public function verify(string $orderId)
{
    DB::transaction(function () use ($orderId) {
        $order = DB::table('pesanan')
            ->where('order_id', $orderId)
            ->lockForUpdate()
            ->first();

        abort_if(!$order, 404, 'Pesanan tidak ditemukan.');
        abort_unless($order->status === 'pending', 409, 'Pesanan sudah diproses.');

        $items = DB::table('detail_pesanan')
            ->where('id_pesanan', $order->id_pesanan)
            ->get();

        $menus = DB::table('menu')
            ->whereIn('id_menu', $items->pluck('id_menu')->unique())
            ->lockForUpdate()
            ->get()
            ->keyBy('id_menu');

        foreach ($items as $item) {
            $menu = $menus->get($item->id_menu);

            if (!$menu || (int) $menu->stok < (int) $item->jumlah_pesanan) {
                throw ValidationException::withMessages([
                    'stok' => 'Stok menu tidak cukup untuk memproses pesanan ini.',
                ]);
            }

            DB::table('menu')
                ->where('id_menu', $item->id_menu)
                ->decrement('stok', (int) $item->jumlah_pesanan);
        }

        $subtotal = (float) DB::table('detail_pesanan')
            ->where('id_pesanan', $order->id_pesanan)
            ->sum('subtotal');

        $total = (float) $order->total_harga;

        DB::table('pembayaran')->insert([
            'id_pesanan' => $order->id_pesanan,
            'tanggal' => now(),
            'metode_pembayaran' => $order->metode_pembayaran ?? 'CASH',
            'bayar' => $total,
            'kembalian' => 0,
            'subtotal' => $subtotal,
            'total' => $total,
        ]);

        DB::table('pesanan')
            ->where('id_pesanan', $order->id_pesanan)
            ->update(['status' => 'diproses']);
    });

    return response()->json(['message' => 'Pembayaran diverifikasi.']);
}

public function updateStatus(Request $request, string $orderId)
{
    $data = $request->validate([
        'status' => ['required', 'in:siap,selesai'],
    ]);

    $order = DB::table('pesanan')->where('order_id', $orderId)->first();
    abort_if(!$order, 404, 'Pesanan tidak ditemukan.');

    $nextStatus = [
        'diproses' => 'siap',
        'siap' => 'selesai',
    ];

    abort_unless(
        ($nextStatus[$order->status] ?? null) === $data['status'],
        409,
        'Perubahan status pesanan tidak valid.'
    );

    DB::table('pesanan')
        ->where('order_id', $orderId)
        ->update(['status' => $data['status']]);

    return response()->json(['message' => 'Status pesanan diperbarui.']);
}

}
