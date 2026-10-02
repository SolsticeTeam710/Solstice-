<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Kategori;
use App\Models\Menu;
use App\Models\Meja;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

    User::create([
        'username' => 'admin',
        'password' => Hash::make('password'),
        'role' => 'admin',
        'status' => 'Active',
    ]);

    User::create([
        'username' => 'kasir1',
        'password' => Hash::make('password'),
        'role' => 'kasir',
        'status' => 'Active',
    ]);

    // 3. Data Meja
        Meja::create(['nomor_meja' => 1, 'status' => 'kosong']);
        Meja::create(['nomor_meja' => 2, 'status' => 'kosong']);
        Meja::create(['nomor_meja' => 3, 'status' => 'kosong']);

    // 2. Data Kategori
    $coffee = Kategori::create(['nama_kategori' => 'Coffee']);
    $nonCoffee = Kategori::create(['nama_kategori' => 'Non-Coffee']);
    $makanan = Kategori::create(['nama_kategori' => 'Makanan']);

    // 3. Data Menu
    Menu::create([
        'id_kategori' => $coffee->id,
        'nama_menu' => 'Es Kopi Susu Aren',
        'harga' => 18000,
        'stok' => 10,
    ]);

    Menu::create([
        'id_kategori' => $nonCoffee->id,
        'nama_menu' => 'Matcha Latte',
        'harga' => 22000,
        'stok' => 10,
    ]);

    Menu::create([
        'id_kategori' => $makanan->id,
        'nama_menu' => 'Nasi Goreng Spesial',
        'harga' => 25000,
        'stok' => 10,
    ]);
}
}
