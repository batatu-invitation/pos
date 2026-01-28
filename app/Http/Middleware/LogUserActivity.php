<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log authenticated users
        if (Auth::check()) {
            // Determine action description based on method
            $method = $request->method();
            $url = $request->fullUrl();
            $path = $request->path();

            if ($this->shouldLog($request)) {

                $event = 'view';
                $description = "Visited " . $path;

                // Map methods to semantic events
                if ($method === 'POST') {
                    $event = 'created';
                    $description = "Created resource on " . $path;
                }
                if ($method === 'PUT' || $method === 'PATCH') {
                    $event = 'updated';
                    $description = "Updated resource on " . $path;
                }
                if ($method === 'DELETE') {
                    $event = 'deleted';
                    $description = "Deleted resource on " . $path;
                }

                // Handle Auth events specifically if path matches
                if (str_contains($path, 'login')) {
                    $event = 'login';
                    $description = "User logged in";
                }
                if (str_contains($path, 'logout')) {
                    $event = 'logout';
                    $description = "User logged out";
                }

                // Status logic
                $status = $response->getStatusCode() >= 400 ? 'Failed' : 'Success';

                activity()
                    ->causedBy(Auth::user())
                    ->withProperties([
                        'ip' => $request->ip(),
                        'status' => $status,
                        'user_agent' => $request->userAgent(),
                        'method' => $method,
                        'url' => $url
                    ])
                    ->event($event)
                    ->log($description);
            }
        }

        return $response;
    }

    protected function shouldLog(Request $request): bool
    {
        $path = $request->path();

        // Exclude internal routes or assets
        $excludes = [
            'livewire', // Livewire internal polling/updates
            '_debugbar',
            'sanctum',
            'api',
            'up',
        ];

        foreach ($excludes as $exclude) {
            if (str_starts_with($path, $exclude)) {
                return false;
            }
        }

        return true;
    }
}
