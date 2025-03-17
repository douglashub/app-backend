<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Viagem extends Model
{
    protected $table = 'viagens';
    
    protected $fillable = [
        'horario_id',
        'rota_id',
        'onibus_id',
        'motorista_id',
        'monitor_id',
        'data_viagem',
        'hora_saida_prevista',
        'hora_chegada_prevista',
        'hora_saida_real',
        'hora_chegada_real',
        'observacoes',
        'status',
    ];

    // Add the horario relationship
    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class);
    }

    // Existing relationships remain the same
    public function rota(): BelongsTo
    {
        return $this->belongsTo(Rota::class);
    }

    public function onibus(): BelongsTo
    {
        return $this->belongsTo(Onibus::class);
    }

    public function motorista(): BelongsTo
    {
        return $this->belongsTo(Motorista::class);
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class)->withDefault();
    }

    public function presencas(): HasMany
    {
        return $this->hasMany(Presenca::class);
    }
}