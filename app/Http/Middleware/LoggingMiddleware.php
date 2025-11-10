<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // en millisecondes

        $user = Auth::guard('api')->user();
        $userId = $user ? $user->id : 'guest';

        // Log des informations de la requête
        $logData = [
            'user_id' => $userId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'request_size' => strlen($request->getContent()),
            'response_size' => strlen($response->getContent()),
        ];

        // Log selon le niveau d'importance
        if ($response->getStatusCode() >= 500) {
            Log::error('API Request Error', $logData);
        } elseif ($response->getStatusCode() >= 400) {
            Log::warning('API Request Warning', $logData);
        } elseif ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('delete')) {
            Log::info('API Request Modification', $logData);
        } else {
            Log::debug('API Request', $logData);
        }

        // Ajouter des headers de monitoring à la réponse
        $response->headers->set('X-Request-ID', uniqid());
        $response->headers->set('X-Response-Time', $duration . 'ms');

        return $response;
    }
}
