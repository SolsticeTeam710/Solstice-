<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class AuthenticatedSessionController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
            'role'     => 'required|in:kasir,admin',
        ]);

        // Boleh login pakai email atau username
        $hasEmailColumn = Schema::hasColumn('users', 'email');
        $field = $hasEmailColumn && filter_var($data['login'], FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'username';

        // Nama lengkap juga diterima agar "Budi Santoso" bisa dipakai di kolom username.
        if ($field === 'username') {
            $userQuery = User::where('username', $data['login']);
            if (Schema::hasColumn('users', 'name')) {
                $userQuery->orWhere('name', $data['login']);
            }
            $user = $userQuery->first();
            if ($user) {
                $data['login'] = $user->username;
            }
        }

        $credentials = [
            $field     => $data['login'],
            'password' => $data['password'],
            'role'     => $data['role'],   // pastikan sesuai tab yang dipilih
        ];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $status = mb_strtolower((string) ($user->status ?? 'aktif'));
            $statusIsActive = in_array($status, ['aktif', 'active'], true);
            $flagIsActive = ! Schema::hasColumn('users', 'is_active') || (bool) $user->is_active;

            if (! $statusIsActive || ! $flagIsActive) {
                Auth::logout();

                return back()
                    ->withInput($request->only('login', 'role'))
                    ->withErrors(['login' => 'Akun ini tidak aktif. Hubungi administrator.']);
            }

            $request->session()->regenerate();

            return Auth::user()->role === 'admin'
                ? redirect('/admin/dashboard')
                : redirect('/kasir/dashboard');
        }

        return back()
            ->withInput($request->only('login', 'role'))
            ->withErrors(['login' => 'Username/email, password, atau peran tidak sesuai.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
