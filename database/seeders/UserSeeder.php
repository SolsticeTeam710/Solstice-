<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Baca kolom yang benar-benar ada di tabel users (database kamu bisa berbeda dari bawaan Laravel)
        $columns = Schema::getColumnListing('users');
        $this->command->info('Kolom tabel users: ' . implode(', ', $columns));

        // Kolom untuk nama: pakai yang tersedia di tabelmu
        $nameColumn = collect(['name', 'nama', 'nama_lengkap', 'full_name'])
            ->first(fn ($c) => in_array($c, $columns));

        // Beri peringatan kalau kolom penting tidak ada
        foreach (['username', 'role', 'status'] as $needed) {
            if (! in_array($needed, $columns)) {
                $this->command->warn("Kolom '{$needed}' tidak ada di tabel users, login berdasarkan kolom ini tidak akan jalan.");
            }
        }
        if (! $nameColumn) {
            $this->command->warn('Kolom nama tidak ditemukan, nama user tidak disimpan.');
        }

        $users = [
            ['username' => 'admin', 'password' => 'password', 'nama' => 'Admin Utama', 'email' => null, 'role' => 'admin'],
            ['username' => 'kasir1', 'password' => 'password', 'nama' => 'Kasir 1', 'email' => null, 'role' => 'kasir'],
        ];

        foreach ($users as $u) {
            $row = [
                'username'   => $u['username'],
                'email'      => $u['email'],
                'password'   => Hash::make($u['password']),
                'role'       => $u['role'],
                'status'     => 'aktif',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($nameColumn) {
                $row[$nameColumn] = $u['nama'];
            }

            // Hanya isi kolom yang memang ada
            $row = array_intersect_key($row, array_flip($columns));

            // Cari user lama lewat username (kalau tidak ada kolomnya, lewat email)
            $key = in_array('username', $columns)
                ? ['username' => $u['username']]
                : ['email' => $u['email']];

            DB::table('users')->updateOrInsert($key, $row);
        }

        $this->command->info('Login Admin: admin / password | Login Kasir: kasir1 / password');
    }
}
