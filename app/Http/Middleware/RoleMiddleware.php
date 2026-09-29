<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        // 1. Verifikasi apakah sesi login aktif
        if (!Auth::check()) {
            return redirect()
                ->route('login')
                ->with('error', 'Silakan login terlebih dahulu.');
        }

        // 2. Verifikasi kesesuaian hak akses (role) pengguna
        if (Auth::user()->role !== $role) {
            abort(
                403,
                'Akses Ditolak: Anda tidak memiliki wewenang untuk membuka halaman ini.'
            );
        }

        // 3. Jika role sesuai, izinkan akses
        return $next($request);
    }
}
