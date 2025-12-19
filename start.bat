@echo off
echo Starting Laravel Docker Cluster...
echo.

REM Check if Docker is running
docker info >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Docker Desktop is not running!
    pause
    exit /b 1
)

echo Starting containers in detached mode...
docker-compose up -d

echo.
echo Waiting for services to be ready...
timeout /t 10 /nobreak >nul

echo.
echo ============================================
echo SERVICES STATUS:
echo ============================================
docker-compose ps

echo.
echo ============================================
echo ACCESS POINTS:
echo ============================================
echo Application:     http://localhost:8080
echo MinIO Console:   http://localhost:9001
echo MySQL:           localhost:3306 (root/root)
echo Redis:           localhost:6379
echo.
echo Health Check:    http://localhost:8080/health
echo ============================================
echo.
echo Use 'logs.bat' to view logs
echo Use 'stop.bat' to stop all services
echo.

REM Open browser to application
start http://localhost:8080

pause