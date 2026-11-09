@extends('layouts.app')

@section('title', 'Buat Pesanan')
@section('content')
    <div class="pagehead">
        <h1>Buat Pesanan</h1>
        <div class="sub">Isi data pemesan dan rincian menu — periksa ringkasan sebelum simpan</div>
    </div>

    <form method="POST" action="{{ url('/pesanan') }}" id="formPesanan">
        @csrf

        <div class="card">
            <h2>1 · Data Pemesan</h2>
            <div class="grid2">
                <div class="field"><label>Nama pemesan *</label><input type="text" name="nama_pemesan" value="{{ old('nama_pemesan') }}" required></div>
                <div class="field"><label>Nomor HP *</label><input type="tel" name="no_hp" value="{{ old('no_hp') }}" required placeholder="08xxxxxxxxxx"></div>
            </div>
            <div class="field"><label>Alamat kirim *</label><textarea name="alamat_kirim" rows="2" required>{{ old('alamat_kirim') }}</textarea></div>
            <div class="grid2">
                <div class="field"><label>Tanggal kirim *</label><input type="date" name="tanggal_kirim" value="{{ old('tanggal_kirim', now()->addDay()->format('Y-m-d')) }}" required></div>
                <div class="field"><label>Catatan (opsional)</label><input type="text" name="catatan" value="{{ old('catatan') }}"></div>
            </div>
            <div class="hint">Pesanan dibuka minimal 1 hari sebelum tanggal pengiriman.</div>
        </div>

        <div class="card">
            <h2>2 · Rincian Menu</h2>
            <div id="rows"></div>
            <button type="button" class="btn btn-ghost btn-sm" id="addRow">+ Tambah item</button>

            <div class="tot" style="display:flex;justify-content:space-between;align-items:center;margin-top:18px;background:var(--surface-3);border-radius:var(--radius-sm);padding:14px 16px">
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
const fmt = n => 'Rp' + n.toLocaleString('id-ID');
let rowCount = 0;

function rowHtml() {
    const i = rowCount++;
    let opts = '<option value="">— pilih menu —</option>';
    MENU.forEach(m => { opts += `<option value="${m.id}" data-harga="${m.harga}">${m.nama} — ${fmt(m.harga)}</option>`; });
    return `
      <div class="row" data-row="${i}" style="display:grid;grid-template-columns:1fr 84px 108px 36px;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid var(--line)">
        <div><select name="items[${i}][menu_id]" class="row-menu" style="width:100%;padding:9px;border:1.5px solid var(--line-strong);border-radius:9px">${opts}</select></div>
        <div><input type="number" name="items[${i}][jumlah]" class="row-jumlah" min="1" value="1" style="width:100%;padding:9px;border:1.5px solid var(--line-strong);border-radius:9px;text-align:center"></div>
        <div class="row-sub" style="text-align:right;font-weight:700">Rp0</div>
        <div><button type="button" class="row-del btn btn-warn btn-sm" style="width:100%">✕</button></div>
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
}
document.getElementById('addRow').addEventListener('click', () => {
    const wrap = document.getElementById('rows'); wrap.insertAdjacentHTML('beforeend', rowHtml());
    wrap.querySelector('.row:last-child .row-menu').addEventListener('change', update);
    wrap.querySelector('.row:last-child .row-jumlah').addEventListener('input', update);
    wrap.querySelector('.row:last-child .row-del').addEventListener('click', e => { e.target.closest('.row').remove(); update(); });
    update();
});
document.getElementById('addRow').click();

const nameEl = document.querySelector('[name=nama_pemesan]');
const alamatEl = document.querySelector('[name=alamat_kirim]');
const tanggalEl = document.querySelector('[name=tanggal_kirim]');
[nameEl, alamatEl, tanggalEl].forEach(el => el.addEventListener('input', () => {
    document.getElementById('sumName').textContent = nameEl.value || '—';
    document.getElementById('sumAlamat').textContent = alamatEl.value || '—';
    document.getElementById('sumTanggal').textContent = tanggalEl.value || '—';
}));
</script>
@endpush
