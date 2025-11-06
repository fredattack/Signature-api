<?php

namespace App\Actions\Signature;

use App\Models\Signature;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeleteSignatureAction
{
    /**
     * Delete a signature (soft delete)
     *
     * @param  array{signature_id: string, user_id: string}  $data
     * @return array{message: string}
     *
     * @throws ValidationException
     */
    public function execute(array $data): array
    {
        $signature = Signature::where('id', $data['signature_id'])
            ->where('user_id', $data['user_id'])
            ->first();

        if (! $signature) {
            throw ValidationException::withMessages([
                'signature_id' => ['Signature not found or you do not have permission to delete it.'],
            ]);
        }

        // Soft delete the signature
        $signature->delete();

        // Note: We keep the image file even after soft delete
        // It can be cleaned up later with a scheduled job that removes images from truly deleted signatures

        return [
            'message' => 'Signature deleted successfully.',
        ];
    }

    /**
     * Permanently delete a signature and its image
     *
     * @param  array{signature_id: string, user_id: string}  $data
     * @return array{message: string}
     *
     * @throws ValidationException
     */
    public function forceDelete(array $data): array
    {
        $signature = Signature::withTrashed()
            ->where('id', $data['signature_id'])
            ->where('user_id', $data['user_id'])
            ->first();

        if (! $signature) {
            throw ValidationException::withMessages([
                'signature_id' => ['Signature not found or you do not have permission to delete it.'],
            ]);
        }

        // Delete image file
        if ($signature->image_path) {
            Storage::disk('public')->delete($signature->image_path);
        }

        // Permanently delete signature
        $signature->forceDelete();

        return [
            'message' => 'Signature permanently deleted.',
        ];
    }
}
