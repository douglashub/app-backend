<?php
// routes/web.php

use Illuminate\Support\Facades\Route;

// Rota básica
Route::get('/', function () {
    return view('welcome');
});

// Rota de teste JSON
Route::get('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API running successfully',
        'server_time' => now()->toDateTimeString(),
        'app_url' => config('app.url'),
        'debug' => config('app.debug')
    ]);
});

// Rota de ping para health checks
Route::get('/api/ping', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'pong',
        'timestamp' => now()->timestamp
    ]);
});

// Rota para exibir informações do ambiente
Route::get('/api/env', function () {
    // Apenas em modo debug
    if (!config('app.debug')) {
        return response()->json(['message' => 'Debug mode disabled'], 403);
    }
    
    return response()->json([
        'app_url' => config('app.url'),
        'app_env' => config('app.env'),
        'app_debug' => config('app.debug'),
        'php_version' => PHP_VERSION,
        'session_domain' => config('session.domain'),
        'server_info' => [
            'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
            'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
            'port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
        ]
    ]);
});

// Adicione suas outras rotas aqui...