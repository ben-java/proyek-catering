@extends('layouts.app')

@section('title', 'Buat Pesanan')
@section('content')
    <div class="pagehead">
        <h1>Buat Pesanan</h1>
        <div class="sub">Isi data pemesan dan rincian menu — periksa ringkasan sebelum simpan</div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="formPesanan">
        @csrf

        <div class="card">
            <h2>1 · Data Pemesan</h2>
            <div class="grid2">
                <div class="field"><label for="nama_pemesan">Nama pemesan *</label><input id="nama_pemesan" type="text" name="nama_pemesan" value="{{ old('nama_pemesan') }}" required></div>
                <div class="field"><label for="no_hp">Nomor HP *</label><input id="no_hp" type="tel" name="no_hp" value="{{ old('no_hp') }}" required placeholder="08xxxxxxxxxx"></div>
            </div>
            <div class="field"><label for="alamat_kirim">Alamat kirim *</label><textarea id="alamat_kirim" name="alamat_kirim" rows="2" required>{{ old('alamat_kirim') }}</textarea></div>
            <div class="grid2">
                <div class="field"><label for="tanggal_kirim">Tanggal kirim *</label><input id="tanggal_kirim" type="date" name="tanggal_kirim" value="{{ old('tanggal_kirim', now()->addDay()->format('Y-m-d')) }}" required></div>
                <div class="field"><label for="catatan">Catatan (opsional)</label><input id="catatan" type="text" name="catatan" value="{{ old('catatan') }}"></div>
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

        <div class="card">
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
<script>
const MENU = {!! $menu->map(fn($m) => ['id'=>$m->id,'nama'=>$m->nama_menu,'harga' => floatval($m->harga_satuan)])->toJson() !!};
// BUG-11: pulihkan item lama saat validasi gagal
const OLD_ITEMS = {!! json_encode(old('items', [])) !!};
const fmt = n => 'Rp' + n.toLocaleString('id-ID');
let rowCount = 0;

function rowHtml(state) {
    const i = rowCount++;
    state = state || {};
    let opts = '<option value="">— pilih menu —</option>';
    MENU.forEach(m => {
        opts += `<option value="${m.id}" data-harga="${m.harga}" ${String(m.id) === String(state.menu_id) ? 'selected' : ''}>${m.nama} — ${fmt(m.harga)}</option>`;
    });
    const jumlah = state.jumlah || 1;
    return `
      <div class="row" data-row="${i}" style="display:grid;grid-template-columns:1fr 84px 108px 36px;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid var(--line)">
        <div><select aria-label="Pilih menu" name="items[${i}][menu_id]" class="row-menu" style="width:100%;padding:9px;border:1.5px solid var(--line-strong);border-radius:9px">${opts}</select></div>
        <div><input aria-label="Jumlah" type="number" name="items[${i}][jumlah]" class="row-jumlah" min="1" value="${jumlah}" style="width:100%;padding:9px;border:1.5px solid var(--line-strong);border-radius:9px;text-align:center"></div>
        <div class="row-sub" style="text-align:right;font-weight:700">Rp0</div>
        <div><button type="button" class="row-del btn btn-warn btn-sm" aria-label="Hapus baris menu" style="width:100%">✕</button></div>
      </div>`;
}

function update() {
    let total = 0; const lines = [];
    document.querySelectorAll('#rows .row').forEach(r => {
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
    row.querySelector('.row-del').addEventListener('click', e => { e.target.closest('.row').remove(); update(); });
}

function addRow(state) {
    const wrap = document.getElementById('rows');
    wrap.insertAdjacentHTML('beforeend', rowHtml(state));
    bindRow(wrap.querySelector('.row:last-child'));
}

function initRows() {
    document.getElementById('rows').innerHTML = '';
    rowCount = 0;
    if (OLD_ITEMS && OLD_ITEMS.length) {
        OLD_ITEMS.forEach(it => addRow(it));
    } else {
        addRow(null);
    }
}

document.getElementById('addRow').addEventListener('click', () => addRow(null));

// BUG-12: reset harus mengosongkan semuanya dan sinkronkan ulang
document.getElementById('formPesanan').addEventListener('reset', () => {
    document.querySelector('[name=nama_pemesan]').value = '';
    document.querySelector('[name=no_hp]').value = '';
    document.querySelector('[name=alamat_kirim]').value = '';
    document.querySelector('[name=tanggal_kirim]').value = '';
    document.querySelector('[name=catatan]').value = '';
    initRows();
    update();
});

['nama_pemesan','no_hp','alamat_kirim','tanggal_kirim','catatan'].forEach(n => {
    const el = document.querySelector(`[name=${n}]`);
    if (el) el.addEventListener('input', syncFields);
});

initRows();
update();
</script>
@endpush
