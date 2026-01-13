@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Queue Monitoring Dashboard</h1>
                <p class="text-gray-600">Monitor and manage background job processing</p>
            </div>
            <div class="flex space-x-3">
                <button onclick="runQueueTest()" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold flex items-center">
                    <i class="fas fa-play mr-2"></i> Dispatch Test Job
                </button>
                <button onclick="clearQueue()" 
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-semibold flex items-center">
                    <i class="fas fa-trash mr-2"></i> Clear Queue
                </button>
                <button onclick="retryFailedJobs()" 
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg font-semibold flex items-center">
                    <i class="fas fa-redo mr-2"></i> Retry Failed
                </button>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-500">
            <i class="fas fa-server mr-1"></i> Current Node: <span class="font-semibold">{{ env('APP_NAME') }}</span>
            <span class="mx-2">•</span>
            <i class="fas fa-clock mr-1"></i> Last Updated: <span id="lastUpdated">{{ now()->format('H:i:s') }}</span>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Pending Jobs</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $queueStats['pending_jobs'] }}</p>
                </div>
                <i class="fas fa-clock text-blue-400 text-2xl"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Processed Total</p>
                    <p class="text-3xl font-bold text-green-600">{{ $queueStats['total_processed'] }}</p>
                </div>
                <i class="fas fa-check-circle text-green-400 text-2xl"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Failed Jobs</p>
                    <p class="text-3xl font-bold text-red-600">{{ $queueStats['failed_jobs'] }}</p>
                </div>
                <i class="fas fa-exclamation-circle text-red-400 text-2xl"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Active Workers</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $queueStats['workers'] }}</p>
                </div>
                <i class="fas fa-cogs text-purple-400 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Pending Jobs -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-list-alt mr-3"></i> Pending Jobs ({{ count($parsedJobs) }})
                </h2>
            </div>
            <div class="p-6">
                @if(count($parsedJobs) > 0)
                    <div class="space-y-4">
                        @foreach($parsedJobs as $job)
                            <div class="border border-blue-200 rounded-lg p-4 bg-blue-50">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ $job['displayName'] }}</p>
                                        <p class="text-sm text-gray-600">Job ID: {{ $job['id'] }}</p>
                                        <p class="text-sm text-gray-600">Attempts: {{ $job['attempts'] }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-clock mr-1"></i> Queued
                                        </span>
                                        <p class="text-xs text-gray-500 mt-2">{{ $job['created_at'] }}</p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="flex items-center">
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-500 h-2 rounded-full" style="width: 30%"></div>
                                        </div>
                                        <span class="ml-3 text-sm text-gray-600">Waiting</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">No pending jobs in queue</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recently Processed Jobs -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-history mr-3"></i> Recently Processed
                </h2>
            </div>
            <div class="p-6">
                @if(count($processedJobs) > 0)
                    <div class="space-y-4">
                        @foreach($processedJobs as $job)
                            <div class="border border-green-200 rounded-lg p-4 bg-green-50">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ $job->filename ?? 'Unknown File' }}</p>
                                        <p class="text-sm text-gray-600">File ID: {{ $job->file_id ?? 'N/A' }}</p>
                                        <p class="text-sm text-gray-600">Node: {{ $job->processed_by ?? 'Unknown' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check mr-1"></i> Completed
                                        </span>
                                        <p class="text-xs text-gray-500 mt-2">{{ $job->processed_at ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center text-sm text-gray-600">
                                    <i class="fas fa-id-badge mr-2"></i>
                                    <span>Job ID: {{ $job->job_id ?? 'N/A' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-check-circle text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">No jobs processed yet</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Failed Jobs -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3"></i> Failed Jobs ({{ count($parsedFailedJobs) }})
                </h2>
            </div>
            <div class="p-6">
                @if(count($parsedFailedJobs) > 0)
                    <div class="space-y-4">
                        @foreach($parsedFailedJobs as $job)
                            <div class="border border-red-200 rounded-lg p-4 bg-red-50">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-800">{{ $job['displayName'] }}</p>
                                        <p class="text-sm text-gray-600 mt-2">Error:</p>
                                        <p class="text-sm text-red-600 bg-red-100 p-2 rounded mt-1">{{ Str::limit($job['error'], 100) }}</p>
                                    </div>
                                    <div class="text-right ml-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-times mr-1"></i> Failed
                                        </span>
                                        <p class="text-xs text-gray-500 mt-2">{{ $job['failed_at'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-smile text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">No failed jobs! Great job!</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Queue Actions & Info -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-cogs mr-3"></i> Queue Management
                </h2>
            </div>
            <div class="p-6">
                <div class="space-y-6">
                    <!-- Live Updates -->
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                        <h3 class="font-semibold text-purple-800 mb-2 flex items-center">
                            <i class="fas fa-sync-alt mr-2"></i> Live Updates
                        </h3>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Auto-refresh:</span>
                            <div class="flex items-center">
                                <span class="mr-2" id="refreshStatus">Enabled</span>
                                <label class="switch">
                                    <input type="checkbox" id="autoRefresh" checked>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-3 text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Dashboard updates every 5 seconds
                        </div>
                    </div>

                    <!-- System Info -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800 mb-3">System Information</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Queue Driver:</span>
                                <span class="font-semibold">Redis</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Connection:</span>
                                <span class="font-semibold">laravel-redis:6379</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Default Queue:</span>
                                <span class="font-semibold">default</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Retry Attempts:</span>
                                <span class="font-semibold">3</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="font-semibold text-blue-800 mb-3">Quick Actions</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <button onclick="runQueueTest()" 
                                    class="bg-blue-500 hover:bg-blue-600 text-white py-2 rounded-lg text-sm font-semibold transition">
                                <i class="fas fa-play mr-1"></i> Test Job
                            </button>
                            <button onclick="clearQueue()" 
                                    class="bg-red-500 hover:bg-red-600 text-white py-2 rounded-lg text-sm font-semibold transition">
                                <i class="fas fa-trash mr-1"></i> Clear All
                            </button>
                            <button onclick="retryFailedJobs()" 
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 rounded-lg text-sm font-semibold transition">
                                <i class="fas fa-redo mr-1"></i> Retry Failed
                            </button>
                            <button onclick="refreshDashboard()" 
                                    class="bg-green-500 hover:bg-green-600 text-white py-2 rounded-lg text-sm font-semibold transition">
                                <i class="fas fa-sync mr-1"></i> Refresh Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}
.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
}
.slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
}
input:checked + .slider {
    background-color: #4CAF50;
}
input:checked + .slider:before {
    transform: translateX(26px);
}
.slider.round {
    border-radius: 34px;
}
.slider.round:before {
    border-radius: 50%;
}
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let chart = null;
let autoRefreshInterval = null;

function initializeChart() {
    const ctx = document.getElementById('jobStatusChart').getContext('2d');
    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: Array.from({length: 10}, (_, i) => `${i+1}m ago`),
            datasets: [{
                label: 'Jobs Processed',
                data: Array(10).fill(0),
                borderColor: '#4CAF50',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Jobs'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Time'
                    }
                }
            }
        }
    });
}

function runQueueTest() {
    axios.post('/queue-test')
        .then(response => {
            showNotification('Job dispatched successfully!', 'success');
            refreshDashboard();
        })
        .catch(error => {
            showNotification('Failed to dispatch job', 'error');
        });
}

function clearQueue() {
    if (confirm('Are you sure you want to clear all pending jobs?')) {
        axios.post('/queue/clear')
            .then(response => {
                showNotification('Queue cleared successfully!', 'success');
                refreshDashboard();
            })
            .catch(error => {
                showNotification('Failed to clear queue', 'error');
            });
    }
}

function retryFailedJobs() {
    axios.post('/queue/retry')
        .then(response => {
            showNotification('Failed jobs requeued!', 'success');
            refreshDashboard();
        })
        .catch(error => {
            showNotification('Failed to retry jobs', 'error');
        });
}

function refreshDashboard() {
    location.reload();
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    } text-white`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} mr-3"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
        document.getElementById('lastUpdated').textContent = new Date().toLocaleTimeString();
        // Partial refresh could be implemented here
    }, 5000);
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    initializeChart();
    startAutoRefresh();
    
    document.getElementById('autoRefresh').addEventListener('change', function(e) {
        if (e.target.checked) {
            document.getElementById('refreshStatus').textContent = 'Enabled';
            startAutoRefresh();
        } else {
            document.getElementById('refreshStatus').textContent = 'Disabled';
            stopAutoRefresh();
        }
    });
});
</script>
@endpush