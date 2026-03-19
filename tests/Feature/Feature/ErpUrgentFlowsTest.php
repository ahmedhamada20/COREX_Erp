<?php

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Items;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\AccountTypesSeeder;
use Illuminate\Support\Facades\DB;

test('account types seeder uses normalized types', function () {
    $user = User::factory()->create([
        'owner_user_id' => null,
    ]);

    $this->seed(AccountTypesSeeder::class);

    $codes = DB::table('account_types')
        ->where('user_id', $user->id)
        ->pluck('code')
        ->all();

    expect($codes)->toContain('COG');
    expect($codes)->not()->toContain('CLT');
});

test('manual journal entry can be posted and appears in account statement', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $assetType = AccountType::create([
        'user_id' => $user->id,
        'name' => 'الأصول',
        'code' => 'AST',
        'normal_side' => 'debit',
        'allow_posting' => true,
        'status' => true,
    ]);

    $equityType = AccountType::create([
        'user_id' => $user->id,
        'name' => 'حقوق الملكية',
        'code' => 'EQT',
        'normal_side' => 'credit',
        'allow_posting' => true,
        'status' => true,
    ]);

    $cash = Account::create([
        'user_id' => $user->id,
        'account_type_id' => $assetType->id,
        'name' => 'Cash',
        'account_number' => '1101',
        'start_balance' => 0,
        'status' => true,
    ]);

    $capital = Account::create([
        'user_id' => $user->id,
        'account_type_id' => $equityType->id,
        'name' => 'Capital',
        'account_number' => '3100',
        'start_balance' => 0,
        'status' => true,
    ]);

    $response = $this->post(route('journal_entries.store'), [
        'entry_date' => now()->toDateString(),
        'description' => 'Opening manual entry',
        'lines' => [
            ['account_id' => $cash->id, 'debit' => 1000, 'credit' => 0],
            ['account_id' => $capital->id, 'debit' => 0, 'credit' => 1000],
        ],
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('journal_entries', [
        'user_id' => $user->id,
        'source' => 'manual',
        'description' => 'Opening manual entry',
    ]);

    $statement = $this->get(route('reports.account_statement', [
        'from_date' => now()->subDay()->toDateString(),
        'to_date' => now()->addDay()->toDateString(),
        'account_id' => $cash->id,
    ]));

    $statement->assertOk();
    $statement->assertSee('Opening manual entry');
});

test('purchase order can be created', function () {
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
        'name' => 'Vendor 1',
        'code' => 'S000001',
        'status' => true,
    ]);

    $itemCategoryId = DB::table('item_categories')->insertGetId([
        'user_id' => $user->id,
        'name' => 'Cat',
        'status' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $item = Items::create([
        'user_id' => $user->id,
        'barcode' => '1234567890123',
        'name' => 'Item A',
        'price' => 20,
        'nos_egomania_price' => 10,
        'egomania_price' => 12,
        'price_retail' => 22,
        'nos_gomla_price_retail' => 18,
        'gomla_price_retail' => 19,
        'type' => 'store',
        'item_category_id' => $itemCategoryId,
        'status' => true,
    ]);

    $response = $this->post(route('purchase_orders.store'), [
        'supplier_id' => $supplier->id,
        'order_date' => now()->toDateString(),
        'tax_amount' => 5,
        'items' => [
            ['item_id' => $item->id, 'quantity' => 2, 'unit_price' => 10],
        ],
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('purchase_orders', [
        'user_id' => $user->id,
        'supplier_id' => $supplier->id,
        'total' => 25.00,
    ]);
});

test('stock adjustment posts stock movements', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $itemCategoryId = DB::table('item_categories')->insertGetId([
        'user_id' => $user->id,
        'name' => 'Cat',
        'status' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $storeId = DB::table('stores')->insertGetId([
        'user_id' => $user->id,
        'name' => 'Main Store',
        'status' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $item = Items::create([
        'user_id' => $user->id,
        'barcode' => '9876543210123',
        'name' => 'Item B',
        'price' => 30,
        'nos_egomania_price' => 14,
        'egomania_price' => 16,
        'price_retail' => 31,
        'nos_gomla_price_retail' => 28,
        'gomla_price_retail' => 29,
        'type' => 'store',
        'item_category_id' => $itemCategoryId,
        'status' => true,
    ]);

    $response = $this->post(route('stock_adjustments.store'), [
        'adjustment_date' => now()->toDateString(),
        'lines' => [
            [
                'item_id' => $item->id,
                'store_id' => $storeId,
                'quantity_diff' => -3,
                'unit_cost' => 10,
            ],
        ],
    ]);

    $response->assertRedirect();

    expect(StockMovement::query()->where('user_id', $user->id)->count())->toBe(1);
    $this->assertDatabaseHas('stock_movements', [
        'user_id' => $user->id,
        'item_id' => $item->id,
        'store_id' => $storeId,
        'movement_type' => 'adjustment_out',
    ]);
});
