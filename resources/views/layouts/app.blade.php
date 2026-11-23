<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="only light">
    <meta name="theme-color" content="#1f4d3d">
    <meta name="description" content="Aplikasi pencatatan pesanan usaha catering">
    <link rel="manifest" href="{{ url('/manifest.json') }}">
    <link rel="icon" href="{{ url('/icons/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ url('/icons/icon-192.png') }}">
    <link rel="stylesheet" href="{{ url('/css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <title>@yield('title', 'CateringKu') — CateringKu</title>
    @stack('head')
</head>
<body>
    <a href="#main-content" class="skip-link">Lewati ke konten utama</a>
    <header class="appbar">
        <a class="brand" href="{{ url('/') }}">
            @include('layouts.brand-mark')
            <span>CateringKu</span>
        </a>
        @auth
        <nav class="nav" aria-label="Navigasi utama">
            <a href="{{ url('/dashboard') }}" class="{{ request()->is('dashboard*') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ url('/pesanan') }}" class="{{ request()->is('pesanan*') ? 'active' : '' }}">Pesanan</a>
            <a href="{{ url('/menu') }}" class="{{ request()->is('menu*') ? 'active' : '' }}">Menu</a>
            <a href="{{ url('/laporan/porsi') }}" class="{{ request()->is('laporan*') ? 'active' : '' }}">Laporan</a>
        </nav>
        @endauth
        <div class="appbar-actions">
            @auth
            <span class="admin">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/></svg>
                {{ auth()->user()->name }}
            </span>
            <form method="POST" action="{{ url('/logout') }}" style="margin:0">
                @csrf
                <button type="submit" class="btn btn-sm btn-logout">Keluar</button>
            </form>
            @else
            <a href="{{ url('/login') }}" class="btn btn-sm btn-logout">Masuk</a>
            @endauth
        </div>
    </header>

    <main class="wrap" id="main-content" tabindex="-1">
        @if(session('ok'))
            <div class="alert alert-ok" role="status">{{ session('ok') }}</div>
        @endif
        @if(session('err'))
            <div class="alert alert-err" role="alert">{{ session('err') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-err" role="alert">
                <strong>Periksa kembali isian:</strong>
                <ul class="list">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="app-footer">
        <span><strong>CateringKu</strong> · Kelola pesanan, sajikan kebahagiaan.</span>
        <span>Ruang kerja usaha catering Anda</span>
    </footer>

    <script nonce="{{ $cspNonce ?? '' }}">
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
        }

        // Konfirmasi aksi destruktif. Atribut onsubmit inline diblokir CSP,
        // jadi listener dipasang dari skrip ber-nonce.
        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form[data-confirm]');
            if (form && !window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
