<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Motorista extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nome',
        'cpf',
        'cnh',
        'categoria_cnh',
        'validade_cnh',
        'telefone',
        'endereco',
        'data_contratacao',
        'status',
        'cargo'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'validade_cnh' => 'date',
        'data_contratacao' => 'date'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Define the relationship between Motorista and Viagem
     *
     * @return HasMany
     */
    public function viagens(): HasMany
    {
        return $this->hasMany(Viagem::class);
    }

    /**
     * Set the motorista's status.
     * This mutator ensures the status is always in the correct format
     *
     * @param  mixed  $value
     * @return void
     */
    public function setStatusAttribute($value)
    {
        $allowedStatuses = ['Ativo', 'Inativo', 'Ferias', 'Licenca'];
        
        // Handle boolean values
        if (is_bool($value)) {
            $this->attributes['status'] = $value ? 'Ativo' : 'Inativo';
            return;
        }
        
        // Handle numeric values
        if (is_numeric($value)) {
            $this->attributes['status'] = $value ? 'Ativo' : 'Inativo';
            return;
        }
        
        // Handle string values with normalization
        if (is_string($value)) {
            $valueLower = strtolower($value);
            
            if (in_array($valueLower, ['active', 'ativo', '1', 'true'])) {
                $this->attributes['status'] = 'Ativo';
            } elseif (in_array($valueLower, ['inactive', 'inativo', '0', 'false'])) {
                $this->attributes['status'] = 'Inativo';
            } elseif (in_array($valueLower, ['vacation', 'ferias', 'férias'])) {
                $this->attributes['status'] = 'Ferias';
            } elseif (in_array($valueLower, ['leave', 'licenca', 'licença'])) {
                $this->attributes['status'] = 'Licenca';
            } elseif (in_array($value, $allowedStatuses)) {
                $this->attributes['status'] = $value;
            } else {
                // Default to Ativo if not recognized
                $this->attributes['status'] = 'Ativo';
            }
            return;
        }
        
        // Default value for any other type
        $this->attributes['status'] = 'Ativo';
    }
    
    /**
     * Set the motorista's cargo.
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