<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aluno extends Model
{
    protected $fillable = [
        'nome',
        'descricao',
        'data_nascimento',
        'responsavel',
        'telefone_responsavel',
        'endereco',
        'ponto_referencia',
        'status'
    ];

    public function presencas(): HasMany
    {
        return $this->hasMany(Presenca::class);
    }
}