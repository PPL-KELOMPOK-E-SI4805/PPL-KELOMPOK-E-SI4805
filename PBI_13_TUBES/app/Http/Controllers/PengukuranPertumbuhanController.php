<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengukuranRequest;
use App\Models\Balita;
use App\Models\PengukuranPertumbuhan;
use App\Services\StatusGiziService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengukuranPertumbuhanController extends Controller
{
    public function __construct(private StatusGiziService $service)
    {
    }

    /** Daftar pengukuran terkini (semua balita), dengan pencarian nama. */
    public function index(Request $request)
    {
        $pengukuran = PengukuranPertumbuhan::with(['balita', 'statusGizi', 'kader'])
            ->when($request->query('q'), fn ($q, $kata) => $q->whereHas(
                'balita', fn ($b) => $b->where('nama', 'like', "%{$kata}%")
            ))
            ->orderByDesc('tanggal_ukur')
            ->paginate(15)
            ->withQueryString();

        return view('pengukuran.index', compact('pengukuran'));
    }

    /** Form input pengukuran baru. Balita bisa dipilih lewat ?balita=ID. */
    public function create(Request $request)
    {
        $balita = $request->query('balita') ? Balita::find($request->query('balita')) : null;
        $daftarBalita = Balita::orderBy('nama')->get(['id_balita', 'nama', 'tanggal_lahir', 'jenis_kelamin']);

        return view('pengukuran.create', [
            'balita' => $balita,
            'daftarBalita' => $daftarBalita,
            'pengukuran' => null,
            'hasil' => null,
            'sebelumnya' => null,
        ]);
    }

    public function store(PengukuranRequest $request)
    {
        $balita = Balita::findOrFail($request->validated('id_balita'));

        try {
            $pengukuran = $this->service->simpan(
                $balita,
                $request->user()->kader->id_kader,
                $request->validated()
            );
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['tanggal_ukur' => $e->getMessage()]);
        }

        $pesan = sprintf(
            'Pengukuran untuk %s (%d bulan) berhasil dicatat oleh Kader %s pada %s WIB',
            $balita->nama,
            $pengukuran->usia_bulan,
            $request->user()->name,
            now('Asia/Jakarta')->translatedFormat('d M Y, H:i')
        );

        return redirect()->route('pengukuran.show', $pengukuran)->with('success', $pesan);
    }

    /** Hasil penilaian status gizi (panel hasil pada mockup). */
    public function show(PengukuranPertumbuhan $pengukuran)
    {
        $pengukuran->load(['balita', 'statusGizi', 'kader']);
        $balita = $pengukuran->balita;

        $sebelumnya = $this->service->pengukuranSebelumnya(
            $balita, $pengukuran->tanggal_ukur->toDateString(), $pengukuran->id_pengukuran
        );

        return view('pengukuran.show', [
            'pengukuran' => $pengukuran,
            'balita' => $balita,
            'status' => $pengukuran->statusGizi,
            'sebelumnya' => $sebelumnya,
            'kbm' => $this->hitungKbm($pengukuran, $sebelumnya),
        ]);
    }

    public function edit(PengukuranPertumbuhan $pengukuran)
    {
        $pengukuran->load('balita');

        return view('pengukuran.create', [
            'balita' => $pengukuran->balita,
            'daftarBalita' => collect(),
            'pengukuran' => $pengukuran,
            'hasil' => null,
            'sebelumnya' => $this->service->pengukuranSebelumnya(
                $pengukuran->balita, $pengukuran->tanggal_ukur->toDateString(), $pengukuran->id_pengukuran
            ),
        ]);
    }

    public function update(PengukuranRequest $request, PengukuranPertumbuhan $pengukuran)
    {
        try {
            $this->service->perbarui($pengukuran, $request->validated());
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['tanggal_ukur' => $e->getMessage()]);
        }

        return redirect()->route('pengukuran.show', $pengukuran)
            ->with('success', 'Pengukuran berhasil diperbarui dan status gizi dihitung ulang.');
    }

    /** Hapus pengukuran; status_gizi ikut terhapus lewat FK cascade (NFR-10). */
    public function destroy(PengukuranPertumbuhan $pengukuran)
    {
        $idBalita = $pengukuran->id_balita;
        $pengukuran->delete();

        return redirect()->route('pengukuran.index', ['balita' => $idBalita])
            ->with('success', 'Pengukuran berhasil dihapus.');
    }

    /**
     * Tombol "Hitung Ulang": hitung dari angka di form tanpa menyimpan (AJAX).
     */
    public function hitung(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_balita' => ['required', 'exists:balita,id_balita'],
            'tanggal_ukur' => ['required', 'date', 'before_or_equal:today'],
            'berat_badan' => ['required', 'numeric', 'min:1', 'max:30'],
            'tinggi_badan' => ['required', 'numeric', 'min:30', 'max:130'],
            'cara_ukur' => ['required', 'in:terlentang,berdiri'],
        ]);

        try {
            return response()->json($this->service->hitung(Balita::findOrFail($data['id_balita']), $data));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Kenaikan Berat Badan Minimal (KBM): target 200 gram per bulan antar penimbangan
     * berurutan (angka 200 gr mengikuti mockup; konfirmasi sumbernya ke tim).
     */
    private function hitungKbm(PengukuranPertumbuhan $sekarang, ?PengukuranPertumbuhan $lalu): ?array
    {
        if (! $lalu) {
            return null;
        }

        $selisihGram = (int) round(($sekarang->berat_badan - $lalu->berat_badan) * 1000);

        return [
            'selisih_gram' => $selisihGram,
            'target_gram' => 200,
            'lulus' => $selisihGram >= 200,
        ];
    }
}
