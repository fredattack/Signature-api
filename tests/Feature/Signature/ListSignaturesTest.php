<?php

namespace Tests\Feature\Signature;

use App\Models\Signature;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListSignaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_list_their_signatures(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        // Create multiple signatures
        for ($i = 1; $i <= 3; $i++) {
            Signature::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'name' => "Signature {$i}",
                'image_path' => "signatures/test{$i}.png",
                'image_width' => 500,
                'image_height' => 200,
                'file_size' => 1024,
                'mime_type' => 'image/png',
            ]);
        }

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/signatures?user_id='.$user->id);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'name',
                        'description',
                        'image_url',
                        'image_path',
                        'image_width',
                        'image_height',
                        'file_size',
                        'mime_type',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_user_only_sees_their_own_signatures(): void
    {
        $user1 = User::create([
            'id' => Str::uuid(),
            'email' => 'user1@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $user2 = User::create([
            'id' => Str::uuid(),
            'email' => 'user2@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        // Create signatures for both users
        Signature::create([
            'id' => Str::uuid(),
            'user_id' => $user1->id,
            'name' => 'User 1 Signature',
            'image_path' => 'signatures/user1.png',
            'image_width' => 500,
            'image_height' => 200,
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ]);

        Signature::create([
            'id' => Str::uuid(),
            'user_id' => $user2->id,
            'name' => 'User 2 Signature',
            'image_path' => 'signatures/user2.png',
            'image_width' => 500,
            'image_height' => 200,
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user1);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/signatures?user_id='.$user1->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $responseData = $response->json('data');
        $this->assertEquals($user1->id, $responseData[0]['user_id']);
    }

    public function test_user_can_search_signatures(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        Signature::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Personal Signature',
            'image_path' => 'signatures/personal.png',
            'image_width' => 500,
            'image_height' => 200,
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ]);

        Signature::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Business Signature',
            'image_path' => 'signatures/business.png',
            'image_width' => 500,
            'image_height' => 200,
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/signatures?user_id='.$user->id.'&search=Personal');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $responseData = $response->json('data');
        $this->assertStringContainsString('Personal', $responseData[0]['name']);
    }

    public function test_user_cannot_list_signatures_without_authentication(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $response = $this->getJson('/api/v1/signatures?user_id='.$user->id);

        $response->assertStatus(401);
    }

    public function test_pagination_works_correctly(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        // Create 20 signatures
        for ($i = 1; $i <= 20; $i++) {
            Signature::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'name' => "Signature {$i}",
                'image_path' => "signatures/test{$i}.png",
                'image_width' => 500,
                'image_height' => 200,
                'file_size' => 1024,
                'mime_type' => 'image/png',
            ]);
        }

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/signatures?user_id='.$user->id.'&per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data');

        $this->assertEquals(20, $response->json('meta.total'));
        $this->assertEquals(2, $response->json('meta.last_page'));
    }
}
