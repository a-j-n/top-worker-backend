<?php

use App\Models\TopPerson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('lists only pending top people for admins', function () {
    $user = User::factory()->create();
    $pendingTopPeople = TopPerson::factory()->count(2)->create([
        'is_approved' => false,
    ]);
    TopPerson::factory()->create([
        'is_approved' => true,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/admin/top-people/pending');

    $response->assertSuccessful();

    expect(collect($response->json('data'))->pluck('id')->all())
        ->toEqualCanonicalizing($pendingTopPeople->pluck('id')->all());
});

it('approves a pending top person through the admin api', function () {
    $user = User::factory()->create();
    $topPerson = TopPerson::factory()->create([
        'is_approved' => false,
    ]);

    Sanctum::actingAs($user);

    $response = $this->patchJson("/api/admin/top-people/{$topPerson->id}/approve");

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.id', $topPerson->id)
        ->assertJsonPath('data.is_approved', true);

    expect($topPerson->fresh()->is_approved)->toBeTrue();
});

it('deletes a pending top person through the existing admin delete api', function () {
    $user = User::factory()->create();
    $topPerson = TopPerson::factory()->create([
        'is_approved' => false,
    ]);

    Sanctum::actingAs($user);

    $this->deleteJson("/api/admin/top-people/{$topPerson->id}")
        ->assertNoContent();

    $this->assertModelMissing($topPerson);
});
