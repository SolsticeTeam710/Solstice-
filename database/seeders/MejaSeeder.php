<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MejaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([1, 2, 3] as $number) {
            if (! DB::table('meja')->where('nomor_meja', $number)->exists()) {
                DB::table('meja')->insert([
                    'nomor_meja' => $number,
                    'status' => 'kosong',
                ]);
            }
        }
    }
}
