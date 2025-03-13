<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rotas', function (Blueprint $table) {
            $table->id(); // Standard Laravel auto-increment id
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->string('tipo', 50);
            $table->decimal('distancia_km', 8, 2);
            $table->integer('tempo_estimado_minutos');
            $table->string('status', 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rotas');
    }
};
