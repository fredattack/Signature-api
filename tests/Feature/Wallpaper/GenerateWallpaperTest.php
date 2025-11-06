<?php

namespace Tests\Feature\Wallpaper;

use App\Models\Signature;
use App\Models\User;
use App\Models\WallpaperTemplate;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class GenerateWallpaperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_generate_wallpaper(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        // Create signature with a test image
        $signature = Signature::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Test Signature',
            'image_path' => 'signatures/test.png',
            'image_width' => 500,
            'image_height' => 200,
            'file_size' => 10000,
            'mime_type' => 'image/png',
        ]);

        // Create a simple test image for the signature
        $this->createTestImage('signatures/test.png');

        // Create template
        $template = WallpaperTemplate::create([
            'id' => Str::uuid(),
            'name' => 'Test Template',
            'is_active' => true,
            'config' => [
                'background_color' => '#000000',
                'signature_position' => 'bottom-right',
                'signature_scale' => 0.3,
                'padding' => 50,
            ],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson('/api/v1/wallpapers', [
                'signature_id' => $signature->id,
                'template_id' => $template->id,
                'resolution' => '1920x1080',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'signature_id',
                    'template_id',
                    'wallpaper_url',
                    'thumbnail_url',
                    'resolution',
                    'file_size',
                    'generation_status',
                    'signature',
                    'template',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'signature_id' => $signature->id,
                    'template_id' => $template->id,
                    'resolution' => '1920x1080',
                    'generation_status' => 'completed',
                ],
            ]);
    }

    public function test_generate_wallpaper_requires_valid_signature(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $template = WallpaperTemplate::create([
            'id' => Str::uuid(),
            'name' => 'Test Template',
            'is_active' => true,
            'config' => [],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson('/api/v1/wallpapers', [
                'signature_id' => Str::uuid(),
                'template_id' => $template->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['signature_id']);
    }

    public function test_generate_wallpaper_requires_valid_template(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $signature = Signature::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Test Signature',
            'image_path' => 'signatures/test.png',
            'image_width' => 500,
            'image_height' => 200,
            'file_size' => 10000,
            'mime_type' => 'image/png',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson('/api/v1/wallpapers', [
                'signature_id' => $signature->id,
                'template_id' => Str::uuid(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['template_id']);
    }

    private function createTestImage(string $path): void
    {
        // Create a simple test PNG image
        $image = imagecreatetruecolor(500, 200);
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, 500, 200, $bgColor);

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        Storage::disk('public')->put($path, $imageData);
    }
}
