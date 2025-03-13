<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            $table->time('hora_chegada_prevista')->nullable()->after('data_viagem');
            $table->time('hora_saida_prevista')->nullable()->after('hora_chegada_prevista');
        });
    }

    public function down(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            $table->dropColumn(['hora_chegada_prevista', 'hora_saida_prevista']);
        });
    }
};