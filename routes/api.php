<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Define a new middleware class for status conversion
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
            if ($route === 'presencas') {
                Route::get('/viagem/{viagemId}', [$controllerClass, 'getPresencasByViagem']);
                Route::get('/aluno/{alunoId}', [$controllerClass, 'getPresencasByAluno']);
            }
            if ($route === 'alunos') {
                Route::get('/{id}/presencas', [$controllerClass, 'presencas']);
            }
            if ($route === 'motoristas') {
                Route::get('/{id}/viagens', [$controllerClass, 'viagens']);
            }
            if ($route === 'monitores') {
                Route::get('/{id}/viagens', [$controllerClass, 'viagens']);
            }
            if ($route === 'onibus') {
                Route::get('/{id}/viagens', [$controllerClass, 'viagens']);
            }
            if ($route === 'horarios') {
                Route::get('/{id}/viagens', [$controllerClass, 'viagens']);
            }
        });
    }

    // Rotas para relatórios
    Route::middleware('api')->prefix('relatorios')->group(function () {
        // Opções para configuração de relatórios
        Route::get('/opcoes', 'App\Http\Controllers\ReportController@getReportOptions');

        // Relatórios de Motoristas
        Route::get('/motoristas', 'App\Http\Controllers\ReportController@motoristaReport');
        Route::get('/motoristas/excel', 'App\Http\Controllers\ReportController@motoristaReportExcel');

        // Relatórios de Monitores
        Route::get('/monitores', 'App\Http\Controllers\ReportController@monitorReport');
        Route::get('/monitores/excel', 'App\Http\Controllers\ReportController@monitorReportExcel');

        // Relatórios de Viagens
        Route::get('/viagens', ['as' => 'relatorios.viagens', 'uses' => 'App\Http\Controllers\ReportController@viagemReport'])
            ->middleware('convert.boolean');

        Route::get('/viagens/excel', ['as' => 'relatorios.viagens.excel', 'uses' => 'App\Http\Controllers\ReportController@viagemReportExcel'])
            ->middleware('convert.boolean');
    });
});
