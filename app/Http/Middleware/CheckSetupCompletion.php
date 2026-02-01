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
        // Sekarang kita HANYA mengecek Manager. Super Admin akan otomatis lewat (skip).
        if ($user && ($user->hasRole('Manager') || in_array($user->role, ['Manager', 'manager']))) {

            $hasSettings = ApplicationSetting::where('user_id', $user->id)->exists();

            if (!$hasSettings) {
                return redirect()->route('setup');
            }
        }

        // Tentukan siapa yang TIDAK perlu setup (Pengecualian)
        $excludedRoles = ['Super Admin', 'Manager'];

        // Cek apakah user login DAN user TIDAK punya role pengecualian tersebut
        if ($user && !$user->hasAnyRole($excludedRoles)) {

            // Cek apakah setting sudah ada
            $hasSettings = ApplicationSetting::where('user_id', $user->created_by)->exists();

            if (!$hasSettings) {
                return redirect()->route('restrictedaccess');
            }
        }

        return $next($request);
    }
}
