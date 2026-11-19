@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto p-4 space-y-4">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold">Pengukuran Pertumbuhan</h1>
        <a href="{{ route('pengukuran.create') }}" class="bg-blue-600 text-white rounded px-4 py-2">+ Tambah Pengukuran</a>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-300 text-green-800 rounded p-3">{{ session('success') }}</div>
    @endif

    <form method="GET" class="flex gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama balita" class="border rounded p-2 flex-1">
        <button class="border rounded px-4">Cari</button>
    </form>

    <table class="w-full text-sm bg-white border rounded">
        <thead class="bg-gray-50 text-left">
            <tr>
                <th class="p-2">Tanggal</th><th>Balita</th><th>Usia</th><th>BB (kg)</th><th>PB/TB (cm)</th>
                <th>BB/U</th><th>PB-TB/U</th><th>BB/PB-TB</th><th></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($pengukuran as $p)
            <tr class="border-t">
                <td class="p-2">{{ $p->tanggal_ukur->format('d/m/Y') }}</td>
                <td>{{ $p->balita->nama }}</td>
                <td>{{ $p->usia_bulan }} bln</td>
                <td>{{ number_format($p->berat_badan, 2) }}</td>
                <td>{{ number_format($p->tinggi_badan, 1) }}</td>
                <td>{{ $p->statusGizi?->status_bb_u ?? '-' }}</td>
                <td>{{ $p->statusGizi?->status_tb_u ?? '-' }}</td>
                <td>{{ $p->statusGizi?->status_bb_tb ?? '-' }}</td>
                <td><a class="text-blue-600" href="{{ route('pengukuran.show', $p) }}">Lihat</a></td>
            </tr>
        @empty
            <tr><td colspan="9" class="p-4 text-center text-gray-500">Belum ada data pengukuran.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $pengukuran->links() }}
</div>
@endsection
