<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandarAntropometri extends Model
{
    protected $table = 'standar_antropometri';
    protected $primaryKey = 'id_standar';
    public $timestamps = false;

    protected $fillable = [
        'indikator', 'jenis_kelamin', 'nilai_acuan',
        'sd_min3', 'sd_min2', 'sd_min1', 'median', 'sd_plus1', 'sd_plus2', 'sd_plus3',
    ];

    protected $casts = [
        'nilai_acuan' => 'float',
        'sd_min3' => 'float',
        'sd_min2' => 'float',
        'sd_min1' => 'float',
        'median' => 'float',
        'sd_plus1' => 'float',
        'sd_plus2' => 'float',
        'sd_plus3' => 'float',
    ];
}
