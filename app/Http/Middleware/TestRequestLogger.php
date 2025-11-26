<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TestRequestLogger
{
    /**
     * Log incoming requests during testing to help debug PHPUnit flows.
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            if (app()->environment('testing')) {
                $context = [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'payload' => $request->all(),
                ];
                try { \Log::info('TestRequestLogger: incoming request', $context); } catch (\Exception $e) {}
                // Also write a deterministic file so PHPUnit can read it even if log level filters apply
                try {
                    $file = base_path('storage/logs/test-request.log');
                    $line = date('Y-m-d H:i:s').' '.json_encode($context, JSON_UNESCAPED_SLASHES)."\n";
                    file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
                } catch (\Exception $e) {
                    // ignore
                }
                // Also write to STDERR so PHPUnit shows the payload in test output.
                try {
                    $stderr = fopen('php://stderr', 'w');
                    if ($stderr) {
                        fwrite($stderr, "TEST_REQUEST_LOG: " . json_encode($context, JSON_UNESCAPED_SLASHES) . "\n");
                        fclose($stderr);
                    }
                } catch (\Exception $e) {
                    // ignore
                }
            }
        } catch (\Exception $e) {
            // ignore logging errors
        }

        return $next($request);
    }
}
