<?php

use App\Http\Controllers\PengukuranPertumbuhanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes PBI-13: Pengukuran Pertumbuhan & Status Gizi
|--------------------------------------------------------------------------
| Tambahkan di routes/web.php:   require __DIR__.'/pbi13.php';
| Middleware 'role' perlu didaftarkan alias-nya (lihat README-PBI13.md).
*/

Route::middleware(['auth', 'role:kader'])->group(function () {
    Route::post('pengukuran/hitung', [PengukuranPertumbuhanController::class, 'hitung'])
        ->name('pengukuran.hitung');

    Route::resource('pengukuran', PengukuranPertumbuhanController::class)
        ->parameters(['pengukuran' => 'pengukuran']);
});
