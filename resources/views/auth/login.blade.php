<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ url('/css/app.css') }}">
    <title>Masuk — CateringKu</title>
    <style>
        body{display:flex;align-items:center;justify-content:center;min-height:100vh}
        .login-card{width:100%;max-width:400px;background:var(--surface);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);padding:32px}
        .login-logo{display:flex;align-items:center;justify-content:center;gap:10px;font-weight:800;font-size:20px;color:var(--accent-strong);margin-bottom:6px}
        .login-sub{text-align:center;color:var(--text-muted);font-size:13px;margin-bottom:22px}
        .login-card .btn{width:100%}
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round"><path d="M12 3c4 3 6 7 5 12-5 0-9-3-10-9 1-2 3-3 5-3zm-2 18c1-3 3-5 6-6"/></svg>
            CateringKu
        </div>
        <div class="login-sub">Masuk sebagai administrator</div>

        @if($errors->any())
            <div class="alert alert-err">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ url('/login') }}">
            @csrf
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-secondary);margin-bottom:16px">
                <input type="checkbox" name="remember" style="width:auto"> Ingat saya
            </label>
            <button type="submit" class="btn btn-primary">Masuk</button>
        </form>
        <div class="hint mt" style="text-align:center">demo: admin@catering.test / password</div>
    </div>
</body>
</html>
