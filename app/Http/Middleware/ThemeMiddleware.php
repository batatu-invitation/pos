<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ThemeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $theme = $request->cookie('theme', 'system');
        
        // Ensure valid theme
        if (!in_array($theme, ['light', 'dark', 'system'])) {
            $theme = 'system';
        }

        View::share('currentTheme', $theme);

        return $next($request);
    }
}
