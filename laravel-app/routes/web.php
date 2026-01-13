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
    $sessionId = session()->getId();
    
    // Use Redis list for session history
    $listKey = 'session_history_list:' . $sessionId;
    
    // Create new entry
    $newEntry = json_encode([
        'node' => env('APP_NAME'),
        'timestamp' => now()->toDateTimeString(),
        'client_ip' => request()->ip(),
        'entry_id' => uniqid(),
    ]);
    
    // Push to Redis list (right side)
    Redis::rpush($listKey, $newEntry);
    
    // Set expiration on the list
    Redis::expire($listKey, 600);
    
    // Get all entries from the list
    $allEntries = Redis::lrange($listKey, 0, -1);
    
    // Decode JSON entries
    $sessionData = array_map(function($entry) {
        return json_decode($entry, true);
    }, $allEntries);
    

    
    return view('session-test', [
        'sessionData' => $sessionData,
        'currentNode' => env('APP_NAME'),
        'sessionId' => $sessionId,
        'debugInfo' => [
            'storage_method' => 'Redis List',
            'list_key' => $listKey,
            'total_entries' => count($sessionData),
            'unique_nodes' => count(array_unique(array_column($sessionData, 'node'))),
        ],
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
