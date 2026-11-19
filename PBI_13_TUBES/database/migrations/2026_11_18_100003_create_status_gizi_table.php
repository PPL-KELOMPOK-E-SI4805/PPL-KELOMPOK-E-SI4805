<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PBI-13: hasil klasifikasi status gizi (1:1 dengan pengukuran_pertumbuhan).
 *
 * Kolom *_tb_u menyimpan PB/U (usia < 24 bln) atau TB/U (usia >= 24 bln),
 * kolom *_bb_tb menyimpan BB/PB atau BB/TB. Kolom boleh null bila tabel standar
 * untuk nilai tersebut tidak ditemukan (mis. panjang badan di luar rentang tabel).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_gizi', function (Blueprint $table) {
            $table->id('id_status_gizi');
            $table->unsignedBigInteger('id_pengukuran')->unique();
            $table->decimal('zscore_bb_u', 5, 2)->nullable();
            $table->decimal('zscore_tb_u', 5, 2)->nullable();
            $table->decimal('zscore_bb_tb', 5, 2)->nullable();
            $table->string('status_bb_u', 30)->nullable();
            $table->string('status_tb_u', 30)->nullable();
            $table->string('status_bb_tb', 30)->nullable();
            $table->dateTime('tanggal_hitung');

            // Hapus pengukuran => status gizi ikut terhapus (NFR-10).
            $table->foreign('id_pengukuran')->references('id_pengukuran')
                ->on('pengukuran_pertumbuhan')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_gizi');
    }
};
