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

class UpdateSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_update_signature_name(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $signature = Signature::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Original Name',
            'image_path' => 'signatures/test.png',
            'image_width' => 500,
            'image_height' => 200,
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson("/api/v1/signatures/{$signature->id}", [
                'user_id' => $user->id,
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('signatures', [
            'id' => $signature->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_user_can_update_signature_description(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $signature = Signature::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Test Signature',
            'image_path' => 'signatures/test.png',
            'image_width' => 500,
            'image_height' => 200,
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson("/api/v1/signatures/{$signature->id}", [
                'user_id' => $user->id,
                'description' => 'New description',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('signatures', [
            'id' => $signature->id,
            'description' => 'New description',
        ]);
    }

    public function test_user_cannot_update_another_users_signature(): void
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

        $signature = Signature::create([
            'id' => Str::uuid(),
            'user_id' => $user1->id,
            'name' => 'User 1 Signature',
            'image_path' => 'signatures/test.png',
            'image_width' => 500,
            'image_height' => 200,
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user2);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson("/api/v1/signatures/{$signature->id}", [
                'user_id' => $user2->id,
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['signature_id']);
    }

    public function test_user_cannot_update_signature_without_authentication(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $signature = Signature::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Test Signature',
            'image_path' => 'signatures/test.png',
            'image_width' => 500,
            'image_height' => 200,
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ]);

        $response = $this->postJson("/api/v1/signatures/{$signature->id}", [
            'user_id' => $user->id,
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401);
    }
}
