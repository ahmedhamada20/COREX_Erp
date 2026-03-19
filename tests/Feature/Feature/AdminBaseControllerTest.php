<?php

use App\Http\Controllers\Admin\AdminBaseController;
use App\Models\User;

it('admin base controller ownerId returns users own id for owner', function (): void {
    $owner = User::factory()->create(['owner_user_id' => null]);

    // Use reflection to call protected method
    $controller = new class extends AdminBaseController {};
    $this->actingAs($owner);

    $reflection = new ReflectionMethod($controller, 'ownerId');
    $reflection->setAccessible(true);
    $result = $reflection->invoke($controller);

    expect($result)->toBe($owner->id);
});

it('admin base controller ownerId returns owner id for sub user', function (): void {
    $owner = User::factory()->create(['owner_user_id' => null]);
    $subUser = User::factory()->create(['owner_user_id' => $owner->id]);

    $controller = new class extends AdminBaseController {};
    $this->actingAs($subUser);

    $reflection = new ReflectionMethod($controller, 'ownerId');
    $reflection->setAccessible(true);
    $result = $reflection->invoke($controller);

    expect($result)->toBe($owner->id);
});

it('admin routes require authentication', function (): void {
    $routes = ['/', '/setting', '/items', '/customers', '/suppliers', '/reports'];

    foreach ($routes as $route) {
        $this->get($route)->assertRedirect('/login');
    }
});
