<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            if (!Schema::hasColumn('viagens', 'horario_id')) {
                $table->bigInteger('horario_id')->unsigned()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            if (Schema::hasColumn('viagens', 'horario_id')) {
                $table->dropColumn('horario_id');
            }
        });
    }
};