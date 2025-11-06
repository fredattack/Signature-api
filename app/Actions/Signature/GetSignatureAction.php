<?php

namespace App\Actions\Signature;

use App\Models\Signature;
use Illuminate\Validation\ValidationException;

class GetSignatureAction
{
    /**
     * Get a specific signature
     *
     * @param  array{signature_id: string, user_id: string}  $data
     *
     * @throws ValidationException
     */
    public function execute(array $data): Signature
    {
        $signature = Signature::where('id', $data['signature_id'])
            ->where('user_id', $data['user_id'])
            ->first();

        if (! $signature) {
            throw ValidationException::withMessages([
                'signature_id' => ['Signature not found or you do not have permission to view it.'],
            ]);
        }

        return $signature;
    }
}
