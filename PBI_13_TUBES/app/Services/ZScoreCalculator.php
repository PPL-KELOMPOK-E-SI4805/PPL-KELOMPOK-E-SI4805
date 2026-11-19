<?php

namespace App\Services;

/**
 * Logika murni (tanpa database/framework) untuk Z-score dan klasifikasi status gizi
 * menurut Permenkes No. 2 Tahun 2020 tentang Standar Antropometri Anak.
 *
 * Satu baris standar berbentuk array:
 *   ['sd_min3'=>.., 'sd_min2'=>.., 'sd_min1'=>.., 'median'=>.., 'sd_plus1'=>.., 'sd_plus2'=>.., 'sd_plus3'=>..]
 */
class ZScoreCalculator
{
    public const BATAS_USIA_BULAN = 60;
    public const KOREKSI_CM = 0.7;
    public const BATAS_PB_TB_BULAN = 24;

    /**
     * Z-score = (nilai - median) / (selisih median ke +-1 SD), tergantung sisi median.
     */
    public static function zscore(float $nilai, array $standar): float
    {
        $median = (float) $standar['median'];

        if ($nilai < $median) {
            $pembagi = $median - (float) $standar['sd_min1'];
        } else {
            $pembagi = (float) $standar['sd_plus1'] - $median;
        }

        if ($pembagi <= 0) {
            throw new \InvalidArgumentException('Data standar tidak valid: selisih SD bernilai nol atau negatif.');
        }

        return round(($nilai - $median) / $pembagi, 2);
    }

    /**
     * Koreksi panjang/tinggi badan bila cara ukur tidak sesuai usia.
     * < 24 bulan harus terlentang (PB); >= 24 bulan harus berdiri (TB).
     * Terlentang vs berdiri selisihnya 0,7 cm (PB lebih panjang dari TB).
     */
    public static function koreksiTinggi(float $tinggi, int $usiaBulan, string $caraUkur): float
    {
        if ($usiaBulan < self::BATAS_PB_TB_BULAN && $caraUkur === 'berdiri') {
            return round($tinggi + self::KOREKSI_CM, 1);
        }

        if ($usiaBulan >= self::BATAS_PB_TB_BULAN && $caraUkur === 'terlentang') {
            return round($tinggi - self::KOREKSI_CM, 1);
        }

        return round($tinggi, 1);
    }

    public static function indikatorTinggi(int $usiaBulan): string
    {
        return $usiaBulan < self::BATAS_PB_TB_BULAN ? 'pb_u' : 'tb_u';
    }

    public static function indikatorBBTinggi(int $usiaBulan): string
    {
        return $usiaBulan < self::BATAS_PB_TB_BULAN ? 'bb_pb' : 'bb_tb';
    }

    /** Pembulatan ke kelipatan 0,5 cm (resolusi tabel BB/PB dan BB/TB). */
    public static function bulatkanTinggiAcuan(float $tinggi): float
    {
        return round($tinggi * 2) / 2;
    }

    // ---- Klasifikasi (Permenkes No. 2 Tahun 2020, Tabel Kategori & Ambang Batas) ----

    public static function kategoriBBU(float $z): string
    {
        return match (true) {
            $z < -3 => 'Berat badan sangat kurang',
            $z < -2 => 'Berat badan kurang',
            $z <= 1 => 'Berat badan normal',
            default => 'Risiko berat badan lebih',
        };
    }

    /** Berlaku untuk PB/U maupun TB/U. */
    public static function kategoriTBU(float $z): string
    {
        return match (true) {
            $z < -3 => 'Sangat pendek',
            $z < -2 => 'Pendek',
            $z <= 3 => 'Normal',
            default => 'Tinggi',
        };
    }

    /** Berlaku untuk BB/PB maupun BB/TB. */
    public static function kategoriBBTB(float $z): string
    {
        return match (true) {
            $z < -3 => 'Gizi buruk',
            $z < -2 => 'Gizi kurang',
            $z <= 1 => 'Gizi baik',
            $z <= 2 => 'Berisiko gizi lebih',
            $z <= 3 => 'Gizi lebih',
            default => 'Obesitas',
        };
    }

    /** Kategori LiLA (pita) sesuai mockup: Hijau bila > 12,5 cm. */
    public static function kategoriPitaLila(float $lila): string
    {
        return match (true) {
            $lila > 12.5 => 'Hijau',
            $lila >= 11.5 => 'Kuning',
            default => 'Merah',
        };
    }
}
