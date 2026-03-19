<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Customer;
use App\Models\Items;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_chart_of_accounts_structure(): void
    {
        $user = \App\Models\User::factory()->create();

        $assetType = AccountType::factory()->create(['user_id' => $user->id, 'name' => 'Assets', 'normal_side' => 'debit']);
        $liabilityType = AccountType::factory()->create(['user_id' => $user->id, 'name' => 'Liabilities', 'normal_side' => 'credit']);
        $revenueType = AccountType::factory()->create(['user_id' => $user->id, 'name' => 'Revenue', 'normal_side' => 'credit']);
        $expenseType = AccountType::factory()->create(['user_id' => $user->id, 'name' => 'Expenses', 'normal_side' => 'debit']);

        $cash = Account::create([
            'user_id' => $user->id,
            'account_type_id' => $assetType->id,
            'name' => 'Cash',
            'account_number' => '101',
            'status' => true,
        ]);

        $this->assertEquals('101', $cash->account_number);
        $this->assertEquals('debit', $assetType->normal_side);
        $this->assertEquals('credit', $revenueType->normal_side);
        $this->assertTrue(true);
    }

    public function test_create_basic_data_performance(): void
    {
        $user = \App\Models\User::factory()->create();

        // item_categories.user_id NOT NULL
        $catIds = [];
        for ($i = 0; $i < 5; $i++) {
            $catIds[] = DB::table('item_categories')->insertGetId([
                'user_id' => $user->id,
                'name' => 'Category '.$i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // customers.user_id NOT NULL
        Customer::factory()->count(10)->create(['user_id' => $user->id]);

        // items without cost_price/selling_price
        Items::factory()->count(10)->create([
            'user_id' => $user->id,
            'item_category_id' => $catIds[0],
        ]);

        // suppliers.supplier_category_id NOT NULL
        $supCatId = DB::table('supplier_categories')->insertGetId([
            'user_id' => $user->id,
            'name' => 'SupCat',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Supplier::factory()->count(5)->create([
            'user_id' => $user->id,
            'supplier_category_id' => $supCatId,
        ]);

        $this->assertTrue(true);
    }
}
