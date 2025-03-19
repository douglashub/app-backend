<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rota_parada', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rota_id')->constrained('rotas')->onDelete('cascade');
            $table->foreignId('parada_id')->constrained('paradas')->onDelete('cascade');
            $table->integer('ordem');
            $table->integer('tempo_estimado_minutos')->nullable();
            $table->time('horario_estimado')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rota_parada');
    }
};
