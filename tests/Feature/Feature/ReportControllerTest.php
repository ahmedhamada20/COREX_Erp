<?php

use App\Models\User;

it('reports index page is accessible to authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/reports')->assertOk();
});

it('balance sheet page is accessible to authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/reports/balance-sheet')
        ->assertOk();
});

it('income statement page is accessible to authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/reports/income-statement')
        ->assertOk();
});

it('trial balance page is accessible to authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/reports/trial-balance')
        ->assertOk();
});

it('reports require authentication', function (): void {
    $this->get('/reports')->assertRedirect('/login');
    $this->get('/reports/balance-sheet')->assertRedirect('/login');
    $this->get('/reports/income-statement')->assertRedirect('/login');
    $this->get('/reports/trial-balance')->assertRedirect('/login');
});
