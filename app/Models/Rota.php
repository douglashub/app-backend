<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rota extends Model
{
    protected $fillable = [
        'nome',
        'descricao',
        'tipo',
        'distancia',
        'tempo_estimado',
        'status'
    ];

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class);
    }

    public function viagens(): HasMany
    {
        return $this->hasMany(Viagem::class);
    }

    public function subrotas(): BelongsToMany
    {
        return $this->belongsToMany(Rota::class, 'rota_subrotas', 'rota_principal_id', 'subrota_id')
            ->withPivot('ordem')
            ->withTimestamps();
    }
    
    public function paradas(): BelongsToMany
    {
        return $this->belongsToMany(Parada::class, 'rota_parada')
            ->withPivot('ordem', 'horario_estimado')
            ->withTimestamps();
    }
}