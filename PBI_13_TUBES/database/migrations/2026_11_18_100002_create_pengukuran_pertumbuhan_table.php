<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PBI-13: riwayat pengukuran pertumbuhan balita per kunjungan.
 *
 * Bergantung pada tabel `balita` dan `kader` (PBI-1 / PBI-7). Jika timestamp
 * migration milik tim lebih baru dari file ini, ganti nama file ini agar
 * urutannya setelah migration `balita` dan `kader`.
 *
 * Penambahan di luar ERD: kolom `catatan` (ada di mockup: "Catatan Kader & Kondisi Balita").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengukuran_pertumbuhan', function (Blueprint $table) {
            $table->id('id_pengukuran');
            $table->unsignedBigInteger('id_balita');
            $table->unsignedBigInteger('id_kader');
            $table->date('tanggal_ukur');
            $table->unsignedTinyInteger('usia_bulan');
            $table->decimal('berat_badan', 5, 2);
            $table->decimal('tinggi_badan', 5, 1);
            $table->enum('cara_ukur', ['terlentang', 'berdiri']);
            $table->decimal('lingkar_kepala', 4, 1)->nullable();
            $table->decimal('lingkar_lengan', 4, 1)->nullable();
            $table->text('catatan')->nullable();

            $table->foreign('id_balita')->references('id_balita')->on('balita')->cascadeOnDelete();
            $table->foreign('id_kader')->references('id_kader')->on('kader');

            $table->index(['id_balita', 'tanggal_ukur']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengukuran_pertumbuhan');
    }
};
