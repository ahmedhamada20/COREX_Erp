<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            UsersSeeder::class,
            SettingSeeder::class,
            TreasurySeeder::class,
            TreasuriesDeliveriesSeeder::class,
            SalesMaterialTypesSeeder::class,
            StoresSeeder::class,
            UnitsSeeder::class,
            ItemCategoriesSeeder::class,
            ItemsTableSeeder::class,
            AccountTypesSeeder::class,
            AccountSeeder::class,
            CustomersTableSeeder::class,
            SupplierCategoriesSeeder::class,
            SuppliersTableSeeder::class,
            PurchaseInvoicesAndReturnsSeeder::class,
            PurchaseReturnItemsSeeder::class,
            UserShiftsSeeder::class,
        ]);
    }
}
