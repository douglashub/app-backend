<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            // Adiciona a coluna apenas se ela não existir
            if (!Schema::hasColumn('viagens', 'horario_id')) {
                $table->foreignId('horario_id')->nullable()->constrained('horarios')->onDelete('set null')->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            if (Schema::hasColumn('viagens', 'horario_id')) {
                $table->dropForeign(['horario_id']);
                $table->dropColumn('horario_id');
            }
        });
    }
};
