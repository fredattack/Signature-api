<?php

namespace Tests\Feature\Signature;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_create_signature(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $file = UploadedFile::fake()->image('signature.png', 500, 200);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson('/api/v1/signatures', [
                'user_id' => $user->id,
                'name' => 'My Signature',
                'description' => 'Test signature',
                'image' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
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
            ]);

        $this->assertDatabaseHas('signatures', [
            'user_id' => $user->id,
            'name' => 'My Signature',
            'description' => 'Test signature',
        ]);
    }

    public function test_user_cannot_create_signature_without_authentication(): void
    {
        $file = UploadedFile::fake()->image('signature.png');

        $response = $this->postJson('/api/v1/signatures', [
            'user_id' => Str::uuid(),
            'name' => 'My Signature',
            'image' => $file,
        ]);

        $response->assertStatus(401);
    }

    public function test_user_cannot_create_signature_without_name(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $file = UploadedFile::fake()->image('signature.png');

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson('/api/v1/signatures', [
                'user_id' => $user->id,
                'image' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_cannot_create_signature_without_image(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson('/api/v1/signatures', [
                'user_id' => $user->id,
                'name' => 'My Signature',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_user_cannot_create_signature_with_invalid_image_type(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson('/api/v1/signatures', [
                'user_id' => $user->id,
                'name' => 'My Signature',
                'image' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }
}
