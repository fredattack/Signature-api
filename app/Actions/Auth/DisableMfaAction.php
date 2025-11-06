<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class DisableMfaAction
{
    /**
     * Disable MFA for user
     *
     * @param  array{user_id: string}  $data
     * @return array{message: string}
     *
     * @throws ValidationException
     */
    public function execute(array $data): array
    {
        $user = User::findOrFail($data['user_id']);

        if (! $user->mfa_enabled) {
            throw ValidationException::withMessages([
                'mfa' => ['MFA is not enabled for this user.'],
            ]);
        }

        // Disable MFA and clear secret
        $user->update([
            'mfa_enabled' => false,
            'mfa_secret' => null,
        ]);

        return [
            'message' => 'MFA has been disabled successfully.',
        ];
    }
}
