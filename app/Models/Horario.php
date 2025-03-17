<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Horario extends Model
{
    protected $fillable = [
        'rota_id',
        'hora_inicio',  // Changed from 'hora_saida'
        'hora_fim',     // Changed from 'hora_chegada'
        'dias_semana',
        'status'        // Changed from 'ativo'
    ];

    protected $casts = [
        'dias_semana' => 'array',
        'status' => 'boolean'  // Changed from 'ativo'
    ];

    public function rota(): BelongsTo
    {
        return $this->belongsTo(Rota::class);
    }
}