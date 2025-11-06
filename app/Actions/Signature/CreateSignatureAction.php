<?php

namespace App\Actions\Signature;

use App\Models\Signature;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class CreateSignatureAction
{
    /**
     * Create a new signature with image processing
     *
     * @param  array{user_id: string, name: string, image: UploadedFile, description?: string}  $data
     */
    public function execute(array $data): Signature
    {
        // Process and store image
        $imageData = $this->processAndStoreImage($data['image']);

        // Create signature record
        $signature = Signature::create([
            'id' => Str::uuid(),
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'image_path' => $imageData['path'],
            'image_width' => $imageData['width'],
            'image_height' => $imageData['height'],
            'file_size' => $imageData['size'],
            'mime_type' => $imageData['mime_type'],
        ]);

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
        // This is a simplified version - in production you'd use more sophisticated background removal
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
