<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rota_id')->constrained()->onDelete('cascade');
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->json('dias_semana');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
