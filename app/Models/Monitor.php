<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Monitor extends Model
{
    protected $table = 'monitores';
    
    protected $fillable = [
        'nome',
        'cpf',
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