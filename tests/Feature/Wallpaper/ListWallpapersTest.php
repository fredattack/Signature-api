<?php

namespace Tests\Feature\Wallpaper;

use App\Models\Signature;
use App\Models\User;
use App\Models\Wallpaper;
use App\Models\WallpaperTemplate;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListWallpapersTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_their_wallpapers(): void
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

        // Create wallpaper for current user
        Wallpaper::create([
            'id' => Str::uuid(),
            'signature_id' => $signature->id,
            'template_id' => $template->id,
            'wallpaper_url' => 'https://example.com/wallpaper1.png',
            'thumbnail_url' => 'https://example.com/thumb1.png',
            'resolution' => '1920x1080',
            'file_size' => 500000,
            'generation_status' => 'completed',
        ]);

        // Create wallpaper for other user (should not appear)
        Wallpaper::create([
            'id' => Str::uuid(),
            'signature_id' => $otherSignature->id,
            'template_id' => $template->id,
            'wallpaper_url' => 'https://example.com/wallpaper2.png',
            'thumbnail_url' => 'https://example.com/thumb2.png',
            'resolution' => '1920x1080',
            'file_size' => 500000,
            'generation_status' => 'completed',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/wallpapers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'signature_id',
                        'template_id',
                        'wallpaper_url',
                        'thumbnail_url',
                        'resolution',
                        'file_size',
                        'generation_status',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_filter_wallpapers_by_signature(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $signature1 = Signature::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Signature 1',
            'image_path' => 'signatures/sig1.png',
            'image_width' => 500,
            'image_height' => 200,
            'file_size' => 10000,
            'mime_type' => 'image/png',
        ]);

        $signature2 = Signature::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Signature 2',
            'image_path' => 'signatures/sig2.png',
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

        Wallpaper::create([
            'id' => Str::uuid(),
            'signature_id' => $signature1->id,
            'template_id' => $template->id,
            'wallpaper_url' => 'https://example.com/wallpaper1.png',
            'thumbnail_url' => 'https://example.com/thumb1.png',
            'resolution' => '1920x1080',
            'file_size' => 500000,
            'generation_status' => 'completed',
        ]);

        Wallpaper::create([
            'id' => Str::uuid(),
            'signature_id' => $signature2->id,
            'template_id' => $template->id,
            'wallpaper_url' => 'https://example.com/wallpaper2.png',
            'thumbnail_url' => 'https://example.com/thumb2.png',
            'resolution' => '1920x1080',
            'file_size' => 500000,
            'generation_status' => 'completed',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/wallpapers?signature_id='.$signature1->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
