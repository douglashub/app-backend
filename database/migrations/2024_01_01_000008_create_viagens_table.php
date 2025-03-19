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
            $table->foreignId('rota_id')->constrained('rotas')->onDelete('cascade');
            $table->foreignId('onibus_id')->constrained('onibus')->onDelete('cascade');
            $table->foreignId('motorista_id')->constrained('motoristas')->onDelete('cascade');
            $table->foreignId('monitor_id')->nullable()->constrained('monitores')->onDelete('set null');
            $table->foreignId('horario_id')->nullable()->constrained('horarios')->onDelete('set null');

            $table->date('data_viagem');

            $table->time('hora_saida_prevista')->nullable();
            $table->time('hora_chegada_prevista')->nullable();
            $table->time('hora_saida_real')->nullable();
            $table->time('hora_chegada_real')->nullable();

            $table->text('observacoes')->nullable();
            $table->enum('status', ['pendente', 'em_andamento', 'concluida', 'cancelada'])->default('pendente');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viagens');
    }
};
