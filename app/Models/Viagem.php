<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Viagem extends Model
{
    protected $table = 'viagens';
    
    protected $fillable = [
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

    // Define the relationship between Viagem and Rota
    public function rota(): BelongsTo
    {
        return $this->belongsTo(Rota::class);
    }

    // Define the relationship between Viagem and Onibus
    public function onibus(): BelongsTo
    {
        return $this->belongsTo(Onibus::class);
    }

    // Define the relationship between Viagem and Motorista
    public function motorista(): BelongsTo
    {
        return $this->belongsTo(Motorista::class);
    }

    // Define the relationship between Viagem and Monitor (nullable)
    public function monitor(): BelongsTo
    {
        // Handle nullability of monitor_id (monitor can be null)
        return $this->belongsTo(Monitor::class)->withDefault(); // Add a default value for nullable relationships
    }

    // Define the relationship between Viagem and Presenca
    public function presencas(): HasMany
    {
        return $this->hasMany(Presenca::class);
    }
}
