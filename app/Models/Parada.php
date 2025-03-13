<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parada extends Model
{
    protected $fillable = [
        'nome',
        'endereco',
        'ponto_referencia',
        'latitude',
        'longitude',
        'tipo'
    ];
}