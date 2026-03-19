<?php

use App\Models\User;

beforeEach(function (): void {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

it('owner user resolves tenant id to own id', function (): void {
    $owner = User::factory()->create();

    expect($owner->tenantId())->toBe($owner->id);
});

it('sub user resolves tenant id to owner id', function (): void {
    $owner = User::factory()->create();
    $subUser = User::factory()->create(['owner_user_id' => $owner->id]);

    expect($subUser->tenantId())->toBe($owner->id);
});

it('authenticated user can access dashboard', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/')->assertOk();
});

it('unauthenticated user is redirected to login', function (): void {
    $this->get('/')->assertRedirect('/login');
});

it('owner role has all permissions', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Owner');

    expect($user->hasPermissionTo('view reports'))->toBeTrue()
        ->and($user->hasPermissionTo('edit settings'))->toBeTrue()
        ->and($user->hasPermissionTo('delete items'))->toBeTrue();
});

it('cashier role cannot access accounting', function (): void {
    $cashier = User::factory()->create();
    $cashier->assignRole('Cashier');

    expect($cashier->hasPermissionTo('view accounts'))->toBeFalse()
        ->and($cashier->hasPermissionTo('view reports'))->toBeFalse()
        ->and($cashier->hasPermissionTo('edit settings'))->toBeFalse();
});

it('viewer role has only read permissions', function (): void {
    $viewer = User::factory()->create();
    $viewer->assignRole('Viewer');

    expect($viewer->hasPermissionTo('view sales_invoices'))->toBeTrue()
        ->and($viewer->hasPermissionTo('create sales_invoices'))->toBeFalse()
        ->and($viewer->hasPermissionTo('delete sales_invoices'))->toBeFalse();
});
