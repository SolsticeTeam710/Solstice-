<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('layouts.login');
    }

    // Dipanggil oleh route('login.process')
    public function process(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role'     => ['required', 'in:kasir,admin'],
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required'    => 'Isi email atau username.',
            'password.required' => 'Isi password.',
        ]);

        // Boleh login pakai email atau username
        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $ok = Auth::attempt([
            $field      => $data['login'],
            'password'  => $data['password'],
            'role'      => $data['role'],   // tab Kasir hanya untuk akun kasir, tab Admin hanya untuk admin
            'is_active' => true,            // akun nonaktif ditolak
        ]);

        if (! $ok) {
            return back()
                ->withInput($request->only('login', 'role'))
                ->with('error', 'Username/email atau password salah, atau akun tidak aktif.');
        }

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
