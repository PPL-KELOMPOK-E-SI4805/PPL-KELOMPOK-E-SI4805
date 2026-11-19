{{-- Form Pengukuran Pertumbuhan (create & edit). Asumsi layout tim: layouts.app dengan @yield('content'). --}}
@extends('layouts.app')

@section('content')
@php
    $edit = (bool) $pengukuran;
    $action = $edit ? route('pengukuran.update', $pengukuran) : route('pengukuran.store');
@endphp
<div class="max-w-5xl mx-auto p-4 space-y-4">
    <nav class="text-sm text-gray-500">
        Dashboard &gt; Pengukuran Pertumbuhan &gt; {{ $edit ? 'Ubah Pengukuran' : 'Input Pengukuran Baru' }}
    </nav>

    <div class="flex justify-between items-start">
        <h1 class="text-2xl font-bold">Pencatatan Pengukuran Pertumbuhan Balita</h1>
        <div class="text-sm bg-blue-50 border border-blue-200 rounded px-3 py-2">
            <div class="font-semibold">Sesi Buka Layanan</div>
            <div>{{ now('Asia/Jakarta')->translatedFormat('l, d F Y') }}</div>
        </div>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-300 text-red-700 rounded p-3 text-sm">
            <ul class="list-disc ml-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form id="form-pengukuran" method="POST" action="{{ $action }}" class="space-y-4">
        @csrf
        @if ($edit) @method('PUT') @endif

        {{-- Identitas balita --}}
        <section class="bg-white border rounded p-4">
            <h2 class="font-semibold mb-2">Identitas Balita</h2>
            @if ($edit)
                <input type="hidden" name="id_balita" value="{{ $balita->id_balita }}">
            @else
                <label class="text-sm">Cari Balita</label>
                <select name="id_balita" id="id_balita" class="w-full border rounded p-2" required>
                    <option value="">-- pilih balita --</option>
                    @foreach ($daftarBalita as $b)
                        <option value="{{ $b->id_balita }}"
                                data-lahir="{{ \Carbon\Carbon::parse($b->tanggal_lahir)->toDateString() }}"
                                data-jk="{{ $b->jenis_kelamin }}"
                                @selected(old('id_balita', $balita->id_balita ?? null) == $b->id_balita)>
                            {{ $b->nama }}
                        </option>
                    @endforeach
                </select>
            @endif
            @if ($balita)
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm mt-3">
                    <div><dt class="text-gray-500">Nama</dt><dd>{{ $balita->nama }}</dd></div>
                    <div><dt class="text-gray-500">Jenis Kelamin</dt><dd>{{ $balita->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd></div>
                    <div><dt class="text-gray-500">NIK</dt><dd>{{ substr($balita->nik, 0, 6) }}******{{ substr($balita->nik, -4) }}</dd></div>
                    <div><dt class="text-gray-500">Tanggal Lahir</dt><dd>{{ \Carbon\Carbon::parse($balita->tanggal_lahir)->translatedFormat('d F Y') }}</dd></div>
                </dl>
            @endif
        </section>

        {{-- Input pengukuran --}}
        <section class="bg-white border rounded p-4 grid md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm">Tanggal Pengukuran *</label>
                <input type="date" name="tanggal_ukur" id="tanggal_ukur" max="{{ now()->toDateString() }}"
                       value="{{ old('tanggal_ukur', $pengukuran?->tanggal_ukur?->toDateString() ?? now()->toDateString()) }}"
                       class="w-full border rounded p-2" required>
            </div>
            <div>
                <label class="text-sm">Usia Saat Pengukuran</label>
                <input type="text" id="usia_tampil" class="w-full border rounded p-2 bg-gray-100" readonly
                       value="{{ $pengukuran ? $pengukuran->usia_bulan.' Bulan' : '' }}">
            </div>
            <div>
                <label class="text-sm">Berat Badan (kg) *</label>
                <input type="number" step="0.01" min="1" max="30" name="berat_badan" id="berat_badan"
                       value="{{ old('berat_badan', $pengukuran?->berat_badan) }}" class="w-full border rounded p-2" required>
                @if ($sebelumnya)
                    <p class="text-xs text-gray-500 mt-1">
                        Bulan lalu: {{ number_format($sebelumnya->berat_badan, 1) }} kg
                    </p>
                @endif
            </div>
            <div>
                <label class="text-sm">Panjang/Tinggi Badan (cm) *</label>
                <input type="number" step="0.1" min="30" max="130" name="tinggi_badan" id="tinggi_badan"
                       value="{{ old('tinggi_badan', $pengukuran?->tinggi_badan) }}" class="w-full border rounded p-2" required>
            </div>
            <div>
                <label class="text-sm">Cara Ukur *</label>
                <select name="cara_ukur" id="cara_ukur" class="w-full border rounded p-2" required>
                    <option value="terlentang" @selected(old('cara_ukur', $pengukuran?->cara_ukur) === 'terlentang')>Terlentang (PB)</option>
                    <option value="berdiri" @selected(old('cara_ukur', $pengukuran?->cara_ukur) === 'berdiri')>Berdiri (TB)</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Jika tidak sesuai usia, sistem menerapkan koreksi 0,7 cm.</p>
            </div>
            <div>
                <label class="text-sm">Lingkar Kepala (cm)</label>
                <input type="number" step="0.1" min="20" max="60" name="lingkar_kepala"
                       value="{{ old('lingkar_kepala', $pengukuran?->lingkar_kepala) }}" class="w-full border rounded p-2">
            </div>
            <div>
                <label class="text-sm">LiLA (cm)</label>
                <input type="number" step="0.1" min="5" max="30" name="lingkar_lengan" id="lila"
                       value="{{ old('lingkar_lengan', $pengukuran?->lingkar_lengan) }}" class="w-full border rounded p-2">
                <p class="text-xs mt-1">Kategori Pita: <span id="pita" class="font-semibold">-</span></p>
            </div>
            <div class="md:col-span-2">
                <label class="text-sm">Catatan Kader &amp; Kondisi Balita</label>
                <textarea name="catatan" rows="3" class="w-full border rounded p-2">{{ old('catatan', $pengukuran?->catatan) }}</textarea>
            </div>
        </section>

        <aside class="bg-yellow-50 border border-yellow-200 rounded p-3 text-sm">
            <strong>Protokol Akurasi Kader:</strong> kalibrasi timbangan ke nol sebelum menimbang;
            balita di bawah 24 bulan diukur terlentang, 24 bulan ke atas diukur berdiri.
        </aside>

        {{-- Hasil hitung ulang (AJAX) --}}
        <section id="panel-hasil" class="hidden bg-white border rounded p-4">
            <h2 class="font-semibold mb-2">Hasil Penilaian Status Gizi (Permenkes No. 2 Tahun 2020)</h2>
            <div id="hasil-isi" class="grid md:grid-cols-3 gap-3 text-sm"></div>
            <p id="hasil-error" class="text-red-600 text-sm"></p>
        </section>

        <div class="flex gap-2">
            <button type="submit" class="bg-blue-600 text-white rounded px-4 py-2">Simpan Pengukuran</button>
            <button type="button" id="btn-hitung" class="border rounded px-4 py-2">Hitung Ulang</button>
            <a id="btn-batal" href="{{ route('pengukuran.index') }}" class="border rounded px-4 py-2">Batal &amp; Kembali</a>
        </div>
    </form>
</div>

<script>
(function () {
    const form = document.getElementById('form-pengukuran');
    let berubah = false;
    form.addEventListener('input', () => berubah = true);
    form.addEventListener('submit', () => berubah = false);

    document.getElementById('btn-batal').addEventListener('click', function (e) {
        if (berubah && !confirm('Perubahan belum disimpan. Yakin batal?')) e.preventDefault();
    });

    // Cara ukur default mengikuti usia.
    const lahirData = @json($balita ? \Carbon\Carbon::parse($balita->tanggal_lahir)->toDateString() : null);
    function lahir() {
        const sel = document.getElementById('id_balita');
        if (sel && sel.selectedOptions[0]) return sel.selectedOptions[0].dataset.lahir || null;
        return lahirData;
    }
    function usiaBulan() {
        const l = lahir(), t = document.getElementById('tanggal_ukur').value;
        if (!l || !t) return null;
        const a = new Date(l), b = new Date(t);
        let m = (b.getFullYear() - a.getFullYear()) * 12 + (b.getMonth() - a.getMonth());
        if (b.getDate() < a.getDate()) m--;
        return m < 0 ? null : m;
    }
    function updateUsia() {
        const m = usiaBulan();
        document.getElementById('usia_tampil').value = m === null ? '' : m + ' Bulan';
        @if (! $edit)
        if (m !== null) document.getElementById('cara_ukur').value = m < 24 ? 'terlentang' : 'berdiri';
        @endif
    }
    ['tanggal_ukur', 'id_balita'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', updateUsia);
    });
    updateUsia();

    document.getElementById('lila').addEventListener('input', function () {
        const v = parseFloat(this.value);
        document.getElementById('pita').textContent =
            isNaN(v) ? '-' : (v > 12.5 ? 'Hijau' : (v >= 11.5 ? 'Kuning' : 'Merah'));
    });

    document.getElementById('btn-hitung').addEventListener('click', async function () {
        const fd = new FormData(form);
        const panel = document.getElementById('panel-hasil');
        const isi = document.getElementById('hasil-isi');
        const err = document.getElementById('hasil-error');
        panel.classList.remove('hidden'); isi.innerHTML = ''; err.textContent = '';
        const res = await fetch(@json(route('pengukuran.hitung')), {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': fd.get('_token'), 'Accept': 'application/json'},
            body: fd,
        });
        const j = await res.json();
        if (!res.ok) { err.textContent = j.message || Object.values(j.errors || {}).flat().join(' '); return; }
        const kartu = (judul, z, s) => `<div class="border rounded p-3"><div class="text-gray-500">${judul}</div>
            <div class="text-lg font-semibold">${z === null ? '-' : z + ' SD'}</div><div>${s ?? 'Data standar tidak tersedia'}</div></div>`;
        isi.innerHTML =
            kartu('BB/U', j.zscore_bb_u, j.status_bb_u) +
            kartu(j.indikator_tinggi === 'pb_u' ? 'PB/U' : 'TB/U', j.zscore_tb_u, j.status_tb_u) +
            kartu(j.indikator_bb_tinggi === 'bb_pb' ? 'BB/PB' : 'BB/TB', j.zscore_bb_tb, j.status_bb_tb);
    });
})();
</script>
@endsection
