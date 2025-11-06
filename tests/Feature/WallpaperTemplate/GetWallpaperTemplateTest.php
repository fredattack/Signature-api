<?php

namespace Tests\Feature\WallpaperTemplate;

use App\Models\User;
use App\Models\WallpaperTemplate;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class GetWallpaperTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_single_template(): void
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
            'description' => 'A test template',
            'preview_url' => 'https://example.com/preview.png',
            'category' => 'minimal',
            'is_premium' => false,
            'is_active' => true,
            'config' => [
                'background_color' => '#000000',
                'signature_position' => 'bottom-right',
            ],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/wallpaper-templates/'.$template->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
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
            ])
            ->assertJson([
                'data' => [
                    'id' => $template->id,
                    'name' => 'Test Template',
                ],
            ]);
    }

    public function test_cannot_get_inactive_template(): void
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
            'name' => 'Inactive Template',
            'is_active' => false,
            'config' => [],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/wallpaper-templates/'.$template->id);

        $response->assertStatus(404);
    }
}
