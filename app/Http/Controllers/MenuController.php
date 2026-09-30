<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function index()
    {
        $menu = DB::table('menu')
            ->where('status', 'AKTIF')
            ->get();

        return response()->json($menu);
    }

    public function show($id)
    {
        $menu = DB::table('menu')
            ->where('id_menu', $id)
            ->first();

        if (!$menu) {
            return response()->json([
                'message' => 'Menu tidak ditemukan'
            ], 404);
        }

        return response()->json($menu);
    }
}
