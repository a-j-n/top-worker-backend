<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('lists users for admins', function () {
    $admin = User::factory()->admin()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
    ]);
    User::factory()->create([
        'name' => 'First User',
        'email' => 'first@example.com',
    ]);
    User::factory()->create([
        'name' => 'Second User',
        'email' => 'second@example.com',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/admin/users');

    $response->assertSuccessful();

    expect(collect($response->json('data'))->pluck('email')->all())
        ->toEqual([
            'admin@example.com',
            'first@example.com',
            'second@example.com',
        ]);
});

it('creates users through the admin api', function () {
    $admin = User::factory()->admin()->create();

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/admin/users', [
        'name' => 'New User',
        'email' => 'new-user@example.com',
        'password' => 'password123',
        'is_admin' => true,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'New User')
        ->assertJsonPath('data.email', 'new-user@example.com')
        ->assertJsonPath('data.is_admin', true);

    $user = User::query()->where('email', 'new-user@example.com')->firstOrFail();

    expect(Hash::check('password123', $user->password))->toBeTrue();
    expect($user->is_admin)->toBeTrue();
    expect($response->json('data'))->not->toHaveKey('password');
});

it('shows a single user through the admin api', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'name' => 'Shown User',
        'email' => 'shown@example.com',
    ]);

    Sanctum::actingAs($admin);

    $this->getJson("/api/admin/users/{$user->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Shown User')
        ->assertJsonPath('data.email', 'shown@example.com');
});

it('updates users through the admin api', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'name' => 'Original User',
        'email' => 'original@example.com',
        'password' => 'old-password',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->putJson("/api/admin/users/{$user->id}", [
        'name' => 'Updated User',
        'email' => 'updated@example.com',
        'password' => 'new-password123',
        'is_admin' => true,
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated User')
        ->assertJsonPath('data.email', 'updated@example.com')
        ->assertJsonPath('data.is_admin', true);

    $user->refresh();

    expect($user->name)->toBe('Updated User');
    expect($user->email)->toBe('updated@example.com');
    expect($user->is_admin)->toBeTrue();
    expect(Hash::check('new-password123', $user->password))->toBeTrue();
});

it('deletes users through the admin api', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    Sanctum::actingAs($admin);

    $this->deleteJson("/api/admin/users/{$user->id}")
        ->assertNoContent();

    $this->assertModelMissing($user);
});
