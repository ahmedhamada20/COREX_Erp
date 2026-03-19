<?php

use App\Models\Account;
use App\Models\AccountType;
use App\Models\FixedAsset;
use App\Models\Items;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('purchase order converts directly to purchase invoice', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $supplierCategoryId = DB::table('supplier_categories')->insertGetId([
        'user_id' => $user->id,
        'name' => 'Default',
        'status' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $supplier = Supplier::create([
        'user_id' => $user->id,
        'supplier_category_id' => $supplierCategoryId,
        'name' => 'Vendor X',
        'code' => 'S000999',
        'status' => true,
    ]);

    $itemCategoryId = DB::table('item_categories')->insertGetId([
        'user_id' => $user->id,
        'name' => 'Default Cat',
        'status' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $item = Items::create([
        'user_id' => $user->id,
        'barcode' => '1112223334445',
        'name' => 'Raw Material',
        'price' => 100,
        'nos_egomania_price' => 80,
        'egomania_price' => 90,
        'price_retail' => 100,
        'nos_gomla_price_retail' => 95,
        'gomla_price_retail' => 98,
        'type' => 'store',
        'item_category_id' => $itemCategoryId,
        'status' => true,
    ]);

    $order = PurchaseOrder::create([
        'user_id' => $user->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'PO-2026-000001',
        'order_date' => now()->toDateString(),
        'status' => 'approved',
        'subtotal' => 200,
        'tax_amount' => 0,
        'total' => 200,
    ]);

    $order->items()->create([
        'item_id' => $item->id,
        'quantity' => 2,
        'unit_price' => 100,
        'line_total' => 200,
    ]);

    $response = $this->post(route('purchase_orders.convert_to_invoice', $order));

    $response->assertRedirect();
    $this->assertDatabaseHas('purchase_invoices', [
        'user_id' => $user->id,
        'supplier_id' => $supplier->id,
        'purchase_order_id' => (string) $order->id,
        'total' => 200,
    ]);
    $this->assertDatabaseHas('purchase_orders', [
        'id' => $order->id,
        'status' => 'closed',
    ]);
});

test('cash flow statement page is reachable', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('reports.cash_flow_statement'));

    $response->assertOk();
    $response->assertSee('قائمة التدفقات النقدية');
});

test('fixed asset depreciation posts journal entry once per period', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $assetType = AccountType::create([
        'user_id' => $user->id,
        'name' => 'Assets',
        'code' => 'AST',
        'normal_side' => 'debit',
        'allow_posting' => true,
        'status' => true,
    ]);

    $expenseType = AccountType::create([
        'user_id' => $user->id,
        'name' => 'Expenses',
        'code' => 'EXP',
        'normal_side' => 'debit',
        'allow_posting' => true,
        'status' => true,
    ]);

    $assetAccount = Account::create([
        'user_id' => $user->id,
        'account_type_id' => $assetType->id,
        'name' => 'Fixed Asset Account',
        'account_number' => '1201',
        'start_balance' => 0,
        'status' => true,
    ]);

    $accumAccount = Account::create([
        'user_id' => $user->id,
        'account_type_id' => $assetType->id,
        'name' => 'Accumulated Depreciation',
        'account_number' => '1211',
        'start_balance' => 0,
        'status' => true,
    ]);

    $expenseAccount = Account::create([
        'user_id' => $user->id,
        'account_type_id' => $expenseType->id,
        'name' => 'Depreciation Expense',
        'account_number' => '6270',
        'start_balance' => 0,
        'status' => true,
    ]);

    FixedAsset::create([
        'user_id' => $user->id,
        'asset_code' => 'FA-000001',
        'name' => 'Laptop',
        'asset_account_id' => $assetAccount->id,
        'accumulated_depreciation_account_id' => $accumAccount->id,
        'depreciation_expense_account_id' => $expenseAccount->id,
        'purchase_date' => now()->subMonths(2)->toDateString(),
        'cost' => 1200,
        'salvage_value' => 0,
        'useful_life_months' => 12,
        'depreciation_start_date' => now()->subMonth()->startOfMonth()->toDateString(),
        'is_group' => false,
        'status' => true,
    ]);

    $payload = [
        'period_from' => now()->startOfMonth()->toDateString(),
        'period_to' => now()->endOfMonth()->toDateString(),
    ];

    $this->post(route('fixed_assets.run_depreciation'), $payload)->assertRedirect();
    $this->post(route('fixed_assets.run_depreciation'), $payload)->assertRedirect();

    expect(DB::table('fixed_asset_depreciations')->where('user_id', $user->id)->count())->toBe(1);
    expect(DB::table('journal_entries')->where('user_id', $user->id)->where('source', 'fixed_asset_depreciation')->count())->toBe(1);
});
