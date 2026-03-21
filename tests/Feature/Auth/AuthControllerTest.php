<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------
    // Registration
    // ---------------------------------------------------------------

    public function test_user_can_register(): void
    {
        // Arrange
        $payload = [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
            'locale' => 'ru',
        ];

        // Act
        $response = $this->postJson('/api/auth/register', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'locale', 'email_verified_at', 'created_at'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'Test User',
            'locale' => 'ru',
        ]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertEquals('newuser@example.com', $response->json('user.email'));
    }

    public function test_register_requires_email_and_password(): void
    {
        // Arrange - пустой payload (без email и password)
        $payload = [];

        // Act
        $response = $this->postJson('/api/auth/register', $payload);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_register_requires_unique_email(): void
    {
        // Arrange
        User::factory()->create(['email' => 'existing@example.com']);

        $payload = [
            'email' => 'existing@example.com',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
        ];

        // Act
        $response = $this->postJson('/api/auth/register', $payload);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_password_confirmation(): void
    {
        // Arrange - пароли не совпадают
        $payload = [
            'email' => 'user@example.com',
            'password' => 'securepass123',
            'password_confirmation' => 'differentpass456',
        ];

        // Act
        $response = $this->postJson('/api/auth/register', $payload);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    // ---------------------------------------------------------------
    // Login
    // ---------------------------------------------------------------

    public function test_user_can_login(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $payload = [
            'email' => 'login@example.com',
            'password' => 'password',
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $payload);

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'locale', 'email_verified_at', 'created_at'],
                'token',
            ]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertEquals($user->id, $response->json('user.id'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        // Arrange
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $payload = [
            'email' => 'user@example.com',
            'password' => 'wrong_password',
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $payload);

        // Assert
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Неверный email или пароль.',
            ]);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        // Arrange - пользователь с таким email не существует
        $payload = [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $payload);

        // Assert
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Неверный email или пароль.',
            ]);
    }

    // ---------------------------------------------------------------
    // Logout
    // ---------------------------------------------------------------

    public function test_authenticated_user_can_logout(): void
    {
        // Arrange
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Act
        $response = $this->postJson('/api/auth/logout');

        // Assert
        $response->assertOk()
            ->assertJson([
                'message' => 'Вы успешно вышли из системы.',
            ]);
    }

    // ---------------------------------------------------------------
    // Profile (GET /api/me)
    // ---------------------------------------------------------------

    public function test_authenticated_user_can_get_profile(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Profile User',
            'email' => 'profile@example.com',
            'locale' => 'en',
        ]);
        Sanctum::actingAs($user);

        // Act
        $response = $this->getJson('/api/me');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'locale', 'email_verified_at', 'created_at'],
            ]);

        $this->assertEquals('Profile User', $response->json('user.name'));
        $this->assertEquals('profile@example.com', $response->json('user.email'));
        $this->assertEquals('en', $response->json('user.locale'));
    }

    // ---------------------------------------------------------------
    // Change Password
    // ---------------------------------------------------------------

    public function test_change_password_works(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => 'old_password',
        ]);
        Sanctum::actingAs($user);

        $payload = [
            'current_password' => 'old_password',
            'password' => 'new_secure_pass',
            'password_confirmation' => 'new_secure_pass',
        ];

        // Act
        $response = $this->putJson('/api/me/password', $payload);

        // Assert
        $response->assertOk()
            ->assertJson([
                'message' => 'Пароль успешно изменён.',
            ]);

        // Проверяем что новый пароль сохранён в БД
        $user->refresh();
        $this->assertTrue(Hash::check('new_secure_pass', $user->password));
    }

    public function test_change_password_fails_with_wrong_current(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => 'actual_password',
        ]);
        Sanctum::actingAs($user);

        $payload = [
            'current_password' => 'wrong_current_password',
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123',
        ];

        // Act
        $response = $this->putJson('/api/me/password', $payload);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Текущий пароль неверен.',
            ]);
    }

    // ---------------------------------------------------------------
    // Unauthenticated access
    // ---------------------------------------------------------------

    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        // Act & Assert - GET /api/me
        $this->getJson('/api/me')
            ->assertStatus(401);

        // Act & Assert - PUT /api/me
        $this->putJson('/api/me', ['name' => 'Hacker'])
            ->assertStatus(401);

        // Act & Assert - PUT /api/me/locale
        $this->putJson('/api/me/locale', ['locale' => 'en'])
            ->assertStatus(401);

        // Act & Assert - PUT /api/me/password
        $this->putJson('/api/me/password', [
            'current_password' => 'pass',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertStatus(401);

        // Act & Assert - POST /api/auth/logout
        $this->postJson('/api/auth/logout')
            ->assertStatus(401);
    }
}
