@extends('layouts.app')

@section('title', 'Terima Kasih')
@section('content')
    <div class="card" style="max-width:560px;margin:40px auto;text-align:center">
        <div style="width:56px;height:56px;border-radius:50%;background:var(--ok-soft);color:var(--accent);display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        </div>
        <h1 style="font-size:20px;color:var(--accent-strong);margin:0 0 8px">Pesanan diterima</h1>
        <p style="color:var(--text-secondary);font-size:14px;margin:0 0 20px">
            Terima kasih! Pesanan Anda sudah tercatat dan berstatus <strong>menunggu konfirmasi</strong>.
            Pemilik catering akan menghubungi Anda untuk konfirmasi.
        </p>
        <a href="{{ url('/pesan') }}" class="btn btn-primary">Buat pesanan lagi</a>
    </div>
@endsection
