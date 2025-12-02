<?php

use App\Http\Controllers\Api\MonitoringController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Monitoring API Routes
|--------------------------------------------------------------------------
|
| These routes provide access to system monitoring data and analytics.
| They are protected by authentication middleware to ensure security.
|
*/

Route::middleware(['auth:sanctum'])->prefix('monitoring')->name('api.monitoring.')->group(function () {
    // System health and status
    Route::get('/health', [MonitoringController::class, 'health'])->name('health');
    Route::get('/status', [MonitoringController::class, 'status'])->name('status');
    
    // Performance metrics
    Route::get('/metrics', [MonitoringController::class, 'metrics'])->name('metrics');
    Route::get('/memory', [MonitoringController::class, 'memory'])->name('memory');
    Route::get('/trends', [MonitoringController::class, 'trends'])->name('trends');
    
    // Database analytics
    Route::get('/database', [MonitoringController::class, 'database'])->name('database');
    Route::post('/database/start-monitoring', [MonitoringController::class, 'startQueryMonitoring'])->name('database.start');
    Route::post('/database/stop-monitoring', [MonitoringController::class, 'stopQueryMonitoring'])->name('database.stop');
    
    // User activity analytics
    Route::get('/activity', [MonitoringController::class, 'activity'])->name('activity');
    
    // Error monitoring
    Route::get('/errors', [MonitoringController::class, 'errors'])->name('errors');
    
    // Dashboard and alerts
    Route::get('/dashboard', [MonitoringController::class, 'dashboard'])->name('dashboard');
    Route::get('/alerts', [MonitoringController::class, 'alerts'])->name('alerts');
    
    // Export and utilities
    Route::get('/export', [MonitoringController::class, 'export'])->name('export');
    Route::post('/clear-cache', [MonitoringController::class, 'clearCache'])->name('clear-cache');
});

/*
|--------------------------------------------------------------------------
| Public Monitoring Routes
|--------------------------------------------------------------------------
|
| These routes provide basic health check functionality for external
| monitoring services without requiring authentication.
|
*/

Route::prefix('monitoring')->name('api.monitoring.public.')->group(function () {
    // Basic health check for external monitoring
    Route::get('/ping', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'version' => app()->version(),
        ]);
    })->name('ping');
    
    // Basic system status for load balancers
    Route::get('/health-check', function () {
        try {
            // Basic database connectivity check
            \Illuminate\Support\Facades\DB::select('SELECT 1');
            
            return response()->json([
                'status' => 'healthy',
                'database' => 'connected',
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'database' => 'disconnected',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 503);
        }
    })->name('health-check');
});