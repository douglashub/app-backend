<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    // Test endpoint
    Route::get('/test', function () {
        return response()->json(['success' => true]);
    });

    // List of services and their respective routes
    $services = [
        'alunos' => App\Services\AlunoService::class,
        'horarios' => App\Services\HorarioService::class,
        'onibus' => App\Services\OnibusService::class,
        'paradas' => App\Services\ParadaService::class,
        'presencas' => App\Services\PresencaService::class,
        'monitores' => App\Services\MonitorService::class,
        'motoristas' => App\Services\MotoristaService::class,
        'rotas' => App\Services\RotaService::class,
        'viagens' => App\Services\ViagemService::class,
    ];

    foreach ($services as $route => $service) {
        $serviceName = class_basename($service);
        $controllerClass = 'App\\Http\\Controllers\\' . str_replace('Service', '', $serviceName) . 'Controller';

        Route::prefix($route)->group(function () use ($controllerClass, $route) {
            Route::get('/', [$controllerClass, 'index']);
            Route::post('/', [$controllerClass, 'store']);
            Route::get('/{id}', [$controllerClass, 'show']);
            Route::put('/{id}', [$controllerClass, 'update']);
            Route::delete('/{id}', [$controllerClass, 'destroy']);

            if ($route === 'rotas') {
                Route::get('/{id}/paradas', [$controllerClass, 'getParadas']);
                Route::get('/{id}/viagens', [$controllerClass, 'getViagens']);
            }
        });
    }
});
