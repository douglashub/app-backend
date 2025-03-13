<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rota_parada', function (Blueprint $table) {
            if (!Schema::hasColumn('rota_parada', 'tempo_estimado_minutos')) {
                $table->integer('tempo_estimado_minutos')->nullable()->after('ordem');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rota_parada', function (Blueprint $table) {
            $table->dropColumn('tempo_estimado_minutos');
        });
    }
};