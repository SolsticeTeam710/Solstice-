<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PesananController extends Controller
{
public function store(Request $request)
{
    $data = $request->validate([
        'nama_pelanggan' => ['required', 'string', 'max:100'],
        'metode_pembayaran' => ['required', 'in:CASH,QRIS'],
        'catatan' => ['nullable', 'string', 'max:1000'],
        'items' => ['required', 'array', 'min:1'],
        'items.*.id_menu' => ['required', 'integer', 'distinct'],
        'items.*.jumlah_pesanan' => ['required', 'integer', 'min:1', 'max:99'],
    ]);

    $order = DB::transaction(function () use ($data) {
        $items = collect($data['items']);
        $menuIds = $items->pluck('id_menu')->unique()->values();

        $menus = DB::table('menu')
            ->whereIn('id_menu', $menuIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id_menu');

        if ($menus->count() !== $menuIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'Ada menu yang tidak ditemukan.',
            ]);
        }

        $subtotal = 0;
        $details = [];

        foreach ($items as $item) {
            $menu = $menus->get($item['id_menu']);
            $quantity = (int) $item['jumlah_pesanan'];

            if ($menu->status !== 'tersedia' || (int) $menu->stok < $quantity) {
                throw ValidationException::withMessages([
                    'items' => "Stok menu {$menu->nama_menu} tidak mencukupi.",
                ]);
            }

            $price = (float) $menu->harga;
            $lineSubtotal = round($price * $quantity, 2);
            $subtotal += $lineSubtotal;

            $details[] = [
                'id_menu' => $menu->id_menu,
                'jumlah_pesanan' => $quantity,
                'harga_satuan' => $price,
                'subtotal' => $lineSubtotal,
            ];
        }

        // 10% mengikuti perhitungan di halaman pelanggan saat ini.
        $total = round($subtotal + ($subtotal * 0.10), 2);

        $orderId = 'ORD-' . Str::upper(Str::random(12));

        $pesananId = DB::table('pesanan')->insertGetId([
            'id_users' => null,
            'nama_pelanggan' => $data['nama_pelanggan'],
            'metode_pembayaran' => $data['metode_pembayaran'],
            'order_id' => $orderId,
            'tanggal' => now(),
            'total_harga' => $total,
            'catatan' => $data['catatan'] ?? null,
            'status' => 'pending',
        ], 'id_pesanan');

        foreach ($details as $detail) {
            DB::table('detail_pesanan')->insert([
                'id_pesanan' => $pesananId,
                ...$detail,
            ]);
        }

        return [
            'order_id' => $orderId,
            'total_harga' => $total,
            'status' => 'pending',
        ];
    });

    return response()->json([
        'message' => 'Pesanan berhasil dibuat.',
        'order' => $order,
    ], 201);
}

    public function show(string $orderId)
{
    $pesanan = DB::table('pesanan')
        ->select('order_id', 'status')
        ->where('order_id', $orderId)
        ->first();

    if (!$pesanan) {
        return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
    }

    return response()->json($pesanan);
}
}
