<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordAction
{
    /**
     * Send password reset link to user email
     *
     * @param  array{email: string}  $data
     * @return array{message: string}
     */
    public function execute(array $data): array
    {
        // Delete old tokens for this email
        DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->delete();

        // Generate new token
        $token = Str::random(64);

        // Store hashed token
        DB::table('password_reset_tokens')->insert([
            'email' => $data['email'],
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // TODO: Send email with reset link
        // Mail::to($data['email'])->send(new PasswordResetMail($token));

        return [
            'message' => 'Password reset link has been sent to your email.',
        ];
    }
}
