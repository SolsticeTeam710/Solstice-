<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
public function handle(Request $request, Closure $next, string ...$roles)
{
    if (!Auth::check()) {
        // Tambahan untuk request API
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Silakan login terlebih dahulu.'], 401);
        }
        return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
    }

    // Akun yang dinonaktifkan harus kehilangan akses meski masih punya sesi lama.
    $user = Auth::user();
    $status = mb_strtolower((string) ($user->status ?? 'aktif'));
    $statusIsActive = in_array($status, ['aktif', 'active'], true);
    $flagIsActive = $user->is_active === null || (bool) $user->is_active;

    if (! $statusIsActive || ! $flagIsActive) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Akun ini tidak aktif. Hubungi administrator.'], 401);
        }

        return redirect()->route('login')->withErrors([
            'login' => 'Akun ini tidak aktif. Hubungi administrator.',
        ]);
    }

    // Menggunakan in_array untuk mendukung banyak role sekaligus
    if (!in_array(Auth::user()->role, $roles)) {
        // Tambahan untuk request API
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Akses Ditolak.'], 403);
        }
        abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk membuka halaman ini.');
    }

    return $next($request);
}
}
