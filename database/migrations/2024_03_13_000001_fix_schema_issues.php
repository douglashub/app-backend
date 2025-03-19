<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::beginTransaction();

        try {
            // Step 1: Add a new temporary boolean column
            if (!Schema::hasColumn('viagens', 'status_temp')) {
                Schema::table('viagens', function (Blueprint $table) {
                    $table->boolean('status_temp')->default(true)->after('status');
                });

                // Step 2: Convert old values into boolean format
                DB::statement("
                    UPDATE viagens 
                    SET status_temp = 
                        CASE 
                            WHEN status = '1' OR status = 'true' OR LOWER(status) IN ('ativo', 'active') THEN true
                            ELSE false
                        END
                ");
            }

            // Step 3: Drop the old 'status' column
            Schema::table('viagens', function (Blueprint $table) {
                $table->dropColumn('status');
            });

            // Step 4: Rename 'status_temp' to 'status'
            Schema::table('viagens', function (Blueprint $table) {
                $table->renameColumn('status_temp', 'status');
            });

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse Migration: Convert status back to VARCHAR
        DB::beginTransaction();

        try {
            // Step 1: Add a temporary string column
            Schema::table('viagens', function (Blueprint $table) {
                $table->string('status_temp', 20)->default('Ativo')->after('status');
            });

            // Step 2: Convert boolean values back to strings
            DB::statement("
                UPDATE viagens 
                SET status_temp = 
                    CASE 
                        WHEN status = true THEN 'Ativo'
                        ELSE 'Inativo'
                    END
            ");

            // Step 3: Drop the boolean 'status' column
            Schema::table('viagens', function (Blueprint $table) {
                $table->dropColumn('status');
            });

            // Step 4: Rename 'status_temp' back to 'status'
            Schema::table('viagens', function (Blueprint $table) {
                $table->renameColumn('status_temp', 'status');
            });

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
};
