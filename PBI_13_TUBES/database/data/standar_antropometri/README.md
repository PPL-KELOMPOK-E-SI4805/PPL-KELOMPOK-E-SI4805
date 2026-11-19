# Data standar antropometri (Permenkes No. 2 Tahun 2020)

Taruh file CSV di folder ini, lalu jalankan `php artisan db:seed --class=StandarAntropometriSeeder`.

| File | Isi | `nilai_acuan` |
|------|-----|---------------|
| `bb_u_L.csv`, `bb_u_P.csv` | BB menurut umur (0-60 bln) | usia (bulan) |
| `pb_u_L.csv`, `pb_u_P.csv` | PB menurut umur (0-23 bln) | usia (bulan) |
| `tb_u_L.csv`, `tb_u_P.csv` | TB menurut umur (24-60 bln) | usia (bulan) |
| `bb_pb_L.csv`, `bb_pb_P.csv` | BB menurut PB (45-110 cm) | panjang badan (cm) |
| `bb_tb_L.csv`, `bb_tb_P.csv` | BB menurut TB (65-120 cm) | tinggi badan (cm) |
| `lk_u_L.csv`, `lk_u_P.csv` | (opsional) lingkar kepala menurut umur | usia (bulan) |

Header kolom (wajib persis):

```
nilai_acuan,sd_min3,sd_min2,sd_min1,median,sd_plus1,sd_plus2,sd_plus3
```

Contoh format (ANGKA DI BAWAH HANYA ILUSTRASI FORMAT, JANGAN DIPAKAI):

```
nilai_acuan,sd_min3,sd_min2,sd_min1,median,sd_plus1,sd_plus2,sd_plus3
0,0.0,0.0,0.0,0.0,0.0,0.0,0.0
```

Sumber angka: lampiran Permenkes No. 2 Tahun 2020 tentang Standar Antropometri Anak
(tabel "Standar BB/U", "PB/U", "TB/U", "BB/PB", "BB/TB").
