<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Halaman login bersama untuk kasir dan admin.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Proses login lalu arahkan sesuai role akun.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended($request->user()->routeBeranda());
    }

    /**
     * Akhiri sesi login.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Anda telah keluar dari akun.');
    }
}
