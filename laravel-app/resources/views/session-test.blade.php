@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl shadow-lg p-8 text-white mb-8">
        <div class="flex items-center mb-4">
            <i class="fas fa-exchange-alt text-4xl mr-4"></i>
            <div>
                <h1 class="text-3xl font-bold">Session Sharing Test</h1>
                <p class="text-blue-100">Testing Redis-based session persistence across multiple nodes</p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
            <div class="bg-blue-400/30 p-4 rounded-lg">
                <i class="fas fa-sync-alt text-xl mb-2"></i>
                <p class="font-semibold">Round Robin Load Balancing</p>
            </div>
            <div class="bg-purple-400/30 p-4 rounded-lg">
                <i class="fas fa-database text-xl mb-2"></i>
                <p class="font-semibold">Redis Session Storage</p>
            </div>
            <div class="bg-green-400/30 p-4 rounded-lg">
                <i class="fas fa-check-circle text-xl mb-2"></i>
                <p class="font-semibold">Session Persistence</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Current Session Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="flex items-center mb-6">
                <i class="fas fa-user-circle text-blue-500 text-2xl mr-3"></i>
                <h2 class="text-2xl font-bold text-gray-800">Current Session</h2>
            </div>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center p-4 bg-blue-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-server text-blue-600 mr-3"></i>
                        <div>
                            <p class="text-sm text-gray-600">Current Node</p>
                            <p class="text-lg font-bold text-blue-700">{{ env('APP_NAME') }}</p>
                        </div>
                    </div>
                    <span class="bg-blue-100 text-blue-800 text-sm font-semibold px-3 py-1 rounded-full">
                        Active
                    </span>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600 mb-2"><i class="fas fa-fingerprint mr-2"></i>Session ID</p>
                    <code class="bg-gray-800 text-white p-3 rounded-lg block text-sm overflow-x-auto">
                        {{ session()->getId() }}
                    </code>
                </div>

                <div class="p-4 bg-green-50 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Requests in Session</p>
                            <p class="text-2xl font-bold text-green-700">{{ count($sessionData) }}</p>
                        </div>
                        <i class="fas fa-chart-line text-green-500 text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

    <!-- Session History Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center">
                <i class="fas fa-history text-purple-500 text-2xl mr-3"></i>
                <h2 class="text-2xl font-bold text-gray-800">Session History</h2>
            </div>
            <span class="bg-purple-100 text-purple-800 text-sm font-semibold px-3 py-1 rounded-full">
                {{ count($sessionData) }} requests
            </span>
        </div>
        
        <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
            @php
                // Reverse the array so newest is first
                $reversedData = array_reverse($sessionData);
            @endphp
            
            @foreach($reversedData as $index => $item)
                @php
                    // Calculate the original position from the end
                    $originalIndex = count($sessionData) - $index;
                @endphp
                <div class="border-l-4 {{ $item['node'] == 'Laravel_Node_1' ? 'border-blue-500' : 'border-green-500' }} pl-4 py-3 bg-gray-50 rounded-r-lg">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full {{ $item['node'] == 'Laravel_Node_1' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600' }} flex items-center justify-center mr-3">
                                {{ $item['node'] == 'Laravel_Node_1' ? '1' : '2' }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">{{ $item['node'] }}</p>
                                <p class="text-xs text-gray-500">Request #{{ $originalIndex }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($item['timestamp'])->format('H:i:s') }}</span>
                            <div class="text-xs text-gray-400 mt-1">
                                {{ \Carbon\Carbon::parse($item['timestamp'])->format('M d') }}
                            </div>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-globe mr-1"></i> IP: {{ $item['client_ip'] }}
                        @if(isset($item['entry_id']))
                            <span class="ml-3">
                                <i class="fas fa-hashtag mr-1"></i> ID: {{ substr($item['entry_id'], 0, 8) }}...
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        
        @if(count($sessionData) > 0)
        <div class="mt-4 pt-4 border-t border-gray-200 text-center text-sm text-gray-500">
            <i class="fas fa-info-circle mr-1"></i>
            Showing newest first ({{ count($sessionData) }} total requests)
        </div>
        @else
        <div class="text-center py-8 text-gray-400">
            <i class="fas fa-inbox text-4xl mb-3"></i>
            <p>No session history yet</p>
            <p class="text-sm mt-1">Refresh the page to start tracking</p>
        </div>
        @endif
    </div>
    </div>

    <!-- Instructions Card -->
    <div class="mt-8 bg-gradient-to-r from-green-50 to-blue-50 rounded-xl shadow-lg p-6 border border-green-200">
        <div class="flex items-center mb-6">
            <i class="fas fa-graduation-cap text-green-600 text-2xl mr-3"></i>
            <h2 class="text-2xl font-bold text-gray-800">Test Instructions</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-5 rounded-lg shadow-sm">
                <div class="flex items-center mb-3">
                    <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mr-3">
                        1
                    </div>
                    <h3 class="font-semibold text-lg">Refresh Multiple Times</h3>
                </div>
                <p class="text-gray-600">Click the refresh button or press F5 repeatedly. Each refresh may hit a different server.</p>
            </div>
            
            <div class="bg-white p-5 rounded-lg shadow-sm">
                <div class="flex items-center mb-3">
                    <div class="w-8 h-8 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mr-3">
                        2
                    </div>
                    <h3 class="font-semibold text-lg">Watch the Node Change</h3>
                </div>
                <p class="text-gray-600">Observe the "Current Node" in the top navbar. It should alternate between Node 1 and Node 2.</p>
            </div>
            
            <div class="bg-white p-5 rounded-lg shadow-sm">
                <div class="flex items-center mb-3">
                    <div class="w-8 h-8 bg-green-100 text-green-600 rounded-full flex items-center justify-center mr-3">
                        3
                    </div>
                    <h3 class="font-semibold text-lg">Session Persistence</h3>
                </div>
                <p class="text-gray-600">Notice how your session history grows with each request, even when switching nodes.</p>
            </div>
            
            <div class="bg-white p-5 rounded-lg shadow-sm">
                <div class="flex items-center mb-3">
                    <div class="w-8 h-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center mr-3">
                        4
                    </div>
                    <h3 class="font-semibold text-lg">Redis in Action</h3>
                </div>
                <p class="text-gray-600">This proves Redis is correctly configured for shared session storage across nodes.</p>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap gap-4 justify-center">
            <a href="{{ route('session.test') }}" class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-8 py-3 rounded-lg font-semibold hover:opacity-90 transition transform hover:scale-105 flex items-center">
                <i class="fas fa-sync-alt mr-3"></i>
                🔄 Refresh Page (Test Load Balancing)
            </a>
            <a href="{{ route('home') }}" class="bg-gray-800 text-white px-8 py-3 rounded-lg font-semibold hover:bg-gray-900 transition flex items-center">
                <i class="fas fa-arrow-left mr-3"></i>
                ← Back to Dashboard
            </a>
            <button onclick="location.reload()" class="bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition flex items-center">
                <i class="fas fa-redo mr-3"></i>
                Manual Refresh
            </button>
        </div>
    </div>

    <!-- Technical Details -->
    <div class="mt-8 bg-gray-800 text-white rounded-xl p-6">
        <h3 class="text-xl font-bold mb-4"><i class="fas fa-code mr-2"></i>Technical Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="bg-gray-700 p-4 rounded-lg">
                <p class="font-semibold text-green-300 mb-2"><i class="fas fa-cog mr-2"></i>Session Driver</p>
                <code>SESSION_DRIVER=redis</code>
            </div>
            <div class="bg-gray-700 p-4 rounded-lg">
                <p class="font-semibold text-blue-300 mb-2"><i class="fas fa-database mr-2"></i>Redis Host</p>
                <code>REDIS_HOST=redis</code>
            </div>
            <div class="bg-gray-700 p-4 rounded-lg">
                <p class="font-semibold text-purple-300 mb-2"><i class="fas fa-network-wired mr-2"></i>Load Balancer</p>
                <code>nginx upstream with 2 servers</code>
            </div>
        </div>
    </div>
</div>
@endsection