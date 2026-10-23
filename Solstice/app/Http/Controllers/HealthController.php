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
        $connectionStartedAt = hrtime(true);
        $connectionDurationMs = null;
        $menuQueryDurationMs = null;

        try {
            // Mengecek koneksi ke database Supabase PostgreSQL
            DB::connection()->getPdo();
            $connectionDurationMs = round((hrtime(true) - $connectionStartedAt) / 1_000_000, 2);

            // Mengecek jumlah menu yang tersedia
            $menuQueryStartedAt = hrtime(true);
            $menuCount = DB::table('menu')->count();
            $menuQueryDurationMs = round((hrtime(true) - $menuQueryStartedAt) / 1_000_000, 2);
        } catch (Throwable $exception) {
            report($exception);

            $payload = [
                'system_name' => 'Coffe Shop Order System',
                'sprint_stage' => 'Sprint 1 - Backend / API Foundation',
                'database' => 'Supabase PostgreSQL',
                'database_status' => 'DISCONNECTED',
                'menu_count' => null,
                'timestamp' => now()->toIso8601String(),
            ];

            if (app()->environment('local')) {
                $payload['timings_ms'] = [
                    'database_connect' => $connectionDurationMs ?? round((hrtime(true) - $connectionStartedAt) / 1_000_000, 2),
                    'menu_count_query' => $menuQueryDurationMs,
                ];
            }

            return response()->json($payload, 503);
        }

        $payload = [
            'system_name' => 'Coffe Shop Order System',
            'sprint_stage' => 'Sprint 1 - Backend / API Foundation',
            'database' => 'Supabase PostgreSQL',
            'database_status' => 'CONNECTED',
            'menu_count' => $menuCount,
            'timestamp' => now()->toIso8601String(),
        ];

        if (app()->environment('local')) {
            $payload['timings_ms'] = [
                'database_connect' => $connectionDurationMs,
                'menu_count_query' => $menuQueryDurationMs,
            ];
        }

        return response()->json($payload, 200);
    }
}
