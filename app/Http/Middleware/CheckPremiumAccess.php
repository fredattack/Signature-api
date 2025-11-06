<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPremiumAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Check if user has active premium
        if (! $user->is_premium || ! ($user->premium_expires_at instanceof \DateTimeInterface) || $user->premium_expires_at < now()) {
            return response()->json([
                'message' => 'Premium subscription required to access this resource.',
                'error' => 'PREMIUM_REQUIRED',
            ], 403);
        }

        return $next($request);
    }
}
