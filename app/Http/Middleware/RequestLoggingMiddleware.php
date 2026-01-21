<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequestLoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        // Log incoming request
        $logData = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
        ];

        // Don't log sensitive data
        if (!in_array($request->path(), ['api/v1/auth/login', 'api/v1/auth/register'])) {
            $logData['request_data'] = $request->except(['password', 'password_confirmation']);
        }

        \Log::info('API Request', $logData);

        $response = $next($request);

        // Log response
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // in milliseconds

        \Log::info('API Response', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
        ]);

        return $response;
    }
}
