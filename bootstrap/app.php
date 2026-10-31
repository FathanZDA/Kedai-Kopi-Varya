<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Pengunjung yang belum login diarahkan ke halaman login.
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Midtrans tidak mengirim token CSRF; webhook diverifikasi lewat
        // signature_key, bukan CSRF.
        $middleware->validateCsrfTokens(except: [
            'api/midtrans/notification',
        ]);

        // Pengguna yang sudah login tidak perlu membuka halaman login lagi.
        $middleware->redirectUsersTo(
            fn () => auth()->user()?->routeBeranda() ?? route('pelanggan-a.beranda')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
