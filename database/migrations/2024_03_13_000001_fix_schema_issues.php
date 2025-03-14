<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations to fix the schema issues based on controllers and models
     */
    public function up(): void
    {
        // Fix the horarios table schema to match controllers and models
        if (Schema::hasTable('horarios')) {
            Schema::table('horarios', function (Blueprint $table) {
                // Rename hora_saida to hora_inicio if needed
                if (Schema::hasColumn('horarios', 'hora_saida') && !Schema::hasColumn('horarios', 'hora_inicio')) {
                    $table->renameColumn('hora_saida', 'hora_inicio');
                }
                // Rename hora_chegada to hora_fim if needed
                if (Schema::hasColumn('horarios', 'hora_chegada') && !Schema::hasColumn('horarios', 'hora_fim')) {
                    $table->renameColumn('hora_chegada', 'hora_fim');
                }
                // Adicionar hora_inicio se não existir
                if (!Schema::hasColumn('horarios', 'hora_inicio') && !Schema::hasColumn('horarios', 'hora_saida')) {
                    $table->time('hora_inicio');
                }
                // Adicionar hora_fim se não existir
                if (!Schema::hasColumn('horarios', 'hora_fim') && !Schema::hasColumn('horarios', 'hora_chegada')) {
                    $table->time('hora_fim');
                }
                // Rename ativo to status if needed
                if (Schema::hasColumn('horarios', 'ativo') && !Schema::hasColumn('horarios', 'status')) {
                    $table->renameColumn('ativo', 'status');
                }
                // Adicionar status se não existir
                if (!Schema::hasColumn('horarios', 'status') && !Schema::hasColumn('horarios', 'ativo')) {
                    $table->boolean('status')->default(true);
                }
            });
        }

        // Fix the presencas table schema to match the controllers and models
        if (Schema::hasTable('presencas')) {
            Schema::table('presencas', function (Blueprint $table) {
                // Rename hora_embarque to hora_registro if needed
                if (Schema::hasColumn('presencas', 'hora_embarque') && !Schema::hasColumn('presencas', 'hora_registro')) {
                    $table->renameColumn('hora_embarque', 'hora_registro');
                }
                // Make sure hora_desembarque field is renamed or dropped
                if (Schema::hasColumn('presencas', 'hora_desembarque')) {
                    $table->dropColumn('hora_desembarque');
                }
            });
        }

        // Fix the viagens table schema to match controllers and models
        if (Schema::hasTable('viagens')) {
            // For PostgreSQL, we need to use a raw query to convert the status column
            $dbDriver = DB::connection()->getDriverName();
            
            if ($dbDriver === 'pgsql') {
                // For PostgreSQL, use explicit conversion
                if (Schema::hasColumn('viagens', 'status')) {
                    DB::statement('ALTER TABLE viagens ALTER COLUMN status TYPE boolean USING CASE WHEN status = \'1\' OR status = \'true\' OR status = \'ativo\' OR status = \'Ativo\' THEN true ELSE false END');
                    DB::statement('ALTER TABLE viagens ALTER COLUMN status SET DEFAULT true');
                }
            } else {
                // For other databases like MySQL
                Schema::table('viagens', function (Blueprint $table) {
                    if (Schema::hasColumn('viagens', 'status')) {
                        $table->boolean('status')->default(true)->change();
                    }
                });
            }
        }

        // Fix the onibus table schema to match controllers and models
        if (Schema::hasTable('onibus')) {
            // No need to change status in onibus table since it should remain a string
            // But we'll ensure it has a default value
            Schema::table('onibus', function (Blueprint $table) {
                if (Schema::hasColumn('onibus', 'status')) {
                    // The default is already set, so we don't need to change anything here
                }
            });
        }
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        // No easy way to revert these changes, since they are adapting schema to match existing code
    }
};