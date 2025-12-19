<?php

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Laravel Docker Cluster API",
 *      description="API documentation for Redis stats, session tests, queue management and uploads",
 *      @OA\Contact(
 *          email="support@example.com"
 *      )
 * )
 *
 * @OA\Server(
 *      url="http://localhost:8080",
 *      description="Local development server"
 * )
 */

namespace App\Http\Controllers;

use App\Jobs\ProcessFileUpload;
use App\Models\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Stats",
 *     description="Redis and system statistics"
 * )
 * 
 * @OA\Tag(
 *     name="Session",
 *     description="Session testing endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Queue",
 *     description="Queue management endpoints"
 * )
 */

class ApiController extends Controller
{
    
    public function index()
    {
        $files = UploadedFile::latest()->take(10)->get();
        $stats = [
            'total_files' => UploadedFile::count(),
            'processed_files' => UploadedFile::where('processed_by_worker', 1)->count(),
            'pending_files' => UploadedFile::where('processed_by_worker', 0)->count(),
            'redis_connected' => Redis::ping() == 'PONG' ? 'Yes' : 'No',
            'current_node' => env('APP_NAME', 'Unknown'),
        ];

        return view('files.index', compact('files', 'stats'));
    }
    /**
     * @OA\Post(
     *     path="/upload",
     *     summary="Upload a file to S3 and dispatch processing",
     *     tags={"Files"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="File to upload"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:100240', // ~100MB
        ], [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The selected item must be a valid file.',
            'file.max' => 'The file is too large. Maximum allowed size is 100 MB.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                            ->withErrors($validator)
                            ->withInput();
        }
        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = 'uploads/' . date('Y/m/d') . '/' . $filename;

        // Store in S3/MinIO
        Storage::disk('s3')->put($path, file_get_contents($file));

        // Save to database
        $uploadedFile = UploadedFile::create([
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'size' => $this->formatBytes($file->getSize()),
            'mime_type' => $file->getMimeType(),
            'uploaded_by_node' => env('APP_NAME', 'Unknown'),
        ]);

        // Dispatch job to process the file
        ProcessFileUpload::dispatch($uploadedFile, env('APP_NAME', 'QueueWorker'));

        // Store in Redis for real-time stats
        Redis::incr('total_uploads');
        Redis::rpush('recent_uploads', json_encode([
            'id' => $uploadedFile->id,
            'filename' => $uploadedFile->original_name,
            'uploaded_at' => now()->toDateTimeString(),
            'node' => env('APP_NAME'),
        ]));
        Redis::ltrim('recent_uploads', 0, 9); // Keep only last 10

        return redirect()->back()->with('success', 'File uploaded successfully! Processing in background...');
    }

    /**
     * @OA\Get(
     *     path="/stats",
     *     summary="Get Redis and upload statistics",
     *     tags={"Stats"},
     *     @OA\Response(
     *         response=200,
     *         description="Statistics data",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_uploads", type="integer", example=25),
     *             @OA\Property(property="redis_memory", type="string", example="1.25MB"),
     *             @OA\Property(property="connected_clients", type="integer", example=3),
     *             @OA\Property(property="queue_size", type="integer", example=5),
     *             @OA\Property(property="last_processed", type="object", nullable=true),
     *             @OA\Property(property="uptime", type="integer", example=3600),
     *             @OA\Property(property="node", type="string", example="Laravel_Node_1"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function stats()
    {
        $redisInfo = Redis::info();
        
        $stats = [
            'total_uploads' => Redis::get('total_uploads') ?: 0,
            'redis_memory' => $redisInfo['used_memory_human'] ?? 'N/A',
            'connected_clients' => $redisInfo['connected_clients'] ?? 'N/A',
            'queue_size' => Redis::llen('queues:default') ?: 0,
            'last_processed' => Redis::get('last_processed_file') ? json_decode(Redis::get('last_processed_file'), true) : null,
            'uptime' => $redisInfo['uptime_in_seconds'] ?? 0,
            'node' => env('APP_NAME', 'Unknown'),
            'timestamp' => now()->toDateTimeString(),
        ];

        return response()->json($stats);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    /**
     * @OA\Get(
     *     path="/queue-dashboard",
     *     summary="Get queue and job stats",
     *     tags={"Queue"},
     *     @OA\Response(
     *         response=200,
     *         description="Queue dashboard data",
     *         @OA\JsonContent(
     *             @OA\Property(property="pending_jobs", type="integer"),
     *             @OA\Property(property="failed_jobs", type="integer"),
     *             @OA\Property(property="total_processed", type="integer"),
     *             @OA\Property(property="queue_size", type="string"),
     *             @OA\Property(property="workers", type="integer")
     *         )
     *     )
     * )
     */
    public function queueDashboard()
    {
        $jobs = Redis::lrange('queues:default', 0, 9);
        $failedJobs = Redis::lrange('queues:default:failed', 0, 9);
        $info = Redis::command('INFO', ['memory']);

        $usedMemory = isset($info['used_memory']) ? (int)$info['used_memory'] : 0;

        $parsedJobs = [];
        foreach ($jobs as $job) {
            $data = json_decode($job, true);
            $parsedJobs[] = [
                'id' => $data['id'] ?? 'N/A',
                'displayName' => $data['displayName'] ?? 'Unknown Job',
                'attempts' => $data['attempts'] ?? 0,
                'created_at' => isset($data['pushedAt']) ? date('Y-m-d H:i:s', $data['pushedAt']) : 'N/A',
            ];
        }
        
        $parsedFailedJobs = [];
        foreach ($failedJobs as $job) {
            $data = json_decode($job, true);
            $parsedFailedJobs[] = [
                'id' => $data['id'] ?? 'N/A',
                'displayName' => $data['displayName'] ?? 'Unknown Job',
                'error' => $data['error'] ?? 'No error message',
                'failed_at' => isset($data['failedAt']) ? date('Y-m-d H:i:s', $data['failedAt']) : 'N/A',
            ];
        }
        
        $queueStats = [
            'pending_jobs' => Redis::llen('queues:default'),
            'failed_jobs' => Redis::llen('queues:default:failed'),
            'total_processed' => Redis::get('jobs:processed:total') ?: 0,
            'queue_size' => $this->formatBytes($usedMemory ?: 0),
            'workers' => $this->getWorkerCount(),
        ];
        
        $recentProcessed = Redis::lrange('jobs:recently_processed', 0, 9);
        $processedJobs = array_map('json_decode', $recentProcessed);
        
        return view('queue.dashboard', compact(
            'parsedJobs', 
            'parsedFailedJobs', 
            'queueStats',
            'processedJobs'
        ));
    }
        /**
     * @OA\Post(
     *     path="/queue-test",
     *     summary="Dispatch a queued job for latest uploaded file",
     *     tags={"Queue"},
     *     @OA\Response(
     *         response=200,
     *         description="Job dispatched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Job dispatched successfully"),
     *             @OA\Property(property="node", type="string", example="Laravel_Node_1"),
     *             @OA\Property(property="file", type="string", example="example.pdf"),
     *             @OA\Property(property="job_id", type="string", example="123_1700000000"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function runQueueTest()
    {
        $file = UploadedFile::latest()->first();
        
        if ($file) {
            ProcessFileUpload::dispatch($file, env('APP_NAME', 'TestNode'));
            
            // Track job dispatch
            Redis::incr('jobs:dispatched:total');
            Redis::rpush('jobs:recently_dispatched', json_encode([
                'file_id' => $file->id,
                'filename' => $file->filename,
                'dispatched_by' => env('APP_NAME'),
                'dispatched_at' => now()->toDateTimeString(),
            ]));
            Redis::ltrim('jobs:recently_dispatched', 0, 9);
            
            return response()->json([
                'success' => true,
                'message' => 'Job dispatched successfully',
                'node' => env('APP_NAME'),
                'file' => $file->filename,
                'job_id' => $file->id . '_' . time(),
                'timestamp' => now()->toDateTimeString(),
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'No files available to process',
            'node' => env('APP_NAME'),
        ], 404);
    }
    
    /**
     * @OA\Post(
     *     path="/queue/clear",
     *     summary="Clear all pending jobs in queue",
     *     tags={"Queue"},
     *     @OA\Response(
     *         response=200,
     *         description="Queue cleared",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Queue cleared successfully"),
     *             @OA\Property(property="jobs_cleared", type="integer", example=5),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function clearQueue()
    {
        $count = Redis::llen('queues:default');
        Redis::del('queues:default');
        
        return response()->json([
            'success' => true,
            'message' => 'Queue cleared successfully',
            'jobs_cleared' => $count,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
        /**
     * @OA\Post(
     *     path="/queue/retry",
     *     summary="Retry failed jobs in queue",
     *     tags={"Queue"},
     *     @OA\Response(
     *         response=200,
     *         description="Failed jobs requeued",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Failed jobs requeued"),
     *             @OA\Property(property="jobs_requeued", type="integer", example=3),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function retryFailedJobs()
    {
        $failedJobs = Redis::lrange('queues:default:failed', 0, -1);
        $count = count($failedJobs);
        
        foreach ($failedJobs as $job) {
            $data = json_decode($job, true);
            Redis::rpush('queues:default', json_encode($data));
        }
        
        Redis::del('queues:default:failed');
        
        return response()->json([
            'success' => true,
            'message' => 'Failed jobs requeued',
            'jobs_requeued' => $count,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
    
    private function getWorkerCount()
    {
        // Count queue worker containers
        exec('docker ps --filter "name=laravel-queue" --format "{{.Names}}"', $output);
        return count($output);
    }

}