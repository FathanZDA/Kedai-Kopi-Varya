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
    <div class="relative flex min-h-screen flex-col overflow-hidden px-4 py-7 sm:px-6 sm:py-10">
        {{-- Watermark logo: pengisi ruang kosong tanpa garis-garis yang ramai. --}}
        <img src="{{ asset('images/brand/varya-mark.png') }}"
             alt=""
             aria-hidden="true"
             class="pointer-events-none absolute -right-20 bottom-[-70px] hidden w-[460px] max-w-[45vw] opacity-[0.06] select-none lg:block">

        {{-- Identitas kedai, mengikuti desain di design/auth. --}}
        <header class="mx-auto flex w-full max-w-6xl items-center gap-3">
            <span class="grid h-12 w-12 shrink-0 place-items-center">
                <img src="{{ asset('images/brand/varya-mark.png') }}"
                     alt=""
                     width="228"
                     height="240"
                     class="h-12 w-auto object-contain">
            </span>

            <span class="font-display text-[17px] leading-none font-black tracking-[0.12em] text-ink uppercase sm:text-[20px]">
                Kedai Varya
            </span>
        </header>

        <main class="relative mx-auto flex w-full max-w-6xl flex-1 items-center justify-center pt-10 sm:pt-14">
            <div class="grid w-full items-center gap-14 lg:grid-cols-[minmax(0,1fr)_500px] lg:gap-16">
                {{-- Panel informasi: mengisi ruang desktop tanpa dekorasi garis. --}}
                <aside class="hidden lg:block">
                    <p class="tag text-[11px] font-bold tracking-[0.18em] text-varya-dark uppercase">
                        Area kasir &amp; admin
                    </p>

                    <h2 class="mt-4 font-display text-[46px] leading-[0.95] font-black text-ink uppercase">
                        Satu tempat<br>untuk seluruh kedai
                    </h2>

                    <p class="mt-5 max-w-md font-serif text-[15px] leading-relaxed text-ink-soft">
                        Mulai dari pesanan masuk sampai rekap harian, semuanya tercatat rapi di satu sistem.
                    </p>

                    <ul class="mt-9 space-y-4">
                        @php
                            $loginPoints = [
                                ['title' => 'Pesanan masuk', 'desc' => 'Pantau antrean dan ubah status pesanan hari ini.', 'icon' => 'M6 3h9l4 4v14H6V3Zm9 0v4h4M9 12h7M9 16h5'],
                                ['title' => 'Menu & ketersediaan', 'desc' => 'Atur harga, foto, dan status habis dalam sekali klik.', 'icon' => 'M5 4h11v16H5V4Zm11 5h3v8a3 3 0 0 1-3 3M8 8h5M8 12h5M8 16h3'],
                                ['title' => 'Laporan penjualan', 'desc' => 'Lihat pendapatan dan menu terlaris tiap periode.', 'icon' => 'M4 20V9m5 11V4m5 16v-7m5 7V7'],
                            ];
                        @endphp

                        @foreach ($loginPoints as $point)
                            <li class="flex items-start gap-4">
                                <span class="grid h-11 w-11 shrink-0 place-items-center border-2 border-ink bg-white shadow-[3px_3px_0_0_#1b1c1d]">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"
                                         class="text-varya-dark" aria-hidden="true">
                                        <path d="{{ $point['icon'] }}"/>
                                    </svg>
                                </span>

                                <span class="pt-0.5">
                                    <span class="block font-mono text-[11px] font-bold tracking-[0.1em] text-ink uppercase">
                                        {{ $point['title'] }}
                                    </span>
                                    <span class="mt-1 block max-w-xs font-serif text-[13px] leading-relaxed text-ink-soft">
                                        {{ $point['desc'] }}
                                    </span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </aside>

                <section class="auth-card w-full max-w-[500px] justify-self-center lg:justify-self-end" aria-labelledby="login-title">
                <div class="admin-stripes h-3 border-b-[3px] border-ink" aria-hidden="true"></div>

                <div class="px-5 py-8 sm:px-10 sm:py-10">
                    <p class="tag text-[11px] font-bold tracking-[0.16em] text-varya-dark">
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

                            <p class="font-serif text-[13px] leading-relaxed text-varya-dark">
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
                                    <p class="tag text-[11px] font-bold text-rust">// Login gagal</p>

                                    <ul class="mt-1.5 space-y-1 font-serif text-[13px] leading-relaxed text-ink">
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
                                <p id="email-error" class="mt-2 font-serif text-[12px] text-rust">
                                    {{ $emailError }}
                                </p>
                            @else
                                <p id="email-help" class="mt-2 font-serif text-[12px] text-muted">
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
                                <p id="password-error" class="mt-2 font-serif text-[12px] text-rust">
                                    {{ $passwordError }}
                                </p>
                            @else
                                <p id="password-help" class="mt-2 font-serif text-[12px] text-muted">
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

                    <p class="mt-7 border-t-2 border-dashed border-line-strong pt-5 text-center font-serif text-[12px] leading-relaxed text-muted">
                        Akun kasir dan admin dibuat oleh pemilik kedai. Tidak ada pendaftaran publik.
                    </p>
                </div>
                </section>
            </div>
        </main>

        <p class="mx-auto mt-8 w-full max-w-6xl text-center font-mono text-[11px] tracking-[0.14em] text-varya-muted uppercase">
            Kedai Kopi Varya // Sistem Pesanan &amp; Kasir
        </p>
    </div>
</body>
</html>
