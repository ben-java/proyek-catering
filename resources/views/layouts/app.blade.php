<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1f4d3d">
    <meta name="description" content="Aplikasi pencatatan pesanan usaha catering">
    <link rel="manifest" href="{{ url('/manifest.json') }}">
    <link rel="stylesheet" href="{{ url('/css/app.css') }}">
    <title>@yield('title', 'CateringKu') — CateringKu</title>
    @stack('head')
</head>
<body>
    <div class="appbar">
        <div style="display:flex;align-items:center;gap:8px">
            <a class="brand" href="{{ url('/') }}">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"><path d="M12 3c4 3 6 7 5 12-5 0-9-3-10-9 1-2 3-3 5-3zm-2 18c1-3 3-5 6-6"/></svg>
                CateringKu
            </a>
            @auth
            <nav class="nav">
                <a href="{{ url('/dashboard') }}" class="{{ request()->is('dashboard*') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ url('/pesanan') }}" class="{{ request()->is('pesanan*') ? 'active' : '' }}">Pesanan</a>
                <a href="{{ url('/menu') }}" class="{{ request()->is('menu*') ? 'active' : '' }}">Menu</a>
                <a href="{{ url('/laporan/porsi') }}" class="{{ request()->is('laporan*') ? 'active' : '' }}">Laporan</a>
            </nav>
            @endauth
        </div>
        <div style="display:flex;align-items:center;gap:10px">
            @auth
            <span class="admin">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/></svg>
                {{ auth()->user()->name }}
            </span>
            <form method="POST" action="{{ url('/logout') }}" style="margin:0">
                @csrf
                <button type="submit" class="btn btn-sm" style="background:rgba(255,255,255,.14);color:#fff">Keluar</button>
            </form>
            @else
            <a href="{{ url('/login') }}" class="btn btn-sm" style="background:rgba(255,255,255,.14);color:#fff">Masuk</a>
            @endauth
        </div>
    </div>

    <main class="wrap">
        @if(session('ok'))
            <div class="alert alert-ok">{{ session('ok') }}</div>
        @endif
        @if(session('err'))
            <div class="alert alert-err">{{ session('err') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-err">
                <strong>Periksa kembali isian:</strong>
                <ul class="list">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
        }
    </script>
    @stack('scripts')
</body>
</html>
