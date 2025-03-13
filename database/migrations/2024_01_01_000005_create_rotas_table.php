<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rotas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->string('tipo', 50)->default('Escolar'); // Add a default value
            $table->decimal('distancia_km', 8, 2)->nullable(); // Make nullable
            $table->integer('tempo_estimado_minutos')->nullable(); // Make nullable
            $table->string('origem')->nullable(); // Add this field
            $table->string('destino')->nullable(); // Add this field
            $table->time('horario_inicio')->nullable(); // Add this field
            $table->time('horario_fim')->nullable(); // Add this field
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('rotas');
    }
};
