<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            $table->foreignId('horario_id')->constrained('horarios');
        });
    }

    public function down(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('horario_id');
        });
    }
};