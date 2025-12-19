<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;

// URL::forceScheme('http');
URL::forceRootUrl('http://localhost:8080');


Route::get('/', [ApiController::class, 'index'])->name('home');
Route::post('/upload', [ApiController::class, 'upload'])->name('upload');
Route::get('/stats', [ApiController::class, 'stats'])->name('stats');

Route::get('/queue-dashboard', [ApiController::class, 'queueDashboard'])->name('queue.dashboard');
Route::post('/queue-test', [ApiController::class, 'runQueueTest'])->name('queue.test');
Route::post('/queue/clear', [ApiController::class, 'clearQueue'])->name('queue.clear');
Route::post('/queue/retry', [ApiController::class, 'retryFailedJobs'])->name('queue.retry');


Route::get('/session-test', function () {
    $sessionData = session()->get('test_data', []);
    $sessionData[] = [
        'node' => env('APP_NAME'),
        'timestamp' => now()->toDateTimeString(),
        'client_ip' => request()->ip(),
    ];
    
    session()->put('test_data', $sessionData);
    
    return view('session-test', [
        'sessionData' => $sessionData,
        'currentNode' => env('APP_NAME'),
        'sessionId' => session()->getId(),
    ]);
})->name('session.test');

Route::get('/health', function () {
    $checks = [
        'database' => DB::connection()->getPdo() ? 'OK' : 'FAILED',
        'redis' => Redis::ping() == 'PONG' ? 'OK' : 'FAILED',
        's3' => class_exists('Illuminate\Support\Facades\Storage') ? 'OK' : 'FAILED',
        'queue' => 'OK',
    ];
    
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toDateTimeString(),
        'node' => env('APP_NAME', 'Unknown'),
        'checks' => $checks,
    ]);
});
