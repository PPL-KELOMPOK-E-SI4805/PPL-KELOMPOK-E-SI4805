<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PBI-13: tabel referensi Standar Antropometri Anak (Permenkes No. 2 Tahun 2020).
 *
 * Catatan perbedaan dengan ERD di proposal:
 *  - enum `indikator` dipecah menjadi bb_u, pb_u, tb_u, bb_pb, bb_tb (+ lk_u untuk
 *    lingkar kepala), karena PB/U (0-23 bln) dan TB/U (24-60 bln) serta BB/PB dan
 *    BB/TB memakai tabel standar yang berbeda dan nilai acuannya saling tumpang tindih.
 *  - `nilai_acuan` = usia (bulan) untuk bb_u/pb_u/tb_u/lk_u, dan panjang/tinggi (cm)
 *    untuk bb_pb/bb_tb.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standar_antropometri', function (Blueprint $table) {
            $table->increments('id_standar');
            $table->enum('indikator', ['bb_u', 'pb_u', 'tb_u', 'bb_pb', 'bb_tb', 'lk_u']);
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->decimal('nilai_acuan', 5, 1);
            $table->decimal('sd_min3', 5, 2);
            $table->decimal('sd_min2', 5, 2);
            $table->decimal('sd_min1', 5, 2);
            $table->decimal('median', 5, 2);
            $table->decimal('sd_plus1', 5, 2);
            $table->decimal('sd_plus2', 5, 2);
            $table->decimal('sd_plus3', 5, 2);

            $table->unique(['indikator', 'jenis_kelamin', 'nilai_acuan'], 'uq_standar_antropometri');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standar_antropometri');
    }
};
