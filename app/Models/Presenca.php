<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presenca extends Model
{
    protected $fillable = [
        'viagem_id',
        'aluno_id',
        'presente',
        'hora_embarque',
        'hora_desembarque',
        'observacoes'
    ];

    protected $casts = [
        'presente' => 'boolean'
    ];

    public function viagem(): BelongsTo
    {
        return $this->belongsTo(Viagem::class);
    }

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class);
    }
}