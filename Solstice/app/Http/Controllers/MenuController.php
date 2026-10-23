<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    /** Return the customer catalog, including sold-out menu items. */
    public function index()
    {
        $menus = DB::table('menu')
            ->leftJoin('kategori', 'menu.id_kategori', '=', 'kategori.id_kategori')
            ->select('menu.*', 'kategori.nama_kategori')
            ->orderBy('menu.nama_menu')
            ->get();

        return response()->json($menus);
    }

    public function show(int $id)
    {
        $menu = DB::table('menu')
            ->leftJoin('kategori', 'menu.id_kategori', '=', 'kategori.id_kategori')
            ->where('menu.id_menu', $id)
            ->where('menu.status', 'tersedia')
            ->select('menu.*', 'kategori.nama_kategori')
            ->first();

        if (! $menu) {
            return response()->json(['message' => 'Menu tidak ditemukan'], 404);
        }

        return response()->json($menu);
    }
}
