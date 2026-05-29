<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('404 page renders branded error view for unknown routes', function (): void {
    $this->get('/this-route-does-not-exist')
        ->assertStatus(404)
        ->assertSee('SIGNAL LOST')
        ->assertSee('This page slipped into the void.');
});

test('403 page renders branded error view for unauthorized panel access', function (): void {
    $employee = User::factory()->create();

    $this->actingAs($employee)
        ->get('/admin')
        ->assertStatus(403)
        ->assertSee('ACCESS DENIED')
        ->assertSee("You don't have clearance.");
});
