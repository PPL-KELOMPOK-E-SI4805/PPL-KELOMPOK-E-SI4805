# PBI-13: Pengukuran Pertumbuhan & Status Gizi Otomatis

## Isi paket (struktur sama dengan root project Laravel, tinggal salin/timpa)

```
app/Http/Controllers/PengukuranPertumbuhanController.php
app/Http/Middleware/EnsureRole.php            <- STUB, hapus jika tim sudah punya middleware role
app/Http/Requests/PengukuranRequest.php
app/Models/{PengukuranPertumbuhan,StatusGizi,StandarAntropometri}.php
app/Services/{StatusGiziService,ZScoreCalculator}.php
database/migrations/2026_11_18_10000{1,2,3}_*.php
database/seeders/StandarAntropometriSeeder.php
database/data/standar_antropometri/README.md   <- taruh CSV Permenkes di sini
resources/views/pengukuran/{index,create,show}.blade.php
routes/pbi13.php
tests/Unit/ZScoreCalculatorTest.php
tests/Feature/PengukuranPertumbuhanTest.php
```

## Langkah pasang

1. Buat branch: `git checkout -b feature/pbi-13-pengukuran-status-gizi`
2. Salin isi folder ini ke root project.
3. `routes/web.php`: tambahkan `require __DIR__.'/pbi13.php';`
4. Daftarkan alias middleware `role`.
   - Laravel 11/12, di `bootstrap/app.php`:
     ```php
     ->withMiddleware(function (Middleware $middleware) {
         $middleware->alias(['role' => \App\Http\Middleware\EnsureRole::class]);
     })
     ```
   - Laravel 10: `app/Http/Kernel.php` -> `$middlewareAliases`.
5. Pastikan migration pengukuran berjalan SETELAH migration `balita` dan `kader` (ganti nama file bila timestamp tim lebih baru).
6. `php artisan migrate`
7. Isi data standar: taruh CSV Permenkes (lihat README di `database/data/standar_antropometri/`) lalu `php artisan db:seed --class=StandarAntropometriSeeder`.
8. Tes: `php artisan test`

## Asumsi yang perlu dicocokkan dengan kode PBI-1 / PBI-7

- Model `Balita` (PK `id_balita`; kolom `nama`, `nik`, `tanggal_lahir`, `jenis_kelamin` 'L'/'P') dan `Kader` (PK `id_kader`, `id_user`) sudah ada. Paket ini TIDAK membuat keduanya.
- `User` punya kolom `role` ('kader' / 'orang_tua') dan relasi `kader()` (hasOne). Controller memakai `$request->user()->kader->id_kader`.
- Layout Blade bernama `layouts.app` dengan `@yield('content')`; styling memakai kelas Tailwind.
- Feature test memakai `User::factory()`, `Kader::factory()`, `Balita::factory()`: sesuaikan `setUp()`.

## Status verifikasi

- Sintaks PHP semua file: lolos (`php -l`).
- `ZScoreCalculatorTest` (26 kasus): lulus, dijalankan dengan runner mini karena PHPUnit/Composer tidak terjangkau saat penulisan.
- BELUM dijalankan: migration, controller, view Blade, feature test. Jalankan `php artisan test` setelah dipasang dan lapor jika ada error.

## Keputusan desain yang perlu disetujui tim

- Enum `indikator` di `standar_antropometri` dipecah (bb_u, pb_u, tb_u, bb_pb, bb_tb, lk_u); ERD hanya menggambar satu enum.
- Kolom `catatan` ditambahkan ke `pengukuran_pertumbuhan` (ada di mockup, tidak di ERD).
- Kategori status gizi memakai istilah Permenkes lengkap, bukan hanya Baik/Kurang/Buruk di dashboard. Pemetaan untuk dashboard belum dibuat.
- Target KBM 200 gram mengikuti angka di mockup; sumbernya belum dikonfirmasi.
- Interpretasi Lingkar Kepala (normosefal dst.) belum diimplementasi karena belum ada tabel standar lk_u dari dokumen.
- Z-score memakai rumus sederhana (selisih SD terdekat) tanpa penyesuaian WHO untuk nilai di luar +-3 SD.
