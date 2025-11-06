<?php

namespace Tests\Unit\Actions\Auth;

use App\Actions\Auth\RegisterUserAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterUserActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_creates_user_and_returns_tokens(): void
    {
        $action = app(RegisterUserAction::class);

        $result = $action->execute([
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('expires_in', $result);

        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertEquals('test@example.com', $result['user']->email);
        $this->assertEquals('John', $result['user']->first_name);
        $this->assertEquals('Doe', $result['user']->last_name);
        $this->assertFalse($result['user']->is_premium);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_execute_creates_refresh_token_session(): void
    {
        $action = app(RegisterUserAction::class);

        $result = $action->execute([
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $this->assertDatabaseHas('sessions', [
            'user_id' => $result['user']->id,
        ]);
    }
}
