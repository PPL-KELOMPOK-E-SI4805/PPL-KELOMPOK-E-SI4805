<?php

namespace Tests\Unit;

use App\Services\ZScoreCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Tes logika murni. Baris standar di bawah adalah DATA UJI buatan (median 10, SD 1),
 * bukan nilai Permenkes, supaya hasil hitung bisa diverifikasi dengan tangan.
 */
class ZScoreCalculatorTest extends TestCase
{
    private array $std = [
        'sd_min3' => 7.0, 'sd_min2' => 8.0, 'sd_min1' => 9.0, 'median' => 10.0,
        'sd_plus1' => 11.5, 'sd_plus2' => 13.0, 'sd_plus3' => 14.5,
    ];

    public function test_zscore_di_median_adalah_nol(): void
    {
        $this->assertSame(0.0, ZScoreCalculator::zscore(10.0, $this->std));
    }

    public function test_zscore_di_bawah_median_memakai_selisih_sd_negatif(): void
    {
        // (8.5 - 10) / (10 - 9) = -1.5
        $this->assertSame(-1.5, ZScoreCalculator::zscore(8.5, $this->std));
    }

    public function test_zscore_di_atas_median_memakai_selisih_sd_positif(): void
    {
        // (12.25 - 10) / (11.5 - 10) = 1.5
        $this->assertSame(1.5, ZScoreCalculator::zscore(12.25, $this->std));
    }

    public function test_data_standar_tidak_valid_melempar_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ZScoreCalculator::zscore(5.0, array_merge($this->std, ['sd_min1' => 10.0]));
    }

    public function test_koreksi_tinggi_sesuai_usia_dan_cara_ukur(): void
    {
        // < 24 bln diukur berdiri -> +0.7
        $this->assertSame(80.7, ZScoreCalculator::koreksiTinggi(80.0, 12, 'berdiri'));
        // >= 24 bln diukur terlentang -> -0.7
        $this->assertSame(86.3, ZScoreCalculator::koreksiTinggi(87.0, 30, 'terlentang'));
        // sesuai -> tidak berubah
        $this->assertSame(75.0, ZScoreCalculator::koreksiTinggi(75.0, 10, 'terlentang'));
        $this->assertSame(90.0, ZScoreCalculator::koreksiTinggi(90.0, 36, 'berdiri'));
        // batas tepat 24 bulan dianggap berdiri
        $this->assertSame(84.3, ZScoreCalculator::koreksiTinggi(85.0, 24, 'terlentang'));
    }

    public function test_pemilihan_indikator_berdasarkan_usia(): void
    {
        $this->assertSame('pb_u', ZScoreCalculator::indikatorTinggi(23));
        $this->assertSame('tb_u', ZScoreCalculator::indikatorTinggi(24));
        $this->assertSame('bb_pb', ZScoreCalculator::indikatorBBTinggi(0));
        $this->assertSame('bb_tb', ZScoreCalculator::indikatorBBTinggi(60));
    }

    public function test_pembulatan_tinggi_acuan_ke_setengah_cm(): void
    {
        $this->assertSame(75.0, ZScoreCalculator::bulatkanTinggiAcuan(75.2));
        $this->assertSame(75.5, ZScoreCalculator::bulatkanTinggiAcuan(75.3));
        $this->assertSame(76.0, ZScoreCalculator::bulatkanTinggiAcuan(75.8));
    }

    /** @dataProvider batasBBU */
    public function test_kategori_bb_u(float $z, string $harapan): void
    {
        $this->assertSame($harapan, ZScoreCalculator::kategoriBBU($z));
    }

    public static function batasBBU(): array
    {
        return [
            [-3.01, 'Berat badan sangat kurang'],
            [-3.0, 'Berat badan kurang'],
            [-2.01, 'Berat badan kurang'],
            [-2.0, 'Berat badan normal'],
            [1.0, 'Berat badan normal'],
            [1.01, 'Risiko berat badan lebih'],
        ];
    }

    /** @dataProvider batasTBU */
    public function test_kategori_tb_u(float $z, string $harapan): void
    {
        $this->assertSame($harapan, ZScoreCalculator::kategoriTBU($z));
    }

    public static function batasTBU(): array
    {
        return [
            [-3.01, 'Sangat pendek'],
            [-2.5, 'Pendek'],
            [-2.0, 'Normal'],
            [3.0, 'Normal'],
            [3.01, 'Tinggi'],
        ];
    }

    /** @dataProvider batasBBTB */
    public function test_kategori_bb_tb(float $z, string $harapan): void
    {
        $this->assertSame($harapan, ZScoreCalculator::kategoriBBTB($z));
    }

    public static function batasBBTB(): array
    {
        return [
            [-3.01, 'Gizi buruk'],
            [-2.5, 'Gizi kurang'],
            [-2.0, 'Gizi baik'],
            [1.0, 'Gizi baik'],
            [1.5, 'Berisiko gizi lebih'],
            [2.5, 'Gizi lebih'],
            [3.01, 'Obesitas'],
        ];
    }

    public function test_kategori_pita_lila(): void
    {
        $this->assertSame('Hijau', ZScoreCalculator::kategoriPitaLila(12.6));
        $this->assertSame('Kuning', ZScoreCalculator::kategoriPitaLila(12.5));
        $this->assertSame('Merah', ZScoreCalculator::kategoriPitaLila(11.4));
    }
}
