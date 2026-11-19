<?php

namespace App\Services;

use App\Models\Balita;
use App\Models\PengukuranPertumbuhan;
use App\Models\StandarAntropometri;
use App\Models\StatusGizi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Menyimpan pengukuran dan menghitung status gizi otomatis (PBI-13 / FR-15).
 */
class StatusGiziService
{
    /** Usia dalam bulan penuh pada tanggal ukur. */
    public function hitungUsiaBulan(Carbon $tanggalLahir, Carbon $tanggalUkur): int
    {
        return $tanggalLahir->copy()->startOfDay()->diffInMonths($tanggalUkur->copy()->startOfDay());
    }

    public function hitungUsiaHari(Carbon $tanggalLahir, Carbon $tanggalUkur): int
    {
        return $tanggalLahir->copy()->startOfDay()->diffInDays($tanggalUkur->copy()->startOfDay());
    }

    /**
     * Hitung status gizi TANPA menyimpan (dipakai tombol "Hitung Ulang" dan
     * penyimpanan). Mengembalikan array hasil, atau melempar
     * \DomainException bila usia di luar cakupan standar.
     *
     * @param array{tanggal_ukur:string, berat_badan:float|string, tinggi_badan:float|string, cara_ukur:string} $data
     */
    public function hitung(Balita $balita, array $data): array
    {
        $tanggalUkur = Carbon::parse($data['tanggal_ukur']);
        $usia = $this->hitungUsiaBulan(Carbon::parse($balita->tanggal_lahir), $tanggalUkur);

        if ($usia > ZScoreCalculator::BATAS_USIA_BULAN) {
            throw new \DomainException(
                'Standar antropometri hanya berlaku untuk usia 0-60 bulan. Status gizi tidak dihitung.'
            );
        }

        $bb = (float) $data['berat_badan'];
        $tinggiAsli = (float) $data['tinggi_badan'];
        $tinggi = ZScoreCalculator::koreksiTinggi($tinggiAsli, $usia, $data['cara_ukur']);

        $jk = $balita->jenis_kelamin;
        $indTinggi = ZScoreCalculator::indikatorTinggi($usia);
        $indBBTinggi = ZScoreCalculator::indikatorBBTinggi($usia);

        $zBBU = $this->zscoreDariTabel('bb_u', $jk, $usia, $bb);
        $zTBU = $this->zscoreDariTabel($indTinggi, $jk, $usia, $tinggi);
        $zBBTB = $this->zscoreDariTabel(
            $indBBTinggi, $jk, ZScoreCalculator::bulatkanTinggiAcuan($tinggi), $bb
        );

        return [
            'usia_bulan' => $usia,
            'usia_hari' => $this->hitungUsiaHari(Carbon::parse($balita->tanggal_lahir), $tanggalUkur),
            'tinggi_terkoreksi' => $tinggi,
            'indikator_tinggi' => $indTinggi,
            'indikator_bb_tinggi' => $indBBTinggi,
            'zscore_bb_u' => $zBBU,
            'zscore_tb_u' => $zTBU,
            'zscore_bb_tb' => $zBBTB,
            'status_bb_u' => $zBBU === null ? null : ZScoreCalculator::kategoriBBU($zBBU),
            'status_tb_u' => $zTBU === null ? null : ZScoreCalculator::kategoriTBU($zTBU),
            'status_bb_tb' => $zBBTB === null ? null : ZScoreCalculator::kategoriBBTB($zBBTB),
        ];
    }

    /** Simpan pengukuran baru + hitung status gizi dalam satu transaksi. */
    public function simpan(Balita $balita, int $idKader, array $data): PengukuranPertumbuhan
    {
        return DB::transaction(function () use ($balita, $idKader, $data) {
            $hasil = $this->hitung($balita, $data);

            $pengukuran = PengukuranPertumbuhan::create([
                'id_balita' => $balita->id_balita,
                'id_kader' => $idKader,
                'tanggal_ukur' => $data['tanggal_ukur'],
                'usia_bulan' => $hasil['usia_bulan'],
                'berat_badan' => $data['berat_badan'],
                'tinggi_badan' => $data['tinggi_badan'],
                'cara_ukur' => $data['cara_ukur'],
                'lingkar_kepala' => $data['lingkar_kepala'] ?? null,
                'lingkar_lengan' => $data['lingkar_lengan'] ?? null,
                'catatan' => $data['catatan'] ?? null,
            ]);

            $this->simpanStatusGizi($pengukuran, $hasil);

            return $pengukuran->load('statusGizi');
        });
    }

    /** Ubah pengukuran lalu hitung ulang status gizinya. */
    public function perbarui(PengukuranPertumbuhan $pengukuran, array $data): PengukuranPertumbuhan
    {
        return DB::transaction(function () use ($pengukuran, $data) {
            $balita = $pengukuran->balita;
            $hasil = $this->hitung($balita, $data);

            $pengukuran->update([
                'tanggal_ukur' => $data['tanggal_ukur'],
                'usia_bulan' => $hasil['usia_bulan'],
                'berat_badan' => $data['berat_badan'],
                'tinggi_badan' => $data['tinggi_badan'],
                'cara_ukur' => $data['cara_ukur'],
                'lingkar_kepala' => $data['lingkar_kepala'] ?? null,
                'lingkar_lengan' => $data['lingkar_lengan'] ?? null,
                'catatan' => $data['catatan'] ?? null,
            ]);

            $this->simpanStatusGizi($pengukuran, $hasil);

            return $pengukuran->load('statusGizi');
        });
    }

    /** Pengukuran sebelum tanggal tertentu, dipakai untuk "Perbandingan Penimbangan Lalu". */
    public function pengukuranSebelumnya(Balita $balita, string $tanggalUkur, ?int $kecualiId = null): ?PengukuranPertumbuhan
    {
        return PengukuranPertumbuhan::where('id_balita', $balita->id_balita)
            ->whereDate('tanggal_ukur', '<', $tanggalUkur)
            ->when($kecualiId, fn ($q) => $q->where('id_pengukuran', '!=', $kecualiId))
            ->orderByDesc('tanggal_ukur')
            ->first();
    }

    private function simpanStatusGizi(PengukuranPertumbuhan $pengukuran, array $hasil): void
    {
        StatusGizi::updateOrCreate(
            ['id_pengukuran' => $pengukuran->id_pengukuran],
            [
                'zscore_bb_u' => $hasil['zscore_bb_u'],
                'zscore_tb_u' => $hasil['zscore_tb_u'],
                'zscore_bb_tb' => $hasil['zscore_bb_tb'],
                'status_bb_u' => $hasil['status_bb_u'],
                'status_tb_u' => $hasil['status_tb_u'],
                'status_bb_tb' => $hasil['status_bb_tb'],
                'tanggal_hitung' => now(),
            ]
        );
    }

    private function zscoreDariTabel(string $indikator, string $jk, float|int $acuan, float $nilai): ?float
    {
        $standar = StandarAntropometri::where('indikator', $indikator)
            ->where('jenis_kelamin', $jk)
            ->where('nilai_acuan', $acuan)
            ->first();

        if (! $standar) {
            return null; // tabel standar tidak memuat nilai acuan ini
        }

        return ZScoreCalculator::zscore($nilai, $standar->only([
            'sd_min3', 'sd_min2', 'sd_min1', 'median', 'sd_plus1', 'sd_plus2', 'sd_plus3',
        ]));
    }
}
