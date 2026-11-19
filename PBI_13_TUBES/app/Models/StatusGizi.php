<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusGizi extends Model
{
    protected $table = 'status_gizi';
    protected $primaryKey = 'id_status_gizi';
    public $timestamps = false;

    protected $fillable = [
        'id_pengukuran',
        'zscore_bb_u', 'zscore_tb_u', 'zscore_bb_tb',
        'status_bb_u', 'status_tb_u', 'status_bb_tb',
        'tanggal_hitung',
    ];

    protected $casts = [
        'zscore_bb_u' => 'float',
        'zscore_tb_u' => 'float',
        'zscore_bb_tb' => 'float',
        'tanggal_hitung' => 'datetime',
    ];

    public function pengukuran(): BelongsTo
    {
        return $this->belongsTo(PengukuranPertumbuhan::class, 'id_pengukuran', 'id_pengukuran');
    }
}
