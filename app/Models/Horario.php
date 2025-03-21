<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Horario extends Model
{
    protected $fillable = [
        'rota_id',
        'nome',
        'descricao',
        'hora_inicio',
        'hora_fim',
        'dias_semana',
        'status',
        'tipo'
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
