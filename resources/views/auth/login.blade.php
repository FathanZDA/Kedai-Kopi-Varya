@php
    $emailError = $errors->first('email');
    $passwordError = $errors->first('password');
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — Kedai Kopi Varya</title>
    <meta name="description" content="Halaman masuk kasir dan admin Kedai Kopi Varya.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..600&family=Playfair+Display:wght@700;800;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])
</head>
<body class="auth-shell min-h-screen font-serif text-ink antialiased">
    <div class="flex min-h-screen flex-col px-4 py-7 sm:px-6 sm:py-10">
        {{-- Identitas kedai, mengikuti desain di design/auth. --}}
        <header class="mx-auto flex w-full max-w-6xl items-center gap-3">
            <span class="grid h-12 w-12 shrink-0 place-items-center border-2 border-ink bg-white shadow-[3px_3px_0_0_#1b1c1d]">
                <img src="{{ asset('images/brand/varya-mark.png') }}"
                     alt="Logo Kedai Kopi Varya"
                     class="h-9 w-9 object-contain">
            </span>

            <span class="font-display text-[17px] leading-none font-black tracking-[0.12em] text-ink uppercase sm:text-[20px]">
                Kedai Varya
            </span>
        </header>

        <main class="mx-auto flex w-full max-w-6xl flex-1 items-start justify-center pt-10 sm:pt-14">
            <section class="auth-card w-full max-w-[500px]" aria-labelledby="login-title">
                <div class="admin-stripes h-3 border-b-[3px] border-ink" aria-hidden="true"></div>

                <div class="px-5 py-8 sm:px-10 sm:py-10">
                    <p class="tag text-[9px] font-bold tracking-[0.16em] text-varya-dark">
                        Silakan masukkan detail anda
                    </p>

                    <h1 id="login-title" class="mt-3 font-display text-[38px] leading-none font-black text-ink sm:text-[44px]">
                        Selamat datang
                    </h1>

                    <p class="mt-3 max-w-sm font-serif text-[13px] leading-relaxed text-ink-soft">
                        Masuk untuk mengelola kedai. Kasir akan diarahkan ke Buat Pesanan, admin ke Dashboard.
                    </p>

                    @if (session('status'))
                        <div role="status"
                             class="mt-6 flex items-start gap-3 border-[3px] border-varya-dark bg-varya-light/50 px-4 py-3">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"
                                 class="mt-0.5 shrink-0 text-varya-dark" aria-hidden="true">
                                <path d="m5 12 4.5 4.5L19 7"/>
                            </svg>

                            <p class="font-serif text-[12px] leading-relaxed text-varya-dark">
                                {{ session('status') }}
                            </p>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div role="alert"
                             class="mt-6 border-[3px] border-rust bg-[#fdf0eb] px-4 py-3.5">
                            <div class="flex items-start gap-3">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"
                                     class="mt-0.5 shrink-0 text-rust" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9"/>
                                    <path d="M12 7v6M12 16.5v.5"/>
                                </svg>

                                <div>
                                    <p class="tag text-[8px] font-bold text-rust">// Login gagal</p>

                                    <ul class="mt-1.5 space-y-1 font-serif text-[12px] leading-relaxed text-ink">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form method="POST"
                          action="{{ route('login.store') }}"
                          novalidate
                          class="mt-7 space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="auth-label">Email</label>

                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 left-0 grid w-11 place-items-center text-muted"
                                      aria-hidden="true">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="5" width="18" height="14" rx="1.5"/>
                                        <path d="m3.5 6.5 8.5 6 8.5-6"/>
                                    </svg>
                                </span>

                                <input id="email"
                                       name="email"
                                       type="email"
                                       value="{{ old('email') }}"
                                       inputmode="email"
                                       autocomplete="username"
                                       autocapitalize="off"
                                       spellcheck="false"
                                       placeholder="nama@email.com"
                                       aria-describedby="{{ $emailError ? 'email-error' : 'email-help' }}"
                                       @if ($emailError) aria-invalid="true" @endif
                                       @class([
                                           'auth-input pl-11',
                                           'auth-input-error' => (bool) $emailError,
                                       ])>
                            </div>

                            @if ($emailError)
                                <p id="email-error" class="mt-1.5 font-serif text-[11px] text-rust">
                                    {{ $emailError }}
                                </p>
                            @else
                                <p id="email-help" class="mt-1.5 font-serif text-[11px] text-muted">
                                    Gunakan email akun kasir atau admin.
                                </p>
                            @endif
                        </div>

                        <div>
                            <label for="password" class="auth-label">Kata sandi</label>

                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 left-0 grid w-11 place-items-center text-muted"
                                      aria-hidden="true">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="4" y="10" width="16" height="11" rx="1.5"/>
                                        <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                                    </svg>
                                </span>

                                <input id="password"
                                       name="password"
                                       type="password"
                                       autocomplete="current-password"
                                       placeholder="••••••••••••"
                                       aria-describedby="{{ $passwordError ? 'password-error' : 'password-help' }}"
                                       @if ($passwordError) aria-invalid="true" @endif
                                       @class([
                                           'auth-input pl-11',
                                           'auth-input-error' => (bool) $passwordError,
                                       ])>
                            </div>

                            @if ($passwordError)
                                <p id="password-error" class="mt-1.5 font-serif text-[11px] text-rust">
                                    {{ $passwordError }}
                                </p>
                            @else
                                <p id="password-help" class="mt-1.5 font-serif text-[11px] text-muted">
                                    Kata sandi bersifat rahasia dan tidak disimpan di browser.
                                </p>
                            @endif
                        </div>

                        <label class="auth-checkbox cursor-pointer select-none">
                            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                            Ingat saya
                        </label>

                        <button type="submit" class="auth-submit group">
                            <span>Masuk ke akun</span>

                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"
                                 class="transition-transform group-hover:translate-x-0.5" aria-hidden="true">
                                <path d="M4 12h15"/>
                                <path d="m13 6 6 6-6 6"/>
                            </svg>
                        </button>
                    </form>

                    <p class="mt-7 border-t-2 border-dashed border-line-strong pt-5 text-center font-serif text-[11px] leading-relaxed text-muted">
                        Akun kasir dan admin dibuat oleh pemilik kedai. Tidak ada pendaftaran publik.
                    </p>
                </div>
            </section>
        </main>

        <p class="mx-auto mt-8 w-full max-w-6xl text-center font-mono text-[8px] tracking-[0.14em] text-varya-muted uppercase">
            Kedai Kopi Varya // Sistem Pesanan &amp; Kasir
        </p>
    </div>
</body>
</html>
