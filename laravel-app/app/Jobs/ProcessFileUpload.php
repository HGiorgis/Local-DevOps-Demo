<?php

namespace App\Jobs;

use App\Models\UploadedFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessFileUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $maxExceptions = 3;

    public function __construct(
        public UploadedFile $file,
        public string $processingNode
    ) {}

    public function handle(): void
    {
        // Mark job as started
        Redis::setex("job:{$this->job->getJobId()}:status", 300, 'processing');
        
        Log::info('Starting file processing job', [
            'job_id' => $this->job->getJobId(),
            'file_id' => $this->file->id,
            'filename' => $this->file->filename,
            'node' => $this->processingNode,
        ]);
        
        // Simulate file processing (resize, generate thumbnails, etc.)
        $steps = ['Validating', 'Processing', 'Optimizing', 'Finalizing'];
        foreach ($steps as $step) {
            sleep(1); // Simulate work
            Redis::setex("job:{$this->job->getJobId()}:step", 300, $step);
            Log::info("File processing step: {$step}", [
                'job_id' => $this->job->getJobId(),
                'file_id' => $this->file->id,
            ]);
        }

        // Update file record
        $this->file->update([
            'processed_by_worker' => 1,
            'processed_at' => now(),
        ]);

        // Log to Redis for real-time updates
        $processedData = [
            'file_id' => $this->file->id,
            'filename' => $this->file->filename,
            'processed_at' => now()->toDateTimeString(),
            'processed_by' => $this->processingNode,
            'job_id' => $this->job->getJobId(),
            'size' => $this->file->size,
        ];
        
        Redis::setex('last_processed_file:' . $this->file->id, 3600, json_encode($processedData));
        Redis::set('last_processed_file', json_encode($processedData));
        
        // Track successful processing
        Redis::incr('jobs:processed:total');
        Redis::incr('files:processed:total');
        Redis::rpush('jobs:recently_processed', json_encode($processedData));
        Redis::ltrim('jobs:recently_processed', 0, 9);
        
        // Mark job as completed
        Redis::setex("job:{$this->job->getJobId()}:status", 300, 'completed');
        Redis::setex("job:{$this->job->getJobId()}:completed_at", 300, now()->toDateTimeString());

        Log::info('File processed successfully', [
            'job_id' => $this->job->getJobId(),
            'file_id' => $this->file->id,
            'processed_by' => $this->processingNode,
            'processing_time' => microtime(true) - LARAVEL_START,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Redis::setex("job:{$this->job->getJobId()}:status", 3600, 'failed');
        Redis::setex("job:{$this->job->getJobId()}:error", 3600, $exception->getMessage());
        
        Log::error('File processing job failed', [
            'job_id' => $this->job->getJobId(),
            'file_id' => $this->file->id,
            'error' => $exception->getMessage(),
            'stack_trace' => $exception->getTraceAsString(),
        ]);
        
        // Track failures
        Redis::incr('jobs:failed:total');
    }
    
    public function tags(): array
    {
        return ['file-processing', 'upload', 'file-' . $this->file->id];
    }
}