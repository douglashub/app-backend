<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            // Remove a antiga chave estrangeira
            $table->dropForeign(['onibus_id']);
            
            // Adiciona a nova chave estrangeira com `CASCADE ON DELETE`
            $table->foreign('onibus_id')
                  ->references('id')
                  ->on('onibus')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            // Remove a nova chave estrangeira
            $table->dropForeign(['onibus_id']);
            
            // Restaura a antiga chave estrangeira (sem cascade)
            $table->foreign('onibus_id')
                  ->references('id')
                  ->on('onibus');
        });
    }
};
