@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column: Upload Form & Stats -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Upload Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="flex items-center mb-4">
                <i class="fas fa-cloud-upload-alt text-blue-500 text-2xl mr-3"></i>
                <h2 class="text-2xl font-bold text-gray-800">Upload File to S3 Storage</h2>
            </div>
            <form action="{{ route('upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="border-2 border-dashed border-blue-300 rounded-xl p-8 text-center bg-blue-50 hover:bg-blue-100 transition cursor-pointer" onclick="document.getElementById('file').click()">
                    <input type="file" name="file" id="file" class="hidden" onchange="handleFileSelect(this)">
                    <i class="fas fa-file-upload text-4xl text-blue-400 mb-4"></i>
                    <p class="text-lg text-gray-700 font-medium">Click to select a file</p>
                    <p class="text-sm text-gray-500 mt-2">Max size: 10MB • All file types supported</p>
                    <p id="fileName" class="text-blue-600 font-semibold mt-3"></p>
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 rounded-xl font-semibold hover:opacity-90 transition flex items-center justify-center">
                    <i class="fas fa-upload mr-2"></i>
                    Upload to S3 & Process in Queue
                </button>
            </form>

            @if(session('success'))
                <div class="mt-4 p-4 bg-green-100 text-green-700 rounded-lg border border-green-200">
                    <i class="fas fa-check-circle mr-2"></i>
                    {{ session('success') }}
                </div>
            @endif
        </div>

        <!-- System Stats Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="flex items-center mb-4">
                <i class="fas fa-chart-bar text-purple-500 text-2xl mr-3"></i>
                <h2 class="text-2xl font-bold text-gray-800">System Statistics</h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                    <p class="text-sm text-blue-600 font-medium"><i class="fas fa-database mr-1"></i> Total Files</p>
                    <p class="text-3xl font-bold text-blue-700" id="totalFiles">{{ $stats['total_files'] }}</p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                    <p class="text-sm text-green-600 font-medium"><i class="fas fa-check-circle mr-1"></i> Processed</p>
                    <p class="text-3xl font-bold text-green-700" id="processedFiles">{{ $stats['processed_files'] }}</p>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100">
                    <p class="text-sm text-yellow-600 font-medium"><i class="fas fa-clock mr-1"></i> Pending</p>
                    <p class="text-3xl font-bold text-yellow-700" id="pendingFiles">{{ $stats['pending_files'] }}</p>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
                    <p class="text-sm text-purple-600 font-medium"><i class="fas fa-bolt mr-1"></i> Redis</p>
                    <p class="text-3xl font-bold text-purple-700">{{ $stats['redis_connected'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Cluster Info -->
    <div class="space-y-6">
        <!-- Cluster Info Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="flex items-center mb-4">
                <i class="fas fa-network-wired text-green-500 text-2xl mr-3"></i>
                <h2 class="text-2xl font-bold text-gray-800">Cluster Information</h2>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-600"><i class="fas fa-server mr-2"></i> Current Node:</span>
                    <span class="font-semibold text-blue-600 bg-blue-100 px-3 py-1 rounded-full">{{ $stats['current_node'] }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-600"><i class="fas fa-id-card mr-2"></i> Session ID:</span>
                    <span class="font-mono text-sm bg-gray-800 text-white px-2 py-1 rounded">{{ substr(session()->getId(), 0, 12) }}...</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-600"><i class="fas fa-clock mr-2"></i> Server Time:</span>
                    <span class="font-semibold">{{ now()->format('H:i:s') }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-600"><i class="fas fa-balance-scale mr-2"></i> Load Balancer:</span>
                    <span class="font-semibold text-green-600"><i class="fas fa-check-circle mr-1"></i> Active</span>
                </div>
            </div>

            <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <h3 class="font-semibold mb-2 text-blue-800"><i class="fas fa-vial mr-2"></i>Test Session Sharing:</h3>
                <a href="{{ route('session.test') }}" class="inline-block bg-blue-100 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-200 transition font-medium">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh to switch nodes
                </a>
                <p class="text-sm text-gray-600 mt-2">
                    Session data persists across different Laravel nodes via Redis
                </p>
            </div>
        </div>

        <!-- Recent Files Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <i class="fas fa-history text-orange-500 text-2xl mr-3"></i>
                    <h2 class="text-2xl font-bold text-gray-800">Recent Files</h2>
                </div>
                <span class="bg-gray-100 text-gray-600 text-sm px-3 py-1 rounded-full">{{ count($files) }} files</span>
            </div>
            <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                @forelse($files as $file)
                    <div class="border-l-4 {{ $file->processed_by_worker ? 'border-green-500' : 'border-yellow-500' }} pl-4 py-3 bg-gray-50 rounded-r-lg">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                @php
                                    $icon = 'file';
                                    if (str_contains($file->mime_type, 'image')) $icon = 'file-image';
                                    elseif (str_contains($file->mime_type, 'pdf')) $icon = 'file-pdf';
                                    elseif (str_contains($file->mime_type, 'text')) $icon = 'file-alt';
                                @endphp
                                <i class="fas fa-{{ $icon }} text-gray-500 mr-2"></i>
                                <span class="font-medium truncate max-w-xs">{{ $file->original_name }}</span>
                            </div>
                            <span class="text-sm {{ $file->processed_by_worker ? 'text-green-600 bg-green-100' : 'text-yellow-600 bg-yellow-100' }} px-2 py-1 rounded-full">
                                {{ $file->processed_by_worker ? '✓ Processed' : '⏳ Queued' }}
                            </span>
                        </div>
                        <div class="text-sm text-gray-500 mt-2 flex justify-between">
                            <span><i class="fas fa-hdd mr-1"></i> {{ $file->size }}</span>
                            <span><i class="fas fa-clock mr-1"></i> {{ $file->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="text-xs text-gray-400 mt-1">
                            <i class="fas fa-server mr-1"></i> Node: {{ $file->uploaded_by_node }}
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-folder-open text-4xl mb-3"></i>
                        <p>No files uploaded yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Live Stats Card -->
<div class="mt-8 bg-white rounded-xl shadow-lg p-6 border border-gray-200">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
            <i class="fas fa-heartbeat text-red-500 text-2xl mr-3"></i>
            <h2 class="text-2xl font-bold text-gray-800">Live Statistics</h2>
        </div>
        <button onclick="updateStats()" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition flex items-center">
            <i class="fas fa-redo mr-2"></i> Refresh Stats
        </button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4" id="liveStats">
        <div class="text-center py-8">
            <i class="fas fa-spinner fa-spin text-2xl text-blue-500"></i>
            <p class="mt-2 text-gray-500">Loading live stats...</p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        const fileName = input.files[0].name;
        const fileSize = (input.files[0].size / 1024 / 1024).toFixed(2);
        document.getElementById('fileName').innerHTML = 
            `<i class="fas fa-file mr-1"></i> ${fileName} <span class="text-gray-500">(${fileSize} MB)</span>`;
    }
}

function updateStats() {
    axios.get('/stats')
        .then(response => {
            const stats = response.data;
            
            // Update main stats
            document.getElementById('totalFiles').textContent = {{ $stats['total_files'] }};
            document.getElementById('processedFiles').textContent = {{ $stats['processed_files'] }};
            document.getElementById('pendingFiles').textContent = {{ $stats['pending_files'] }};
            
            // Update live stats
            const liveStatsDiv = document.getElementById('liveStats');
            liveStatsDiv.innerHTML = `
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-5 rounded-xl border border-blue-200">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-upload text-blue-600 text-xl mr-2"></i>
                        <p class="text-sm font-medium text-blue-700">Total Uploads (Redis)</p>
                    </div>
                    <p class="text-3xl font-bold text-blue-800">${stats.total_uploads}</p>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-5 rounded-xl border border-purple-200">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-memory text-purple-600 text-xl mr-2"></i>
                        <p class="text-sm font-medium text-purple-700">Redis Memory</p>
                    </div>
                    <p class="text-3xl font-bold text-purple-800">${stats.redis_memory}</p>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-green-100 p-5 rounded-xl border border-green-200">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-tasks text-green-600 text-xl mr-2"></i>
                        <p class="text-sm font-medium text-green-700">Queue Size</p>
                    </div>
                    <p class="text-3xl font-bold text-green-800">${stats.queue_size}</p>
                </div>
                <div class="bg-gradient-to-br from-red-50 to-red-100 p-5 rounded-xl border border-red-200">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-users text-red-600 text-xl mr-2"></i>
                        <p class="text-sm font-medium text-red-700">Connected Clients</p>
                    </div>
                    <p class="text-3xl font-bold text-red-800">${stats.connected_clients}</p>
                </div>
            `;
            
        })
        .catch(error => {
            console.error('Error fetching stats:', error);
        });
}

// Update stats every 5 seconds
setInterval(updateStats, 5000);

// Initial load
document.addEventListener('DOMContentLoaded', function() {
    updateStats();
});
</script>
@endpush