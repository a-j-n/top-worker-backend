<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('rejects non-admin credentials on the admin login endpoint', function () {
    $user = User::factory()->create([
        'email' => 'member@example.com',
    ]);

    $response = $this->postJson('/api/admin/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect($user->tokens()->count())->toBe(0);
});

it('allows admin credentials on the admin login endpoint', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin@example.com',
    ]);

    $response = $this->postJson('/api/admin/login', [
        'email' => $admin->email,
        'password' => 'password',
        'device_name' => 'Pest',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.email', 'admin@example.com')
        ->assertJsonPath('user.is_admin', true);

    expect($admin->tokens()->count())->toBe(1);
});

it('forbids non-admin users from protected admin routes', function (string $uri) {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson($uri)->assertForbidden();
})->with([
    'admin me' => '/api/admin/me',
    'admin users index' => '/api/admin/users',
    'admin pending top people' => '/api/admin/top-people/pending',
]);
