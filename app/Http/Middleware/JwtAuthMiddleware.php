<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    public function __construct(
        private readonly JwtService $jwtService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'message' => 'Unauthorized. Token not provided.',
            ], 401);
        }

        $payload = $this->jwtService->verifyAccessToken($token);

        if (! $payload) {
            return response()->json([
                'message' => 'Unauthorized. Invalid or expired token.',
            ], 401);
        }

        /** @var string $userId */
        $userId = $payload->sub ?? null;

        if (! $userId) {
            return response()->json([
                'message' => 'Unauthorized. Invalid token payload.',
            ], 401);
        }

        $user = User::find($userId);

        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized. User not found.',
            ], 401);
        }

        // Attach user to request
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
