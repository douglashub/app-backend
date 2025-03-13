<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rota_subrotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rota_principal_id')->constrained('rotas')->onDelete('cascade');
            $table->foreignId('subrota_id')->constrained('rotas')->onDelete('cascade');
            $table->integer('ordem');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rota_subrotas');
    }
};
