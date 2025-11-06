<?php

namespace App\Services;

use App\Models\Session;
use App\Models\User;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;

class JwtService
{
    /**
     * Generate access token
     */
    public function generateAccessToken(User $user): string
    {
        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + (int) config('jwt.access_token_ttl', 900), // 15 minutes
        ];

        return JWT::encode($payload, config('jwt.secret'), config('jwt.algo', 'HS256'));
    }

    /**
     * Generate refresh token
     *
     * @return array{token: string, session: Session}
     */
    public function generateRefreshToken(User $user, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        $token = Str::random(64);
        $ttl = (int) config('jwt.refresh_token_ttl', 2592000); // 30 days

        $session = Session::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'refresh_token' => hash('sha256', $token),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => Carbon::now()->addSeconds($ttl),
            'last_used_at' => Carbon::now(),
        ]);

        return [
            'token' => $token,
            'session' => $session,
        ];
    }

    /**
     * Verify access token
     */
    public function verifyAccessToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key(config('jwt.secret'), config('jwt.algo', 'HS256')));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Verify refresh token and return session
     */
    public function verifyRefreshToken(string $token): ?Session
    {
        $hashedToken = hash('sha256', $token);

        $session = Session::where('refresh_token', $hashedToken)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($session) {
            $session->update(['last_used_at' => Carbon::now()]);
        }

        return $session;
    }

    /**
     * Revoke refresh token
     */
    public function revokeRefreshToken(string $token): bool
    {
        $hashedToken = hash('sha256', $token);

        return Session::where('refresh_token', $hashedToken)->delete() > 0;
    }

    /**
     * Revoke all user sessions
     */
    public function revokeAllUserSessions(User $user): int
    {
        return Session::where('user_id', $user->id)->delete();
    }
}
