<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Parada extends Model
{
    protected $fillable = [
        'nome',
        'endereco',
        'ponto_referencia',
        'latitude',
        'longitude',
        'tipo',
        'status'
    ];
    
    public function rotas(): BelongsToMany
    {
        return $this->belongsToMany(Rota::class, 'rota_parada')
                    ->withPivot('ordem', 'horario_estimado')
                    ->withTimestamps();
    }
}