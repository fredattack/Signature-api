<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use OTPHP\TOTP;

class VerifyMfaAction
{
    /**
     * Verify MFA code and enable MFA for user
     *
     * @param  array{user_id: string, code: string}  $data
     * @return array{message: string}
     *
     * @throws ValidationException
     */
    public function execute(array $data): array
    {
        $user = User::findOrFail($data['user_id']);

        if (! $user->mfa_secret) {
            throw ValidationException::withMessages([
                'mfa' => ['MFA is not set up for this user.'],
            ]);
        }

        // Create TOTP instance
        $totp = TOTP::createFromSecret($user->mfa_secret);

        // Verify code
        $code = $data['code'] ?: '000000';
        if (! $totp->verify($code)) {
            throw ValidationException::withMessages([
                'code' => ['Invalid MFA code.'],
            ]);
        }

        // Enable MFA
        $user->update([
            'mfa_enabled' => true,
        ]);

        return [
            'message' => 'MFA has been enabled successfully.',
        ];
    }
}
