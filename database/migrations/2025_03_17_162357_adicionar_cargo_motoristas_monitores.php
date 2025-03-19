<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('monitores', function (Blueprint $table) {
            if (!Schema::hasColumn('monitores', 'cargo')) {
                $table->string('cargo', 255)
                    ->default('Efetivo')
                    ->check("'cargo' IN ('Efetivo', 'ACT', 'Temporário')")
                    ->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('monitores', function (Blueprint $table) {
            if (Schema::hasColumn('monitores', 'cargo')) {
                $table->dropColumn('cargo');
            }
        });
    }
};
