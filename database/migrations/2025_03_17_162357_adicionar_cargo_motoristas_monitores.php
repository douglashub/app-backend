<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Adicionar coluna cargo aos monitores
        Schema::table('monitores', function (Blueprint $table) {
            $table->enum('cargo', ['Efetivo', 'ACT', 'Temporário'])->default('Efetivo')->after('status');
        });

        // Adicionar coluna cargo aos motoristas
        Schema::table('motoristas', function (Blueprint $table) {
            $table->enum('cargo', ['Efetivo', 'ACT', 'Temporário'])->default('Efetivo')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitores', function (Blueprint $table) {
            $table->dropColumn('cargo');
        });

        Schema::table('motoristas', function (Blueprint $table) {
            $table->dropColumn('cargo');
        });
    }
};