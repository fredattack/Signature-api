<?php

namespace Tests\Feature\WallpaperTemplate;

use App\Models\User;
use App\Models\WallpaperTemplate;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListWallpaperTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_active_templates(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        // Create active templates
        WallpaperTemplate::create([
            'id' => Str::uuid(),
            'name' => 'Template 1',
            'description' => 'First template',
            'preview_url' => 'https://example.com/preview1.png',
            'category' => 'minimal',
            'is_premium' => false,
            'is_active' => true,
            'config' => [
                'background_color' => '#000000',
                'signature_position' => 'bottom-right',
                'signature_scale' => 0.3,
                'padding' => 50,
            ],
        ]);

        WallpaperTemplate::create([
            'id' => Str::uuid(),
            'name' => 'Template 2',
            'description' => 'Second template',
            'preview_url' => 'https://example.com/preview2.png',
            'category' => 'artistic',
            'is_premium' => true,
            'is_active' => true,
            'config' => [
                'background_color' => '#FFFFFF',
                'signature_position' => 'center',
                'signature_scale' => 0.4,
                'padding' => 100,
            ],
        ]);

        // Create inactive template (should not appear in results)
        WallpaperTemplate::create([
            'id' => Str::uuid(),
            'name' => 'Inactive Template',
            'description' => 'Inactive',
            'preview_url' => 'https://example.com/preview3.png',
            'category' => 'minimal',
            'is_premium' => false,
            'is_active' => false,
            'config' => [],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/wallpaper-templates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'preview_url',
                        'category',
                        'is_premium',
                        'config',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_filter_templates_by_category(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        WallpaperTemplate::create([
            'id' => Str::uuid(),
            'name' => 'Minimal Template',
            'category' => 'minimal',
            'is_active' => true,
            'config' => [],
        ]);

        WallpaperTemplate::create([
            'id' => Str::uuid(),
            'name' => 'Artistic Template',
            'category' => 'artistic',
            'is_active' => true,
            'config' => [],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/wallpaper-templates?category=minimal');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_filter_templates_by_premium_status(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        WallpaperTemplate::create([
            'id' => Str::uuid(),
            'name' => 'Free Template',
            'is_premium' => false,
            'is_active' => true,
            'config' => [],
        ]);

        WallpaperTemplate::create([
            'id' => Str::uuid(),
            'name' => 'Premium Template',
            'is_premium' => true,
            'is_active' => true,
            'config' => [],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/wallpaper-templates?is_premium=false');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
