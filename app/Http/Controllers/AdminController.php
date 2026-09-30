<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // Dashboard admin
    public function dashboard()
    {
        return response()->json([
            'total_user' => DB::table('users')->count(),
            'total_menu' => DB::table('menu')->count(),
            'total_kategori' => DB::table('kategori')->count(),
            'total_pesanan' => DB::table('pesanan')->count(),
        ]);
    }

    // Kelola pengguna
    public function users()
    {
        $users = DB::table('users')
            ->select(
                'id_users',
                'username',
                'role',
                'status',
                'created_at'
            )
            ->get();

        return response()->json($users);
    }

    // Kelola menu
    public function menu()
    {
        $menu = DB::table('menu')->get();

        return response()->json($menu);
    }

    // Kelola kategori
    public function kategori()
    {
        $kategori = DB::table('kategori')->get();

        return response()->json($kategori);
    }

    // Kelola stok
    public function stok()
    {
        $stok = DB::table('menu')
            ->select(
                'id_menu',
                'nama_menu',
                'stok',
                'batas_minimum'
            )
            ->get();

        return response()->json($stok);
    }

    // Peringatan stok menipis
    public function stokMenipis()
    {
        $menu = DB::table('menu')
            ->whereColumn('stok', '<=', 'batas_minimum')
            ->get();

        return response()->json($menu);
    }

    // Laporan penjualan
    public function laporan()
    {
        $laporan = DB::table('laporan')
            ->orderBy('periode', 'desc')
            ->get();

        return response()->json($laporan);
    }
}
