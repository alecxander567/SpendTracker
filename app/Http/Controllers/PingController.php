<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PingController extends Controller
{
    /**
     * Simple ping endpoint for uptime monitoring.
     */
    public function ping(Request $request)
    {
        try {
            // Check database connection
            DB::connection()->getPdo();
            $dbStatus = 'connected';
        } catch (\Exception $e) {
            $dbStatus = 'error: ' . $e->getMessage();
        }

        // Check application status
        $appStatus = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'app_name' => config('app.name'),
            'app_version' => config('app.version', '1.0.0'),
        ];

        // Check if database is healthy
        $isHealthy = strpos($dbStatus, 'error') === false;

        // Response data
        $response = [
            'success' => $isHealthy,
            'message' => $isHealthy ? 'Application is healthy' : 'Application is unhealthy',
            'data' => [
                'app' => $appStatus,
                'database' => [
                    'status' => $dbStatus,
                    'connection' => config('database.default'),
                ],
                'server' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'server_time' => now()->toDateTimeString(),
                    'timezone' => config('app.timezone'),
                ],
                'system' => [
                    'memory_usage' => $this->getMemoryUsage(),
                    'load_average' => $this->getLoadAverage(),
                ],
            ],
        ];

        return response()->json($response, $isHealthy ? 200 : 503);
    }

    /**
     * Simple ping endpoint (minimal response for uptime monitoring).
     */
    public function simplePing()
    {
        try {
            // Just check if we can connect to the database
            DB::connection()->getPdo();

            return response()->json([
                'status' => 'ok',
                'timestamp' => now()->toIso8601String(),
                'message' => 'Application is running',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'timestamp' => now()->toIso8601String(),
                'message' => 'Application is unavailable',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 503);
        }
    }

    /**
     * Health check endpoint with detailed system information.
     */
    public function healthCheck(Request $request)
    {
        try {
            $checks = [];

            // Check database
            try {
                DB::connection()->getPdo();
                $checks['database'] = [
                    'status' => 'passed',
                    'message' => 'Database connection successful',
                ];
            } catch (\Exception $e) {
                $checks['database'] = [
                    'status' => 'failed',
                    'message' => 'Database connection failed: ' . $e->getMessage(),
                ];
            }

            // Check storage
            $storagePath = storage_path();
            $isWritable = is_writable($storagePath);
            $checks['storage'] = [
                'status' => $isWritable ? 'passed' : 'failed',
                'message' => $isWritable ? 'Storage is writable' : 'Storage is not writable',
            ];

            // Check cache
            try {
                cache()->put('health_check', 'ok', 60);
                $cacheValue = cache()->get('health_check');
                $checks['cache'] = [
                    'status' => $cacheValue === 'ok' ? 'passed' : 'failed',
                    'message' => $cacheValue === 'ok' ? 'Cache is working' : 'Cache is not working',
                ];
            } catch (\Exception $e) {
                $checks['cache'] = [
                    'status' => 'failed',
                    'message' => 'Cache failed: ' . $e->getMessage(),
                ];
            }

            // Check if all passed
            $allPassed = collect($checks)->every(fn($check) => $check['status'] === 'passed');

            return response()->json([
                'success' => $allPassed,
                'status' => $allPassed ? 'healthy' : 'unhealthy',
                'timestamp' => now()->toIso8601String(),
                'checks' => $checks,
                'summary' => [
                    'passed' => collect($checks)->filter(fn($check) => $check['status'] === 'passed')->count(),
                    'failed' => collect($checks)->filter(fn($check) => $check['status'] === 'failed')->count(),
                    'total' => count($checks),
                ],
            ], $allPassed ? 200 : 503);
        } catch (\Exception $e) {
            Log::error('Health check failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 'unhealthy',
                'timestamp' => now()->toIso8601String(),
                'error' => 'Health check failed: ' . $e->getMessage(),
            ], 503);
        }
    }

    /**
     * Get memory usage.
     */
    private function getMemoryUsage(): string
    {
        $memory = memory_get_usage(true);
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($memory >= 1024 && $i < count($units) - 1) {
            $memory /= 1024;
            $i++;
        }
        return round($memory, 2) . ' ' . $units[$i];
    }

    /**
     * Get system load average (Unix/Linux only).
     */
    private function getLoadAverage()
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => round($load[0], 2),
                '5min' => round($load[1], 2),
                '15min' => round($load[2], 2),
            ];
        }
        return 'Not available on this platform';
    }
}
