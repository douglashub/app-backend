<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Motorista extends Model
{
    protected $fillable = [
        'nome',
        'cpf',
        'cnh',
        'categoria_cnh',
        'validade_cnh',
        'telefone',
        'endereco',
        'data_contratacao',
        'status'
    ];

    public function viagens(): HasMany
    {
        return $this->hasMany(Viagem::class);
    }
}