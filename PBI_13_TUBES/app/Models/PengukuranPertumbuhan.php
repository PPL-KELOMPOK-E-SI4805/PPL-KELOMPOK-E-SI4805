<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PengukuranPertumbuhan extends Model
{
    protected $table = 'pengukuran_pertumbuhan';
    protected $primaryKey = 'id_pengukuran';
    public $timestamps = false;

    protected $fillable = [
        'id_balita', 'id_kader', 'tanggal_ukur', 'usia_bulan',
        'berat_badan', 'tinggi_badan', 'cara_ukur',
        'lingkar_kepala', 'lingkar_lengan', 'catatan',
    ];

    protected $casts = [
        'tanggal_ukur' => 'date',
        'usia_bulan' => 'integer',
        'berat_badan' => 'float',
        'tinggi_badan' => 'float',
        'lingkar_kepala' => 'float',
        'lingkar_lengan' => 'float',
    ];

    public function getRouteKeyName(): string
    {
        return 'id_pengukuran';
    }

    public function balita(): BelongsTo
    {
        return $this->belongsTo(Balita::class, 'id_balita', 'id_balita');
    }

    public function kader(): BelongsTo
    {
        return $this->belongsTo(Kader::class, 'id_kader', 'id_kader');
    }

    public function statusGizi(): HasOne
    {
        return $this->hasOne(StatusGizi::class, 'id_pengukuran', 'id_pengukuran');
    }
}
