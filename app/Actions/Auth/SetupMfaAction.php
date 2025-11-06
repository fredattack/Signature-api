<?php

namespace App\Actions\Auth;

use App\Models\User;
use chillerlan\QRCode\QRCode;
use OTPHP\TOTP;

class SetupMfaAction
{
    /**
     * Setup MFA for user (generate secret and QR code)
     *
     * @param  array{user_id: string}  $data
     * @return array{secret: string, qr_code: string}
     */
    public function execute(array $data): array
    {
        $user = User::findOrFail($data['user_id']);

        // Generate TOTP secret
        $totp = TOTP::generate();
        $totp->setLabel($user->email ?: 'user');
        $totp->setIssuer(config('app.name') ?: 'SignatureApp');

        $secret = $totp->getSecret();

        // Save secret (not enabled yet)
        $user->update([
            'mfa_secret' => $secret,
        ]);

        // Generate QR Code
        $provisioningUri = $totp->getProvisioningUri();
        $qrCode = (new QRCode)->render($provisioningUri);

        return [
            'secret' => $secret,
            'qr_code' => $qrCode,
        ];
    }
}
