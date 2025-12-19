<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Local DevOps Demo</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Icons & HTTP -->
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* small polish */
        body { font-feature-settings: "ss01"; }
    </style>
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

<!-- ================= HEADER ================= -->
<header class="sticky top-0 z-50 bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">

        <!-- Logo / Title -->
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-purple-600 text-white">
                <i class="fab fa-laravel text-white"></i>
            </div>
            <div>
                <h1 class="text-lg font-bold leading-tight">
                    Laravel Local DevOps Demo
                </h1>
                <p class="text-xs text-gray-500">
                    Load balanced • Redis sessions • Queues • S3
                </p>
            </div>
        </div>

        <!-- Status Pills -->
        <div class="flex items-center space-x-3 text-sm">

            <!-- Node -->
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                <i class="fas fa-microchip text-xs"></i>
                {{ env('APP_NAME', 'Node') }}
            </span>

            <!-- Session -->
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-green-50 text-green-700 border border-green-200">
                <i class="fas fa-id-card text-xs"></i>
                {{ substr(session()->getId(), 0, 8) }}…
            </span>

            <!-- Redis -->
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                <i class="fas fa-memory text-xs"></i>
                Redis
            </span>
        </div>
    </div>
</header>

<!-- ================= CONTENT ================= -->
<main class="flex-1 max-w-7xl mx-auto w-full px-6 py-8">
    @yield('content')
</main>

<!-- ================= FOOTER ================= -->
<footer class="bg-gray-900 text-gray-300 border-t border-gray-700">
    <div class="max-w-7xl mx-auto px-6 py-6 grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">

        <!-- About -->
        <div>
            <h3 class="text-white font-semibold mb-2">About This Demo</h3>
            <p class="text-gray-400 leading-relaxed">
                Demonstrates a horizontally scalable Laravel architecture using
                Docker, Nginx load balancing, Redis-backed sessions, queues,
                and S3-compatible object storage.
            </p>
        </div>

        <!-- Stack -->
        <div>
            <h3 class="text-white font-semibold mb-2">Tech Stack</h3>
            <ul class="space-y-1 text-gray-400">
                <li><i class="fab fa-laravel mr-2 text-red-400"></i> Laravel</li>
                <li><i class="fab fa-docker mr-2 text-blue-400"></i> Docker / Compose</li>
                <li><i class="fas fa-network-wired mr-2 text-green-400"></i> Nginx Load Balancer</li>
                <li><i class="fas fa-database mr-2 text-purple-400"></i> Redis Sessions & Queues</li>
                <li><i class="fas fa-cloud mr-2 text-yellow-400"></i> MinIO (S3)</li>
            </ul>
        </div>

        <!-- Environment -->
        <div>
            <h3 class="text-white font-semibold mb-2">Environment</h3>
            <ul class="space-y-1 text-gray-400">
                <li>
                    <i class="fas fa-layer-group mr-2"></i>
                    Environment: <span class="text-gray-200">{{ config('app.env') }}</span>
                </li>
                <li>
                    <i class="fas fa-clock mr-2"></i>
                    Server Time: <span class="text-gray-200">{{ now()->format('Y-m-d H:i:s') }}</span>
                </li>
                <li>
                    <i class="fas fa-heartbeat mr-2"></i>
                    Status: <span class="text-green-400 font-medium">Healthy</span>
                </li>
            </ul>
        </div>
    </div>

    <div class="border-t border-gray-700 text-center py-3 text-xs text-gray-500">
        © {{ date('Y') }} Laravel Docker Cluster Demo • Built for learning & scaling
    </div>
</footer>

@stack('scripts')
</body>
</html>
