{{-- Panel Hasil Penilaian Status Gizi (sesuai mockup Form Pengukuran Pertumbuhan). --}}
@extends('layouts.app')

@section('content')
@php
    $warna = fn ($s) => match (true) {
        in_array($s, ['Berat badan normal', 'Normal', 'Gizi baik']) => 'bg-green-100 text-green-800',
        in_array($s, ['Berat badan sangat kurang', 'Sangat pendek', 'Gizi buruk', 'Obesitas']) => 'bg-red-100 text-red-800',
        default => 'bg-yellow-100 text-yellow-800',
    };
    $labelTinggi = $pengukuran->usia_bulan < 24 ? 'PB/U' : 'TB/U';
    $labelBBTinggi = $pengukuran->usia_bulan < 24 ? 'BB/PB' : 'BB/TB';
    $kartu = [
        ['BB/U (Berat Badan menurut Umur)', $status?->zscore_bb_u, $status?->status_bb_u, number_format($pengukuran->berat_badan, 2).' kg'],
        [$labelTinggi.' (Panjang/Tinggi Badan menurut Umur)', $status?->zscore_tb_u, $status?->status_tb_u, number_format($pengukuran->tinggi_badan, 1).' cm'],
        [$labelBBTinggi.' (Berat Badan menurut Panjang/Tinggi)', $status?->zscore_bb_tb, $status?->status_bb_tb, number_format($pengukuran->berat_badan, 2).' kg'],
    ];
@endphp
<div class="max-w-5xl mx-auto p-4 space-y-4">
    <nav class="text-sm text-gray-500">Dashboard &gt; Pengukuran Pertumbuhan &gt; Hasil</nav>

    @if (session('success'))
        <div class="bg-green-50 border border-green-300 text-green-800 rounded p-3">{{ session('success') }}</div>
    @endif

    <h1 class="text-2xl font-bold">{{ $balita->nama }}
        <span class="text-base font-normal text-gray-500">
            · {{ $pengukuran->usia_bulan }} bulan · {{ $pengukuran->tanggal_ukur->translatedFormat('d F Y') }}
        </span>
    </h1>

    <h2 class="font-semibold">Hasil Penilaian Status Gizi (Standar Permenkes No. 2 Tahun 2020)</h2>
    <div class="grid md:grid-cols-3 gap-3">
        @foreach ($kartu as [$judul, $z, $kategori, $nilai])
            <div class="bg-white border rounded p-4">
                <div class="text-sm text-gray-500">{{ $judul }}</div>
                <div class="text-lg font-semibold">{{ $nilai }}</div>
                <div class="text-sm">Z-score: {{ $z === null ? '-' : number_format($z, 2).' SD' }}</div>
                <span class="inline-block mt-1 text-xs rounded px-2 py-1 {{ $kategori ? $warna($kategori) : 'bg-gray-100 text-gray-600' }}">
                    {{ $kategori ?? 'Data standar tidak tersedia' }}
                </span>
            </div>
        @endforeach
    </div>

    <div class="grid md:grid-cols-2 gap-3">
        <div class="bg-white border rounded p-4">
            <div class="text-sm text-gray-500">LiLA</div>
            @if ($pengukuran->lingkar_lengan)
                @php $lila = \App\Services\ZScoreCalculator::kategoriPitaLila($pengukuran->lingkar_lengan); @endphp
                <div class="font-semibold">{{ number_format($pengukuran->lingkar_lengan, 1) }} cm · Pita {{ $lila }}</div>
            @else <div class="text-gray-500">Tidak diukur</div> @endif
        </div>
        <div class="bg-white border rounded p-4">
            <div class="text-sm text-gray-500">Lingkar Kepala</div>
            <div class="font-semibold">
                {{ $pengukuran->lingkar_kepala ? number_format($pengukuran->lingkar_kepala, 1).' cm' : 'Tidak diukur' }}
            </div>
        </div>
    </div>

    <div class="bg-white border rounded p-4">
        <div class="font-semibold mb-1">Perbandingan Penimbangan Lalu</div>
        @if ($sebelumnya)
            <p class="text-sm">
                {{ $sebelumnya->tanggal_ukur->translatedFormat('d F Y') }} ({{ $sebelumnya->usia_bulan }} bulan):
                BB {{ number_format($sebelumnya->berat_badan, 2) }} kg, PB/TB {{ number_format($sebelumnya->tinggi_badan, 1) }} cm.
            </p>
            <p class="text-sm mt-1">
                Kenaikan Berat Badan Minimal (KBM):
                <strong class="{{ $kbm['lulus'] ? 'text-green-700' : 'text-red-700' }}">
                    {{ $kbm['lulus'] ? 'Lulus Target' : 'Tidak Lulus Target' }}
                    ({{ $kbm['selisih_gram'] >= 0 ? '+' : '' }}{{ $kbm['selisih_gram'] }} gr, target {{ $kbm['target_gram'] }} gr)
                </strong>
            </p>
        @else
            <p class="text-sm text-gray-500">Belum ada penimbangan sebelumnya.</p>
        @endif
    </div>

    @if ($pengukuran->catatan)
        <div class="bg-white border rounded p-4 text-sm"><strong>Catatan Kader:</strong> {{ $pengukuran->catatan }}</div>
    @endif

    <aside class="bg-red-50 border border-red-200 rounded p-3 text-sm">
        <strong>Catatan Penting Rujukan:</strong> hasil ini hanya alat bantu pemantauan dan bukan pengganti diagnosis
        tenaga kesehatan. Jika balita berada pada garis kuning/merah (BGM), segera rujuk ke Puskesmas Pembina.
    </aside>

    <div class="flex gap-2">
        <a href="{{ route('pengukuran.edit', $pengukuran) }}" class="border rounded px-4 py-2">Ubah</a>
        <form method="POST" action="{{ route('pengukuran.destroy', $pengukuran) }}"
              onsubmit="return confirm('Hapus pengukuran ini? Status gizi terkait ikut terhapus.')">
            @csrf @method('DELETE')
            <button class="border border-red-400 text-red-700 rounded px-4 py-2">Hapus</button>
        </form>
        <a href="{{ route('pengukuran.create', ['balita' => $balita->id_balita]) }}" class="bg-blue-600 text-white rounded px-4 py-2">+ Tambah Pengukuran</a>
    </div>
</div>
@endsection
