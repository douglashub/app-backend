<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ReportService;
use App\Services\HateoasService;
use App\Services\LoggingService;

class ReportServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ReportService::class, function ($app) {
            return new ReportService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Esta função é chamada após todos os serviços estarem registrados
        // Você pode executar qualquer código de inicialização aqui
    }
}