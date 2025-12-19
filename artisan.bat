@echo off
echo ============================================
echo Laravel Artisan Commands
echo ============================================
echo.
echo Select container:
echo 1. App Server 1
echo 2. App Server 2
echo 3. Queue Worker
echo.
set /p container="Enter choice (1-3): "
set /p command="Enter Artisan command (e.g., 'migrate', 'make:controller Test'): "

if "%container%"=="1" docker-compose exec app-1 php artisan %command%
if "%container%"=="2" docker-compose exec app-2 php artisan %command%
if "%container%"=="3" docker-compose exec queue-worker php artisan %command%

echo.
pause