<?php

namespace App\Actions\Signature;

use App\Models\Signature;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;

class UpdateSignatureAction
{
    /**
     * Update an existing signature
     *
     * @param  array{signature_id: string, user_id: string, name?: string, description?: string, image?: UploadedFile}  $data
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
                'signature_id' => ['Signature not found or you do not have permission to update it.'],
            ]);
        }

        $updateData = [];

        // Update name if provided
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        // Update description if provided
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }

        // Update image if provided
        if (isset($data['image'])) {
            // Delete old image
            if ($signature->image_path) {
                Storage::disk('public')->delete($signature->image_path);
            }

            // Process and store new image
            $imageData = $this->processAndStoreImage($data['image']);
            $updateData['image_path'] = $imageData['path'];
            $updateData['image_width'] = $imageData['width'];
            $updateData['image_height'] = $imageData['height'];
            $updateData['file_size'] = $imageData['size'];
            $updateData['mime_type'] = $imageData['mime_type'];
        }

        $signature->update($updateData);
        $signature->refresh();

        return $signature;
    }

    /**
     * Process and store signature image
     *
     * @return array{path: string, width: int, height: int, size: int, mime_type: string}
     */
    private function processAndStoreImage(UploadedFile $file): array
    {
        // Create image manager
        $manager = ImageManager::gd();

        // Load and process image
        $image = $manager->read($file->getRealPath());

        // Resize if too large (max 2000px on longest side)
        $maxSize = 2000;
        if ($image->width() > $maxSize || $image->height() > $maxSize) {
            $image->scale(width: $maxSize, height: $maxSize);
        }

        // Remove background (make transparent)
        $image->trim();

        // Generate unique filename
        $filename = Str::uuid().'.png';
        $path = 'signatures/'.$filename;

        // Convert to PNG for transparency support
        $encodedImage = $image->toPng();

        // Store image
        Storage::disk('public')->put($path, (string) $encodedImage);

        return [
            'path' => $path,
            'width' => $image->width(),
            'height' => $image->height(),
            'size' => Storage::disk('public')->size($path),
            'mime_type' => 'image/png',
        ];
    }
}
