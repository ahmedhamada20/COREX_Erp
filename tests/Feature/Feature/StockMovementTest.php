<?php

use App\Models\AuditTrail;
use App\Models\Items;
use App\Models\StockMovement;
use App\Models\Stores;
use App\Models\User;
use App\Services\Inventory\StockMovementRecorder;

it('records stock movement when created', function (): void {
    $user = User::factory()->create();
    $item = Items::factory()->create(['user_id' => $user->id]);
    $store = Stores::factory()->create(['user_id' => $user->id]);

    $recorder = new StockMovementRecorder;
    $recorder->record([
        'user_id' => $user->id,
        'item_id' => $item->id,
        'store_id' => $store->id,
        'movement_type' => 'purchase_in',
        'quantity' => 10,
        'unit_cost' => 50,
        'reference_type' => 'App\Models\PurchaseInvoice',
        'reference_id' => 1,
        'notes' => null,
    ]);

    expect(StockMovement::where('user_id', $user->id)->where('item_id', $item->id)->count())->toBe(1);
    expect(StockMovement::first()->quantity)->toBe(10.0);
    expect(StockMovement::first()->movement_type)->toBe('purchase_in');
});

it('stores absolute quantity regardless of sign', function (): void {
    $user = User::factory()->create();
    $item = Items::factory()->create(['user_id' => $user->id]);

    $recorder = new StockMovementRecorder;
    $recorder->record([
        'user_id' => $user->id,
        'item_id' => $item->id,
        'store_id' => null,
        'movement_type' => 'sales_out',
        'quantity' => -5,
        'unit_cost' => 0,
    ]);

    expect(StockMovement::first()->quantity)->toBe(5.0);
});

it('audit trail log creates a record', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    AuditTrail::log('invoice_posted', null, [], ['invoice_id' => 1]);

    expect(AuditTrail::where('user_id', $user->id)->where('action', 'invoice_posted')->count())->toBe(1);
});
