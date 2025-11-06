<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetPasswordAction
{
    /**
     * Reset user password with token
     *
     * @param  array{email: string, token: string, password: string}  $data
     * @return array{message: string}
     *
     * @throws ValidationException
     */
    public function execute(array $data): array
    {
        // Find reset token
        /** @var object{email: string, token: string, created_at: string}|null $resetRecord */
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->first();

        if (! $resetRecord) {
            throw ValidationException::withMessages([
                'email' => ['No password reset request found for this email.'],
            ]);
        }

        // Verify token
        if (! Hash::check($data['token'], $resetRecord->token)) {
            throw ValidationException::withMessages([
                'token' => ['Invalid password reset token.'],
            ]);
        }

        // Check if token is expired (valid for 1 hour)
        if (now()->diffInMinutes($resetRecord->created_at) > 60) {
            throw ValidationException::withMessages([
                'token' => ['Password reset token has expired.'],
            ]);
        }

        // Update user password
        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }

        $user->update([
            'password_hash' => Hash::make($data['password']),
        ]);

        // Delete used token
        DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->delete();

        return [
            'message' => 'Password has been reset successfully.',
        ];
    }
}
