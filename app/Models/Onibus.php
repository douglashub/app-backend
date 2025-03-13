<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Onibus extends Model
{
    protected $table = 'onibus';
    
    protected $fillable = [
        'placa',
        'capacidade',
        'modelo',
        'ano_fabricacao',
        'status'
    ];

    public function viagens(): HasMany
    {
        return $this->hasMany(Viagem::class);
    }
}