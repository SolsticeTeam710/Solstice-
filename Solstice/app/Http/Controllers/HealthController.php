<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Exception;

class HealthController extends Controller
{
    /**
     * Mengecek kesiapan server dan status konektivitas database PostgreSQL.
     */
    public function check()
    {
        try {
            // Mengecek koneksi ke database Supabase PostgreSQL
            DB::connection()->getPdo();

            $dbStatus = "CONNECTED";

            // Mengecek jumlah menu yang tersedia
            $menuCount = DB::table('menu')->count();

        } catch (Exception $e) {
            $dbStatus = "DISCONNECTED: " . $e->getMessage();
            $menuCount = 0;
        }

        return response()->json([
            'system_name' => 'Coffe Shop Order System',
            'sprint_stage' => 'Sprint 1 - Backend / API Foundation',
            'database' => 'Supabase PostgreSQL',
            'database_status' => $dbStatus,
            'menu_count' => $menuCount,
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
