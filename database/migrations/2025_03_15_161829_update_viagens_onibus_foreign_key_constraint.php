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
        Schema::table('viagens', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['onibus_id']);
            
            // Add the foreign key constraint with cascade on delete
            $table->foreign('onibus_id')
                  ->references('id')
                  ->on('onibus')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            // Drop the modified foreign key constraint
            $table->dropForeign(['onibus_id']);
            
            // Restore the original foreign key constraint without cascade
            $table->foreign('onibus_id')
                  ->references('id')
                  ->on('onibus');
        });
    }
};