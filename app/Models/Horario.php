<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Horario extends Model
{
    protected $fillable = [
        'rota_id',
        'hora_saida',
        'hora_chegada',
        'dias_semana',
        'ativo'
    ];

    protected $casts = [
        'dias_semana' => 'array',
        'ativo' => 'boolean'
    ];

    public function rota(): BelongsTo
    {
        return $this->belongsTo(Rota::class);
    }
}