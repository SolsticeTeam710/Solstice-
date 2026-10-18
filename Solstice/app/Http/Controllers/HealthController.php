<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Throwable;

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

            // Mengecek jumlah menu yang tersedia
            $menuCount = DB::table('menu')->count();
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'system_name' => 'Coffe Shop Order System',
                'sprint_stage' => 'Sprint 1 - Backend / API Foundation',
                'database' => 'Supabase PostgreSQL',
                'database_status' => 'DISCONNECTED',
                'menu_count' => null,
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }

        return response()->json([
            'system_name' => 'Coffe Shop Order System',
            'sprint_stage' => 'Sprint 1 - Backend / API Foundation',
            'database' => 'Supabase PostgreSQL',
            'database_status' => 'CONNECTED',
            'menu_count' => $menuCount,
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }
}
