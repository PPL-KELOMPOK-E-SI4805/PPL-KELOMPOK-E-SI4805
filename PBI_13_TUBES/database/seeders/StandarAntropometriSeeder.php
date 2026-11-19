<?php

namespace Database\Seeders;

use App\Models\StandarAntropometri;
use Illuminate\Database\Seeder;

/**
 * Mengisi tabel standar_antropometri dari file CSV di database/data/standar_antropometri/.
 *
 * Nama file:  {indikator}_{L|P}.csv   contoh: bb_u_L.csv, bb_u_P.csv, pb_u_L.csv, tb_u_P.csv,
 *             bb_pb_L.csv, bb_tb_P.csv, lk_u_L.csv
 * Header CSV: nilai_acuan,sd_min3,sd_min2,sd_min1,median,sd_plus1,sd_plus2,sd_plus3
 *
 * Angka WAJIB disalin dari lampiran Permenkes No. 2 Tahun 2020 (bukan dikarang).
 * Seeder ini sengaja tidak membawa data standar bawaan.
 */
class StandarAntropometriSeeder extends Seeder
{
    public function run(): void
    {
        $dir = database_path('data/standar_antropometri');
        $files = glob($dir.'/*.csv') ?: [];

        if (! $files) {
            $this->command?->warn("Tidak ada CSV di {$dir}. Tabel standar_antropometri dibiarkan kosong.");

            return;
        }

        foreach ($files as $file) {
            if (! preg_match('/^(bb_u|pb_u|tb_u|bb_pb|bb_tb|lk_u)_([LP])\.csv$/', basename($file), $m)) {
                $this->command?->warn('Lewati file dengan nama tidak sesuai: '.basename($file));

                continue;
            }

            [, $indikator, $jk] = $m;
            $handle = fopen($file, 'r');
            $header = fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) !== count($header)) {
                    continue;
                }
                $data = array_combine($header, $row);

                StandarAntropometri::updateOrCreate(
                    ['indikator' => $indikator, 'jenis_kelamin' => $jk, 'nilai_acuan' => $data['nilai_acuan']],
                    collect($data)->except('nilai_acuan')->map(fn ($v) => (float) $v)->all()
                );
            }
            fclose($handle);
            $this->command?->info("Seed {$indikator} {$jk}: ".basename($file));
        }
    }
}
