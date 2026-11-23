<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="only light">
    <link rel="stylesheet" href="{{ url('/css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <title>Masuk — CateringKu</title>
    <style>
        body{display:flex;align-items:center;justify-content:center;min-height:100vh}
        .login-card{width:100%;max-width:400px;background:var(--surface);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);padding:32px}
        .login-logo{display:flex;align-items:center;justify-content:center;gap:10px;font-weight:800;font-size:20px;color:var(--accent-strong);margin-bottom:6px}
        .login-logo .brand-mark{width:42px;height:42px;padding:4px;background:var(--accent-soft);border-radius:13px}
        .login-sub{text-align:center;color:var(--text-muted);font-size:13px;margin-bottom:22px}
        .login-card .btn{width:100%}
    </style>
</head>
<body class="login-page">
    <main class="login-card">
        <div class="login-logo">
            @include('layouts.brand-mark')
            CateringKu
        </div>
        <div class="login-intro">
            <h1>Selamat datang kembali</h1>
            <p>Masuk untuk mengelola pesanan dan menu catering.</p>
        </div>

        @if($errors->any())
            <div class="alert alert-err">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ url('/login') }}">
            @csrf
            <div class="field">
                <label for="email">Email / username</label>
                <input id="email" type="text" name="email" value="{{ \App\Support\Scalar::str(old('email')) }}" placeholder="admin" autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="current-password" required>
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-secondary);margin-bottom:16px">
                <input type="checkbox" name="remember" style="width:auto"> Ingat saya
            </label>
            <button type="submit" class="btn btn-primary">Masuk</button>
        </form>
        <div class="hint mt" style="text-align:center">demo: admin / password</div>
        <div style="text-align:center;margin-top:12px;font-size:13px">
            Pelanggan? <a href="{{ url('/pesan') }}">Pesan di sini</a> tanpa login.
        </div>
    </main>
</body>
</html>
