<?php

use App\Models\Category;
use App\Models\TopPerson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('persists and returns bio through the admin top people api', function () {
    $user = User::factory()->admin()->create();
    $category = Category::factory()->create();

    Sanctum::actingAs($user);

    $storeResponse = $this->postJson('/api/admin/top-people', [
        'name' => 'Ahmed Gamal',
        'phone' => '+201001234567',
        'bio' => 'Experienced electrical technician for homes and small businesses.',
        'category_id' => $category->id,
        'is_approved' => true,
    ]);

    $storeResponse
        ->assertCreated()
        ->assertJsonPath('data.bio', 'Experienced electrical technician for homes and small businesses.');

    $topPerson = TopPerson::query()->firstOrFail();

    expect($topPerson->bio)->toBe('Experienced electrical technician for homes and small businesses.');

    $updateResponse = $this->patchJson("/api/admin/top-people/{$topPerson->id}", [
        'bio' => null,
    ]);

    $updateResponse
        ->assertSuccessful()
        ->assertJsonPath('data.bio', null);

    expect($topPerson->fresh()->bio)->toBeNull();
});

it('accepts nullable bio on public submissions and exposes it on approved listings', function () {
    $category = Category::factory()->create([
        'name' => 'Plumbing',
        'name_ar' => 'سباكة',
        'slug' => 'plumbing',
    ]);

    $submissionResponse = $this->postJson('/api/top-people', [
        'name' => 'Sara Adel',
        'phone' => '+201009876543',
        'bio' => 'Reliable plumber focused on leak detection and fixture installation.',
        'category_id' => $category->id,
    ]);

    $submissionResponse
        ->assertCreated()
        ->assertJsonPath('data.bio', 'Reliable plumber focused on leak detection and fixture installation.')
        ->assertJsonPath('data.is_approved', false);

    TopPerson::query()->create([
        'name' => 'Mona Samir',
        'phone' => '+201000000001',
        'bio' => null,
        'category_id' => $category->id,
        'is_approved' => true,
    ]);

    TopPerson::query()->create([
        'name' => 'Karim Hassan',
        'phone' => '+201000000002',
        'bio' => 'Specialist in emergency plumbing and water heater maintenance.',
        'category_id' => $category->id,
        'is_approved' => true,
    ]);

    $indexResponse = $this->getJson('/api/top-people');

    $indexResponse
        ->assertSuccessful()
        ->assertJsonFragment([
            'name' => 'Mona Samir',
            'bio' => null,
        ])
        ->assertJsonFragment([
            'name' => 'Karim Hassan',
            'bio' => 'Specialist in emergency plumbing and water heater maintenance.',
        ]);
});
