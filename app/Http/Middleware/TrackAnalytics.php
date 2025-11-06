<?php

namespace App\Http\Middleware;

use App\Services\AnalyticsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackAnalytics
{
    public function __construct(
        protected AnalyticsService $analytics
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Track after response to avoid blocking the request
        if ($this->shouldTrack($request, $response)) {
            $this->trackRequest($request, $response);
        }

        return $response;
    }

    /**
     * Determine if the request should be tracked
     */
    protected function shouldTrack(Request $request, Response $response): bool
    {
        // Don't track health checks, metrics endpoints, or analytics itself
        if ($request->is('health', 'metrics', 'api/*/analytics/*')) {
            return false;
        }

        // Only track successful API requests
        if (! $request->is('api/*')) {
            return false;
        }

        // Track successful requests (2xx) and important errors (403, 404)
        $statusCode = $response->getStatusCode();

        return $statusCode >= 200 && $statusCode < 300 || in_array($statusCode, [403, 404]);
    }

    /**
     * Track the request
     */
    protected function trackRequest(Request $request, Response $response): void
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        $eventType = $this->determineEventType($request);
        $eventData = [
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
        ];

        if ($eventType) {
            $this->analytics->trackEvent($eventType, $user, $eventData, $request);
        }
    }

    /**
     * Determine event type from request
     */
    protected function determineEventType(Request $request): ?string
    {
        $method = $request->method();
        $path = $request->path();

        // Signature events
        if (str_contains($path, 'signatures')) {
            if ($method === 'POST' && ! str_contains($path, '/')) {
                return 'signature.created';
            }
            if ($method === 'DELETE') {
                return 'signature.deleted';
            }
            if ($method === 'GET') {
                return str_contains($path, '/') ? 'signature.viewed' : null;
            }
        }

        // Wallpaper events
        if (str_contains($path, 'wallpapers') && ! str_contains($path, 'templates')) {
            if ($method === 'POST') {
                return 'wallpaper.generated';
            }
            if ($method === 'DELETE') {
                return 'wallpaper.deleted';
            }
        }

        // Template events
        if (str_contains($path, 'wallpaper-templates')) {
            if ($method === 'GET') {
                return str_contains($path, '/') ? 'template.viewed' : 'template.list_viewed';
            }
        }

        return null;
    }
}
