<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paradas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('endereco');
            $table->string('ponto_referencia')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('tipo', ['Inicio', 'Intermediaria', 'Final']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paradas');
    }
};
