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

class DeleteSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_delete_signature(): void
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
            ->deleteJson("/api/v1/signatures/{$signature->id}", [
                'user_id' => $user->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Signature deleted successfully.',
            ]);

        // Should be soft deleted
        $this->assertSoftDeleted('signatures', [
            'id' => $signature->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_signature(): void
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
            ->deleteJson("/api/v1/signatures/{$signature->id}", [
                'user_id' => $user2->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['signature_id']);

        // Signature should still exist
        $this->assertDatabaseHas('signatures', [
            'id' => $signature->id,
        ]);
    }

    public function test_user_cannot_delete_signature_without_authentication(): void
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

        $response = $this->deleteJson("/api/v1/signatures/{$signature->id}", [
            'user_id' => $user->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_user_cannot_delete_nonexistent_signature(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $fakeId = Str::uuid();

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->deleteJson("/api/v1/signatures/{$fakeId}", [
                'user_id' => $user->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['signature_id']);
    }
}
