<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitores', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cpf')->unique();
            $table->string('telefone');
            $table->text('endereco');
            $table->date('data_contratacao');
            $table->enum('status', ['Ativo', 'Ferias', 'Licenca', 'Inativo'])->default('Ativo');
            $table->enum('cargo', ['Efetivo', 'ACT', 'Temporário'])->default('Efetivo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitores');
    }
};
