<?php

namespace Database\Seeders;

use App\Models\UploadedFile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Redis;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Clear Redis (optional, be careful in production!)
        // Redis::flushall();

        // Reset counters
        Redis::set('total_uploads', 0);

        // Create sample files if none exist
        if (UploadedFile::count() == 0) {
            UploadedFile::create([
                'filename' => 'sample1.txt',
                'original_name' => 'sample1.txt',
                'path' => 'uploads/samples/sample1.txt',
                'size' => '1.2 KB',
                'mime_type' => 'text/plain',
                'uploaded_by_node' => 'Seeder',
                'processed_by_worker' => 1,
            ]);

            UploadedFile::create([
                'filename' => 'sample2.jpg',
                'original_name' => 'sample2.jpg',
                'path' => 'uploads/samples/sample2.jpg',
                'size' => '450 KB',
                'mime_type' => 'image/jpeg',
                'uploaded_by_node' => 'Seeder',
                'processed_by_worker' => 0,
            ]);

            UploadedFile::create([
                'filename' => 'sample3.pdf',
                'original_name' => 'sample3.pdf',
                'path' => 'uploads/samples/sample3.pdf',
                'size' => '2.5 MB',
                'mime_type' => 'application/pdf',
                'uploaded_by_node' => 'Seeder',
                'processed_by_worker' => 1,
            ]);

            $this->command->info('Sample files created successfully!');
        }

        $this->command->info('Database seeded!');
    }
}