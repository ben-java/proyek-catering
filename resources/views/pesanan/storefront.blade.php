<div class="storefront">
    <nav class="shop-links" aria-label="Jelajahi catering">
        <a href="#pilihan-menu">Pilihan menu</a><a href="#cara-pesan">Cara pesan</a>
    </nav>
    <section class="food-hero" aria-labelledby="hero-title">
        <div class="food-hero-copy">
            <span class="eyebrow">Dari pilihan menu, jadi momen bersama</span>
            <h1 id="hero-title">Meja penuh rasa.<br><em>Momen penuh cerita.</em></h1>
            <p>Pilih menu catering untuk rapat, kumpul keluarga, atau acara Anda berikutnya. Atur porsi dan tanggal kirim dalam satu pesanan.</p>
            <div class="hero-actions"><a href="#pilihan-menu" class="btn btn-terra">Lihat menu & pesan ↗</a><a href="#formPesanan" class="shop-text-link">Langsung isi pesanan →</a></div>
            <div class="hero-note"><span aria-hidden="true">✳</span> Rencanakan hari ini. Tanggal kirim mulai besok.</div>
        </div>
        <figure class="food-hero-photo"><img src="{{ url('/images/rice-table.jpg') }}" alt="Ilustrasi sajian nasi dengan aneka pelengkap" width="1100" height="850" fetchpriority="high"><figcaption>Selera berbeda, satu meja bersama.<small>Foto ilustrasi, bukan foto menu aktual.</small></figcaption><span class="food-stamp" aria-hidden="true">PILIH MENU<br>ATUR PORSI<br>NIKMATI MOMEN</span></figure>
    </section>
    <section class="shop-section" id="pilihan-menu" aria-labelledby="menu-heading">
        <div class="shop-section-head"><div><span class="eyebrow">Temukan selera Anda</span><h2 id="menu-heading">Pilihan dari dapur kami.</h2></div><p>Harga per porsi sesuai katalog.<br>Tambahkan pilihan langsung ke formulir.</p></div>
        <div class="food-menu-grid">
            @forelse($menu as $pilihan)
                @php
                    $imageKey = config('catering.menu_images.' . mb_strtolower(trim($pilihan->nama_menu)));
                    $image = $imageKey ? config('catering.image_credits.' . $imageKey) : null;
                @endphp
                <article class="food-menu-card">
                    <div class="food-menu-image">
                        @if($image)
                            <img src="{{ url('/images/' . $image['file']) }}" alt="Referensi {{ $pilihan->nama_menu }}: {{ $image['title'] }}" loading="lazy" width="700" height="500">
                            <span>Referensi sajian</span>
                        @else
                            <div class="menu-photo-placeholder">@include('layouts.brand-mark')<span>Foto menu segera hadir</span></div>
                        @endif
                    </div>
                    <div class="food-menu-content"><span class="eyebrow">Menu tersedia</span><h3>{{ $pilihan->nama_menu }}</h3><div class="food-menu-bottom"><div><strong>Rp{{ number_format($pilihan->harga_satuan, 0, ',', '.') }}</strong><small>/ porsi</small></div><button type="button" class="btn btn-ghost btn-sm add-menu" data-menu-id="{{ $pilihan->id }}" aria-label="Tambah {{ $pilihan->nama_menu }} ke pesanan">+ Tambah</button></div></div>
                </article>
            @empty
                <div class="card"><h3>Menu sedang disiapkan</h3><p>Belum ada menu tersedia. Silakan kembali lagi untuk melihat pilihan terbaru.</p></div>
            @endforelse
        </div>
        <p class="shop-disclaimer">Foto referensi sesuai jenis hidangan, bukan produk asli CateringKu. Untuk paket kotak, foto dapat menampilkan lauk utamanya; penyajian aktual dikonfirmasi pemilik.</p>
        <details class="photo-credits"><summary>Sumber & lisensi foto</summary><ul>@foreach(config('catering.image_credits', []) as $credit)<li><a href="{{ $credit['source'] }}" target="_blank" rel="noopener noreferrer">{{ $credit['title'] }}</a> — {{ $credit['author'] }} · <a href="{{ $credit['license_url'] }}" target="_blank" rel="noopener noreferrer">{{ $credit['license'] }}</a>. Ditampilkan dengan crop untuk kartu menu.</li>@endforeach</ul></details>
        <div id="menu-feedback" class="menu-feedback" role="status" aria-live="polite"></div>
    </section>
    <section class="shop-section food-story" id="cara-pesan" aria-labelledby="story-heading">
        <div><span class="eyebrow">Lebih mudah direncanakan</span><h2 id="story-heading">Anda siapkan acaranya.<br>Kita mulai dari menunya.</h2><p>CateringKu mempertemukan pilihan menu dan kebutuhan acara Anda dalam formulir yang sederhana. Tidak perlu masuk akun untuk membuat pesanan.</p><a href="#formPesanan" class="shop-text-link">Mulai rencanakan pesanan →</a></div>
        <ol class="order-steps"><li><b>01</b><div><h3>Pilih & sesuaikan</h3><p>Tambahkan menu, atur porsi, dan lihat estimasi total secara langsung.</p></div></li><li><b>02</b><div><h3>Lengkapi detail acara</h3><p>Isi kontak, alamat, serta tanggal kirim minimal besok.</p></div></li><li><b>03</b><div><h3>Tunggu konfirmasi</h3><p>Pesanan tercatat sebagai menunggu konfirmasi. Pemilik akan menghubungi Anda.</p></div></li></ol>
    </section>
    @if(count(config('catering.testimonials', [])))
        <section class="shop-section" aria-labelledby="reviews-heading"><span class="eyebrow">Cerita dari pelanggan</span><h2 id="reviews-heading">Setelah meja kembali kosong.</h2><div class="occasion-grid">@foreach(config('catering.testimonials', []) as $review)<blockquote><p>“{{ $review['quote'] }}”</p><cite>{{ $review['name'] }}</cite></blockquote>@endforeach</div></section>
    @endif
    <div class="shop-section-head order-heading"><div><span class="eyebrow">Selangkah menuju acara Anda</span><h2>Susun pesanan Anda.</h2></div><p>Periksa kembali rincian sebelum menyimpan.<br>Pesanan tetap memerlukan konfirmasi pemilik.</p></div>
</div>
