<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\ApplicationSetting;

class CheckSetupCompletion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Prevent redirect loop if already on setup page or logout
        if ($request->routeIs('setup') || $request->is('setup*') || $request->routeIs('logout')) {
            return $next($request);
        }

        // Check if user has specific roles and needs setup
        // We check if they have 'Super Admin' or 'Manager' role
        if ($user && ($user->hasRole(['Super Admin', 'Manager']) || in_array($user->role, ['Super Admin', 'Manager', 'super_admin', 'manager']))) {
            $hasSettings = ApplicationSetting::where('user_id', $user->id)->exists();
            
            if (!$hasSettings) {
                return redirect()->route('setup');
            }
        }

        return $next($request);
    }
}
