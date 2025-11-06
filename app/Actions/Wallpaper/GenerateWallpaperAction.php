<?php

namespace App\Actions\Wallpaper;

use App\Models\Signature;
use App\Models\Wallpaper;
use App\Models\WallpaperTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class GenerateWallpaperAction
{
    /**
     * Generate a wallpaper from a signature and template
     *
     * @param  array{signature_id: string, template_id: string, resolution?: string}  $data
     */
    public function execute(array $data): Wallpaper
    {
        $signature = Signature::findOrFail($data['signature_id']);
        $template = WallpaperTemplate::findOrFail($data['template_id']);

        // Determine resolution (default to 1920x1080)
        $resolution = $data['resolution'] ?? '1920x1080';
        [$width, $height] = explode('x', $resolution);

        // Generate wallpaper image
        $wallpaperData = $this->generateWallpaperImage($signature, $template, (int) $width, (int) $height);

        // Create wallpaper record
        $wallpaper = Wallpaper::create([
            'id' => Str::uuid(),
            'signature_id' => $signature->id,
            'template_id' => $template->id,
            'wallpaper_url' => $wallpaperData['url'],
            'thumbnail_url' => $wallpaperData['thumbnail_url'],
            'resolution' => $resolution,
            'file_size' => $wallpaperData['size'],
            'generation_status' => 'completed',
        ]);

        return $wallpaper;
    }

    /**
     * Generate wallpaper image by compositing signature on template
     *
     * @return array{url: string, thumbnail_url: string, size: int}
     */
    private function generateWallpaperImage(
        Signature $signature,
        WallpaperTemplate $template,
        int $width,
        int $height
    ): array {
        $manager = ImageManager::gd();

        // Create base canvas with specified dimensions
        $wallpaper = $manager->create($width, $height);

        // Get template config
        /** @var array<string, mixed> $config */
        $config = $template->config ?? [];

        // Apply background color (from template config or default)
        /** @var string $bgColor */
        $bgColor = $config['background_color'] ?? '#000000';
        $wallpaper->fill($bgColor);

        // Load signature image
        $signaturePath = $signature->image_path ? Storage::disk('public')->path($signature->image_path) : '';
        if (! $signaturePath) {
            throw new \Exception('Signature image path is missing');
        }
        $signatureImage = $manager->read($signaturePath);

        // Calculate signature position and size based on template config
        /** @var string $signaturePosition */
        $signaturePosition = $config['signature_position'] ?? 'bottom-right';
        /** @var float $signatureScale */
        $signatureScale = $config['signature_scale'] ?? 0.3; // 30% of wallpaper width

        // Scale signature
        $targetWidth = (int) ($width * $signatureScale);
        $signatureImage->scale(width: $targetWidth);

        // Position signature
        /** @var int $padding */
        $padding = $config['padding'] ?? 50;
        $position = $this->calculateSignaturePosition(
            $signaturePosition,
            $width,
            $height,
            $signatureImage->width(),
            $signatureImage->height(),
            $padding
        );

        // Place signature on wallpaper
        $wallpaper->place($signatureImage, 'top-left', $position['x'], $position['y']);

        // Save wallpaper
        $filename = Str::uuid().'.png';
        $path = 'wallpapers/'.$filename;
        $encodedWallpaper = $wallpaper->toPng();
        Storage::disk('public')->put($path, (string) $encodedWallpaper);

        // Generate thumbnail (400x300)
        $thumbnail = $wallpaper->scale(width: 400);
        $thumbnailFilename = Str::uuid().'_thumb.png';
        $thumbnailPath = 'wallpapers/thumbnails/'.$thumbnailFilename;
        Storage::disk('public')->put($thumbnailPath, (string) $thumbnail->toPng());

        return [
            'url' => Storage::disk('public')->url($path),
            'thumbnail_url' => Storage::disk('public')->url($thumbnailPath),
            'size' => Storage::disk('public')->size($path),
        ];
    }

    /**
     * Calculate signature position based on configuration
     *
     * @return array{x: int, y: int}
     */
    private function calculateSignaturePosition(
        string $position,
        int $wallpaperWidth,
        int $wallpaperHeight,
        int $signatureWidth,
        int $signatureHeight,
        int $padding
    ): array {
        return match ($position) {
            'top-left' => ['x' => $padding, 'y' => $padding],
            'top-center' => ['x' => (int) (($wallpaperWidth - $signatureWidth) / 2), 'y' => $padding],
            'top-right' => ['x' => $wallpaperWidth - $signatureWidth - $padding, 'y' => $padding],
            'center' => [
                'x' => (int) (($wallpaperWidth - $signatureWidth) / 2),
                'y' => (int) (($wallpaperHeight - $signatureHeight) / 2),
            ],
            'bottom-left' => ['x' => $padding, 'y' => $wallpaperHeight - $signatureHeight - $padding],
            'bottom-center' => [
                'x' => (int) (($wallpaperWidth - $signatureWidth) / 2),
                'y' => $wallpaperHeight - $signatureHeight - $padding,
            ],
            'bottom-right' => [
                'x' => $wallpaperWidth - $signatureWidth - $padding,
                'y' => $wallpaperHeight - $signatureHeight - $padding,
            ],
            default => ['x' => $wallpaperWidth - $signatureWidth - $padding, 'y' => $wallpaperHeight - $signatureHeight - $padding],
        };
    }
}
