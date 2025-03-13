<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('viagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rota_id')->constrained('rotas');
            $table->foreignId('onibus_id')->constrained('onibus');
            $table->foreignId('motorista_id')->constrained('motoristas');
            $table->foreignId('monitor_id')->nullable()->constrained('monitores');
            $table->date('data_viagem');
            $table->time('hora_saida_real')->nullable();
            $table->time('hora_chegada_real')->nullable();
            $table->text('observacoes')->nullable();
            $table->string('status', 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viagens');
    }
};
