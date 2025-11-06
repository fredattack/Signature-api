<?php

namespace Tests\Feature\Wallpaper;

use App\Models\Signature;
use App\Models\User;
use App\Models\Wallpaper;
use App\Models\WallpaperTemplate;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeleteWallpaperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_delete_their_wallpaper(): void
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
            'name' => 'My Signature',
            'image_path' => 'signatures/test.png',
            'image_width' => 500,
            'image_height' => 200,
            'file_size' => 10000,
            'mime_type' => 'image/png',
        ]);

        $template = WallpaperTemplate::create([
            'id' => Str::uuid(),
            'name' => 'Template',
            'is_active' => true,
            'config' => [],
        ]);

        $wallpaper = Wallpaper::create([
            'id' => Str::uuid(),
            'signature_id' => $signature->id,
            'template_id' => $template->id,
            'wallpaper_url' => url('storage/wallpapers/test.png'),
            'thumbnail_url' => url('storage/wallpapers/thumbnails/test_thumb.png'),
            'resolution' => '1920x1080',
            'file_size' => 500000,
            'generation_status' => 'completed',
        ]);

        // Create dummy files
        Storage::disk('public')->put('wallpapers/test.png', 'test content');
        Storage::disk('public')->put('wallpapers/thumbnails/test_thumb.png', 'test content');

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->deleteJson('/api/v1/wallpapers/'.$wallpaper->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Wallpaper deleted successfully.',
            ]);

        $this->assertSoftDeleted('wallpapers', ['id' => $wallpaper->id]);
    }

    public function test_user_cannot_delete_other_users_wallpaper(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $otherUser = User::create([
            'id' => Str::uuid(),
            'email' => 'other@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $otherSignature = Signature::create([
            'id' => Str::uuid(),
            'user_id' => $otherUser->id,
            'name' => 'Other Signature',
            'image_path' => 'signatures/other.png',
            'image_width' => 500,
            'image_height' => 200,
            'file_size' => 10000,
            'mime_type' => 'image/png',
        ]);

        $template = WallpaperTemplate::create([
            'id' => Str::uuid(),
            'name' => 'Template',
            'is_active' => true,
            'config' => [],
        ]);

        $wallpaper = Wallpaper::create([
            'id' => Str::uuid(),
            'signature_id' => $otherSignature->id,
            'template_id' => $template->id,
            'wallpaper_url' => 'https://example.com/wallpaper.png',
            'thumbnail_url' => 'https://example.com/thumb.png',
            'resolution' => '1920x1080',
            'file_size' => 500000,
            'generation_status' => 'completed',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->deleteJson('/api/v1/wallpapers/'.$wallpaper->id);

        $response->assertStatus(404);

        $this->assertDatabaseHas('wallpapers', ['id' => $wallpaper->id]);
    }
}
