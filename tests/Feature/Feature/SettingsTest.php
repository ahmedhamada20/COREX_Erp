<?php

use App\Models\Settings;
use App\Models\User;

it('settings model stores erp fields correctly', function (): void {
    $user = User::factory()->create();

    $settings = Settings::create([
        'user_id' => $user->id,
        'name' => 'شركة COREX',
        'vat_number' => '123456789',
        'vat_rate' => 15.0,
        'base_currency' => 'SAR',
        'invoice_prefix' => 'INV-',
        'decimal_places' => 2,
        'enable_inventory_tracking' => true,
        'status' => true,
    ]);

    expect($settings->vat_rate)->toBe(15.0)
        ->and($settings->base_currency)->toBe('SAR')
        ->and($settings->enable_inventory_tracking)->toBeTrue()
        ->and($settings->decimal_places)->toBe(2);
});

it('settings page is accessible to authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/setting')->assertOk();
});

it('settings can be updated via post', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/setting', [
        'name' => 'اختبار الشركة',
        'vat_rate' => 15,
        'base_currency' => 'EGP',
        'decimal_places' => 2,
    ])->assertRedirect();

    expect(Settings::where('user_id', $user->id)->where('name', 'اختبار الشركة')->exists())->toBeTrue();
});
