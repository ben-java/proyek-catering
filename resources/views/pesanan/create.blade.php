@extends('layouts.app')

@section('title', 'Buat Pesanan')
@section('content')
    @if(request()->is('pesan'))
        @push('head')
            <link rel="stylesheet" href="{{ url('/css/storefront.css') }}?v={{ filemtime(public_path('css/storefront.css')) }}">
        @endpush
        @include('pesanan.storefront')
    @endif
    <div class="pagehead {{ request()->is('pesan') ? 'storefront-form-title' : '' }}">
        <h1>Buat Pesanan</h1>
        <div class="sub">Isi data pemesan dan rincian menu — periksa ringkasan sebelum simpan</div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="formPesanan" class="{{ request()->is('pesan') ? 'shop-order-form' : '' }}">
        @csrf

        <div class="card">
            <h2>1 · Data Pemesan</h2>
            <div class="grid2">
                <div class="field"><label for="nama_pemesan">Nama pemesan *</label><input id="nama_pemesan" type="text" name="nama_pemesan" value="{{ \App\Support\Scalar::str(old('nama_pemesan')) }}" required></div>
                <div class="field"><label for="no_hp">Nomor HP *</label><input id="no_hp" type="tel" name="no_hp" value="{{ \App\Support\Scalar::str(old('no_hp')) }}" required placeholder="08xxxxxxxxxx"></div>
            </div>
            <div class="field"><label for="alamat_kirim">Alamat kirim *</label><textarea id="alamat_kirim" name="alamat_kirim" rows="2" required>{{ \App\Support\Scalar::str(old('alamat_kirim')) }}</textarea></div>
            <div class="grid2">
                <div class="field"><label for="tanggal_kirim">Tanggal kirim *</label><input id="tanggal_kirim" type="date" min="{{ now()->addDay()->format('Y-m-d') }}" name="tanggal_kirim" value="{{ \App\Support\Scalar::str(old('tanggal_kirim', now()->addDay()->format('Y-m-d'))) }}" required></div>
                <div class="field"><label for="catatan">Catatan (opsional)</label><input id="catatan" type="text" name="catatan" value="{{ \App\Support\Scalar::str(old('catatan')) }}"></div>
            </div>
            <div class="hint">Pesanan dibuka minimal 1 hari sebelum tanggal pengiriman.</div>
        </div>

        <div class="card">
            <h2>2 · Rincian Menu</h2>
            <div id="rows"></div>
            <button type="button" class="btn btn-ghost btn-sm" id="addRow">+ Tambah item</button>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:18px;background:var(--surface-3);border-radius:var(--radius-sm);padding:14px 16px">
                <span style="color:var(--text-muted)">Total harga pesanan</span>
                <span style="font-size:22px;font-weight:800;color:var(--accent)" id="grandTotal">Rp0</span>
            </div>
        </div>

        <div class="card order-summary-card">
            <div class="confirm">
                <h3 style="margin:0 0 12px;font-size:14px;color:var(--accent)">✔ Ringkasan konfirmasi — periksa sebelum simpan</h3>
                <div class="rowc"><span class="k">Pemesan</span><span class="v" id="sumName">—</span></div>
                <div class="rowc"><span class="k">Alamat kirim</span><span class="v" id="sumAlamat">—</span></div>
                <div class="rowc"><span class="k">Tanggal kirim</span><span class="v" id="sumTanggal">—</span></div>
                <div class="rowc"><span class="k">Rincian</span><span class="v" id="sumItems">—</span></div>
                <div class="rowc"><span class="k">Total</span><span class="v" id="sumTotal">Rp0</span></div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px">
                <button type="reset" class="btn btn-ghost">Reset</button>
                <button type="submit" class="btn btn-primary">Simpan Pesanan</button>
            </div>
        </div>
    </form>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
const MENU = {{ Illuminate\Support\Js::from($menu->map(fn($m) => ['id'=>$m->id,'nama'=>$m->nama_menu,'harga' => floatval($m->harga_satuan)])) }};
// BUG-11: pulihkan item lama saat validasi gagal
const OLD_ITEMS = {{ Illuminate\Support\Js::from(old('items', [])) }};
const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[char]));
const fmt = n => 'Rp' + n.toLocaleString('id-ID');
let rowCount = 0;
let feedbackTimer;

function rowHtml(state) {
    const i = rowCount++;
    state = state || {};
    let opts = '<option value="">— pilih menu —</option>';
    MENU.forEach(m => {
        opts += `<option value="${m.id}" data-harga="${m.harga}" ${String(m.id) === String(state.menu_id) ? 'selected' : ''}>${escapeHtml(m.nama)} — ${fmt(m.harga)}</option>`;
    });
    const jumlah = Math.max(1, parseInt(state.jumlah, 10) || 1);
    return `
      <div class="order-item" data-row="${i}">
        <div class="oi-menu">
          <select aria-label="Pilih menu" name="items[${i}][menu_id]" class="row-menu">${opts}</select>
        </div>
        <div class="oi-ctrl">
          <div class="oi-qty"><input aria-label="Jumlah" type="number" name="items[${i}][jumlah]" class="row-jumlah" min="1" value="${jumlah}"></div>
          <div class="oi-sub row-sub">Rp0</div>
          <button type="button" class="row-del btn btn-warn btn-sm" aria-label="Hapus baris menu">✕</button>
        </div>
      </div>`;
}

function update() {
    let total = 0; const lines = [];
    document.querySelectorAll('#rows .order-item').forEach(r => {
        const sel = r.querySelector('.row-menu'); const jum = r.querySelector('.row-jumlah');
        const m = MENU.find(x => x.id == sel.value);
        if (m) {
            const j = Math.max(1, parseInt(jum.value) || 1);
            const sub = m.harga * j; total += sub;
            r.querySelector('.row-sub').textContent = fmt(sub);
            lines.push(`${j} × ${m.nama} (${fmt(sub)})`);
        } else { r.querySelector('.row-sub').textContent = 'Rp0'; }
    });
    document.getElementById('grandTotal').textContent = fmt(total);
    document.getElementById('sumTotal').textContent = fmt(total);
    document.getElementById('sumItems').textContent = lines.length ? lines.join(' + ') : '—';
    syncFields();
}

// BUG-13: sinkronkan ringkasan dari nilai field saat ini
function syncFields() {
    const nameEl = document.querySelector('[name=nama_pemesan]');
    const alamatEl = document.querySelector('[name=alamat_kirim]');
    const tanggalEl = document.querySelector('[name=tanggal_kirim]');
    document.getElementById('sumName').textContent = nameEl.value || '—';
    document.getElementById('sumAlamat').textContent = alamatEl.value || '—';
    document.getElementById('sumTanggal').textContent = tanggalEl.value || '—';
}

function bindRow(row) {
    row.querySelector('.row-menu').addEventListener('change', update);
    row.querySelector('.row-jumlah').addEventListener('input', update);
    row.querySelector('.row-del').addEventListener('click', e => { e.target.closest('.order-item').remove(); update(); });
}

function addRow(state) {
    const wrap = document.getElementById('rows');
    wrap.insertAdjacentHTML('beforeend', rowHtml(state));
    const row = wrap.querySelector('.order-item:last-child');
    bindRow(row);
    return row;
}

function initRows() {
    document.getElementById('rows').innerHTML = '';
    rowCount = 0;
    const previousItems = Object.values(OLD_ITEMS || {});
    if (previousItems.length) {
        previousItems.forEach(it => addRow(it));
    } else {
        addRow(null);
    }
}

document.getElementById('addRow').addEventListener('click', () => addRow(null));

// BUG-12: reset harus mengosongkan semuanya dan sinkronkan ulang
document.getElementById('formPesanan').addEventListener('reset', event => {
    event.preventDefault();
    document.querySelector('[name=nama_pemesan]').value = '';
    document.querySelector('[name=no_hp]').value = '';
    document.querySelector('[name=alamat_kirim]').value = '';
    document.querySelector('[name=tanggal_kirim]').value = '';
    document.querySelector('[name=catatan]').value = '';
    document.getElementById('rows').replaceChildren();
    rowCount = 0;
    addRow(null);
    update();
});

['nama_pemesan','no_hp','alamat_kirim','tanggal_kirim','catatan'].forEach(n => {
    const el = document.querySelector(`[name=${n}]`);
    if (el) el.addEventListener('input', syncFields);
});

document.querySelectorAll('.add-menu').forEach(button => {
    button.addEventListener('click', () => {
        const menuId = button.dataset.menuId;
        const item = MENU.find(m => String(m.id) === menuId);
        if (!item) return;
        const rows = [...document.querySelectorAll('#rows .order-item')];
        let row = rows.find(r => r.querySelector('.row-menu').value === menuId);
        if (row) {
            const quantity = row.querySelector('.row-jumlah');
            quantity.value = (parseInt(quantity.value, 10) || 1) + 1;
        } else {
            row = rows.find(r => !r.querySelector('.row-menu').value);
            if (row) row.querySelector('.row-menu').value = menuId;
            else row = addRow({menu_id: menuId, jumlah: 1});
        }
        update();
        const feedback = document.getElementById('menu-feedback');
        if (feedback) {
            feedback.textContent = `${item.nama} ditambahkan. Atur porsi di formulir pesanan.`;
            clearTimeout(feedbackTimer);
            feedbackTimer = setTimeout(() => { feedback.textContent = ''; }, 4000);
        }
        button.classList.remove('menu-added');
        requestAnimationFrame(() => button.classList.add('menu-added'));
    });
});

initRows();
update();
</script>
@endpush
