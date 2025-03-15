<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations to fix the motoristas table status field.
     */
    public function up(): void
    {
        // Verificar o driver do banco de dados
        $driverName = DB::connection()->getDriverName();
        
        if ($driverName === 'pgsql') {
            // Para PostgreSQL, uma abordagem diferente é necessária
            
            // 1. Criar uma coluna temporária que não tenha as restrições de enum
            if (!Schema::hasColumn('motoristas', 'status_temp')) {
                Schema::table('motoristas', function (Blueprint $table) {
                    $table->string('status_temp')->nullable()->after('status');
                });
            }
            
            // 2. Inserir valores válidos na coluna temporária
            DB::statement("
                UPDATE motoristas 
                SET status_temp = 
                    CASE 
                        WHEN status::text LIKE 'Ativo' THEN 'Ativo'
                        WHEN status::text LIKE 'Inativo' THEN 'Inativo'
                        WHEN status::text LIKE 'Ferias' THEN 'Ferias'
                        WHEN status::text LIKE 'Licenca' THEN 'Licenca'
                        ELSE 'Ativo'
                    END
            ");
            
            // 3. Verificar se o tipo enum já existe
            $enumExists = DB::select("SELECT 1 FROM pg_type WHERE typname = 'motorista_status_enum'");
            
            // 4. Remover a coluna original
            Schema::table('motoristas', function (Blueprint $table) {
                $table->dropColumn('status');
            });
            
            // 5. Criar ou recrear o tipo enum se necessário
            if (empty($enumExists)) {
                DB::statement("CREATE TYPE motorista_status_enum AS ENUM ('Ativo', 'Inativo', 'Ferias', 'Licenca')");
            }
            
            // 6. Adicionar a nova coluna status como enum
            DB::statement("ALTER TABLE motoristas ADD COLUMN status motorista_status_enum NOT NULL DEFAULT 'Ativo'");
            
            // 7. Copiar valores da coluna temporária para a nova coluna status
            DB::statement("
                UPDATE motoristas 
                SET status = status_temp::motorista_status_enum
            ");
            
            // 8. Remover a coluna temporária
            Schema::table('motoristas', function (Blueprint $table) {
                $table->dropColumn('status_temp');
            });
            
        } elseif ($driverName === 'mysql') {
            // Para MySQL, modificar a coluna existente para ENUM
            DB::statement("ALTER TABLE motoristas MODIFY COLUMN status ENUM('Ativo', 'Inativo', 'Ferias', 'Licenca') NOT NULL DEFAULT 'Ativo'");
            
            // Atualizar valores existentes
            DB::statement("
                UPDATE motoristas 
                SET status = 
                    CASE 
                        WHEN status = 'true' OR status = '1' OR status = 'active' OR status = 'ativo' OR status = 'Active' THEN 'Ativo'
                        WHEN status = 'false' OR status = '0' OR status = 'inactive' OR status = 'inativo' OR status = 'Inactive' THEN 'Inativo'
                        WHEN status = 'vacation' OR status = 'ferias' OR status = 'Vacation' THEN 'Ferias'
                        WHEN status = 'leave' OR status = 'licenca' OR status = 'Leave' THEN 'Licenca'
                        ELSE 'Ativo'
                    END
            ");
        } else {
            // Para SQLite ou outros bancos, converter para string
            Schema::table('motoristas', function (Blueprint $table) {
                $table->string('status')->default('Ativo')->change();
            });
            
            // Atualizar valores existentes
            $motoristas = DB::table('motoristas')->get();
            foreach ($motoristas as $motorista) {
                $newStatus = 'Ativo'; // Valor padrão
                
                $currentStatus = is_string($motorista->status) ? strtolower($motorista->status) : '';
                
                if ($currentStatus === 'false' || $currentStatus === '0' || $currentStatus === 'inactive' || $currentStatus === 'inativo') {
                    $newStatus = 'Inativo';
                } elseif ($currentStatus === 'vacation' || $currentStatus === 'ferias') {
                    $newStatus = 'Ferias';
                } elseif ($currentStatus === 'leave' || $currentStatus === 'licenca') {
                    $newStatus = 'Licenca';
                }
                
                DB::table('motoristas')
                    ->where('id', $motorista->id)
                    ->update(['status' => $newStatus]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não é necessário reverter essas alterações
    }
};