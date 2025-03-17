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
        'status',
        'cargo'
    ];

    protected $casts = [
        'data_contratacao' => 'date'
    ];

    public function viagens(): HasMany
    {
        return $this->hasMany(Viagem::class);
    }

    /**
     * Set the monitor's cargo.
     * This mutator ensures the cargo is always in the correct format
     *
     * @param  mixed  $value
     * @return void
     */
    public function setCargoAttribute($value)
    {
        $allowedCargos = ['Efetivo', 'ACT', 'Temporário'];
        
        if (is_string($value) && in_array($value, $allowedCargos)) {
            $this->attributes['cargo'] = $value;
        } else {
            // Default to Efetivo if not recognized
            $this->attributes['cargo'] = 'Efetivo';
        }
    }
}