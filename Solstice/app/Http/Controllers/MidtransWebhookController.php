<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $orderId = (string) $request->input('order_id', '');
        $statusCode = (string) $request->input('status_code', '');
        $grossAmount = (string) $request->input('gross_amount', '');
        $signature = (string) $request->input('signature_key', '');
        $signatureSecretKey = (string) config('midtrans.signature_secret_key');

        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signature === '') {
            return response()->json(['message' => 'Notifikasi tidak lengkap.'], 400);
        }

        if ($signatureSecretKey === '') {
            return response()->json(['message' => 'Signature Secret Key belum dikonfigurasi.'], 500);
        }

        $expectedSignature = hash(
            'sha512',
            $orderId . $statusCode . $grossAmount . $signatureSecretKey
        );

        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json(['message' => 'Signature tidak valid.'], 403);
        }

        $transactionStatus = (string) $request->input('transaction_status', '');
$fraudStatus = strtolower((string) $request->input('fraud_status', 'accept'));

// QRIS yang belum selesai dibayar tidak mengubah pesanan.
if (
    $transactionStatus !== 'settlement' ||
    $statusCode !== '200' ||
    ($request->filled('fraud_status') && $fraudStatus !== 'accept')
) {
    return response()->json(['received' => true]);
}

$result = DB::transaction(function () use ($orderId, $grossAmount) {
    $order = DB::table('pesanan')
        ->where('order_id', $orderId)
        ->lockForUpdate()
        ->first();

    if (! $order) {
        return 'not_found';
    }

    // Mencegah notifikasi yang sama memproses pesanan dua kali.
    if ($order->status !== 'pending') {
        return 'already_processed';
    }

    if (strtoupper((string) $order->metode_pembayaran) !== 'QRIS') {
        return 'wrong_payment_method';
    }

    if (abs((float) $order->total_harga - (float) $grossAmount) > 0.01) {
        return 'amount_mismatch';
    }

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

        if (! $menu || (int) $menu->stok < (int) $item->jumlah_pesanan) {
            return 'stock_unavailable';
        }
    }

    foreach ($items as $item) {
        DB::table('menu')
            ->where('id_menu', $item->id_menu)
            ->decrement('stok', (int) $item->jumlah_pesanan);
    }

    $total = (float) $order->total_harga;
    $subtotal = (float) $items->sum('subtotal');

    DB::table('pembayaran')->insert([
        'id_pesanan' => $order->id_pesanan,
        'tanggal' => now(),
        'metode_pembayaran' => 'QRIS',
        'bayar' => $total,
        'kembalian' => 0,
        'subtotal' => $subtotal,
        'total' => $total,
    ]);

    DB::table('pesanan')
        ->where('id_pesanan', $order->id_pesanan)
        ->update(['status' => 'selesai']);

    DB::table('meja')
        ->where('id_pesanan', $order->id_pesanan)
        ->update([
            'id_pesanan' => null,
            'status' => 'kosong',
        ]);

    return 'processed';
});

if ($result === 'not_found') {
    return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
}

if (! in_array($result, ['processed', 'already_processed'], true)) {
    return response()->json(['message' => 'Notifikasi tidak dapat diproses.'], 422);
}

Cache::forever('admin.metrics.version', (string) Str::uuid());

return response()->json(['received' => true]);
    }
}
