<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [];
        $categoryAliases = [
            'Coffee' => ['coffee', 'kopi'],
            'Non-Coffee' => ['non-coffee', 'non coffee', 'non-kopi'],
            'Makanan' => ['makanan'],
        ];

        foreach ($categoryAliases as $name => $aliases) {
            $category = DB::table('kategori')
                ->whereIn(DB::raw('LOWER(nama_kategori)'), $aliases)
                ->first();
            if (! $category) {
                $id = DB::table('kategori')->insertGetId(['nama_kategori'=>$name], 'id_kategori');
                $category = (object) ['id_kategori'=>$id];
            }
            $categories[$name] = $category->id_kategori;
        }

        $menus = [
            ['Ice Kopi Susu Aren','Coffee',18000,150,20],
            ['Ice Caramel Macchiato','Coffee',24000,85,15],
            ['Matcha Latte','Non-Coffee',22000,120,15],
            ['Almond Croissant','Makanan',20000,12,5],
            ['Nasi Goreng Kampung','Makanan',28000,0,5],
        ];
        foreach ($menus as [$name,$category,$price,$stock,$minimum]) {
            if (! DB::table('menu')->where('nama_menu',$name)->exists()) {
                DB::table('menu')->insert([
                    'id_kategori'=>$categories[$category], 'nama_menu'=>$name, 'harga'=>$price,
                    'stok'=>$stock, 'batas_minimum'=>$minimum, 'status'=>$stock > 0 ? 'tersedia' : 'habis',
                ]);
            }
        }
    }
}
