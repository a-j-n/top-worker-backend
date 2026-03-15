<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows api requests from any domain', function () {
    $origin = 'https://frontend.example.com';

    $this->withHeaders([
        'Origin' => $origin,
        'Access-Control-Request-Method' => 'GET',
        'Access-Control-Request-Headers' => 'Content-Type, Authorization',
    ])->options('/api/top-people')
        ->assertSuccessful()
        ->assertHeader('Access-Control-Allow-Origin', '*');

    $this->withHeaders([
        'Origin' => $origin,
    ])->getJson('/api/top-people')
        ->assertSuccessful()
        ->assertHeader('Access-Control-Allow-Origin', '*');
});
