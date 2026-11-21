<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SalesTrendSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Data tren contoh hanya boleh dibuat di environment local atau testing.');
        }

        if (DB::table('pesanan')->where('order_id', 'like', 'DEMO-TREND-%')->exists()) {
            $this->command?->info('Data tren contoh sudah tersedia. Seeder dilewati.');
            return;
        }

        $userId = DB::table('users')->orderBy('id_users')->value('id_users');
        $menus = DB::table('menu')->orderBy('id_menu')->get(['id_menu', 'harga']);

        if (! $userId || $menus->isEmpty()) {
            throw new RuntimeException('Jalankan UserSeeder dan CatalogSeeder sebelum SalesTrendSeeder.');
        }

        DB::transaction(function () use ($userId, $menus) {
            for ($daysAgo = 59; $daysAgo >= 0; $daysAgo--) {
                $menu = $menus[$daysAgo % $menus->count()];
                $quantity = ($daysAgo % 3) + 1;
                $price = (float) $menu->harga;
                $subtotal = round($price * $quantity, 2);
                $total = round($subtotal * 1.10, 2);
                $method = $daysAgo % 2 === 0 ? 'CASH' : 'QRIS';
                $paid = $method === 'CASH' ? $total + 10000 : $total;
                $date = Carbon::today()
                    ->subDays($daysAgo)
                    ->setTime(9 + ($daysAgo % 9), ($daysAgo * 7) % 60);
                $orderId = 'DEMO-TREND-' . $date->format('Ymd');

                $pesananId = DB::table('pesanan')->insertGetId([
                    'id_users' => $userId,
                    'nama_pelanggan' => 'Pelanggan Contoh ' . str_pad((string) ($daysAgo + 1), 2, '0', STR_PAD_LEFT),
                    'metode_pembayaran' => $method,
                    'order_id' => $orderId,
                    'tanggal' => $date,
                    'total_harga' => $total,
                    'catatan' => null,
                    'status' => 'selesai',
                ], 'id_pesanan');

                DB::table('detail_pesanan')->insert([
                    'id_pesanan' => $pesananId,
                    'id_menu' => $menu->id_menu,
                    'jumlah_pesanan' => $quantity,
                    'harga_satuan' => $price,
                    'subtotal' => $subtotal,
                ]);

                DB::table('pembayaran')->insert([
                    'id_pesanan' => $pesananId,
                    'tanggal' => $date,
                    'metode_pembayaran' => $method,
                    'bayar' => $paid,
                    'kembalian' => $method === 'CASH' ? 10000 : 0,
                    'subtotal' => $subtotal,
                    'total' => $total,
                ]);
            }
        });

        $this->command?->info('60 transaksi contoh untuk tren dua bulan berhasil dibuat.');
    }
}
