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

    /**
     * Definição de casts para tipos de dados automáticos
     */
    protected $casts = [
        'data_viagem' => 'date:Y-m-d', // Garante que data_viagem seja formatada corretamente
        'status' => 'boolean', // Garante que status sempre seja booleano
    ];

    /**
     * Relação com Horario (Se não existir, retorna null sem erro)
     */
    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class)->withDefault();
    }

    /**
     * Relação com Rota (Evita erro quando não há relacionamento)
     */
    public function rota(): BelongsTo
    {
        return $this->belongsTo(Rota::class)->withDefault();
    }

    /**
     * Relação com Ônibus (Evita erro quando não há relacionamento)
     */
    public function onibus(): BelongsTo
    {
        return $this->belongsTo(Onibus::class)->withDefault();
    }

    /**
     * Relação com Motorista (Evita erro quando não há relacionamento)
     */
    public function motorista(): BelongsTo
    {
        return $this->belongsTo(Motorista::class)->withDefault();
    }

    /**
     * Relação com Monitor (Evita erro quando não há relacionamento)
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class)->withDefault();
    }

    /**
     * Relação com Presenças (HasMany)
     */
    public function presencas(): HasMany
    {
        return $this->hasMany(Presenca::class);
    }
}
