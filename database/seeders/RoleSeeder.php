<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions by module
        $permissions = [
            // Dashboard
            'view dashboard',

            // Sales
            'view sales_invoices', 'create sales_invoices', 'edit sales_invoices',
            'delete sales_invoices', 'post sales_invoices', 'cancel sales_invoices',

            // Purchases
            'view purchase_invoices', 'create purchase_invoices', 'edit purchase_invoices',
            'delete purchase_invoices', 'post purchase_invoices', 'cancel purchase_invoices',

            // Sales Returns
            'view sales_returns', 'create sales_returns', 'cancel sales_returns',

            // Purchase Returns
            'view purchase_returns', 'create purchase_returns', 'cancel purchase_returns',

            // Customers & Suppliers
            'view customers', 'create customers', 'edit customers', 'delete customers',
            'view suppliers', 'create suppliers', 'edit suppliers', 'delete suppliers',

            // Items & Inventory
            'view items', 'create items', 'edit items', 'delete items',
            'view stock_movements',

            // Accounting
            'view accounts', 'create accounts', 'edit accounts', 'delete accounts',
            'view journal_entries', 'create journal_entries',

            // Reports
            'view reports',

            // Treasury
            'view treasuries', 'create treasuries', 'edit treasuries',
            'view shifts', 'open shifts', 'close shifts',

            // Settings
            'view settings', 'edit settings',

            // Users
            'view users', 'create users', 'edit users', 'delete users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Owner: full access
        $ownerRole = Role::firstOrCreate(['name' => 'Owner']);
        $ownerRole->syncPermissions(Permission::all());

        // Accountant: accounting + reports, no settings/users
        $accountantRole = Role::firstOrCreate(['name' => 'Accountant']);
        $accountantRole->syncPermissions([
            'view dashboard',
            'view sales_invoices', 'create sales_invoices', 'edit sales_invoices', 'post sales_invoices',
            'view purchase_invoices', 'create purchase_invoices', 'edit purchase_invoices', 'post purchase_invoices',
            'view sales_returns', 'create sales_returns',
            'view purchase_returns', 'create purchase_returns',
            'view customers', 'create customers', 'edit customers',
            'view suppliers', 'create suppliers', 'edit suppliers',
            'view items', 'view stock_movements',
            'view accounts', 'create accounts', 'edit accounts',
            'view journal_entries', 'create journal_entries',
            'view reports',
            'view treasuries', 'view shifts',
        ]);

        // Cashier: sales + treasury, no accounting
        $cashierRole = Role::firstOrCreate(['name' => 'Cashier']);
        $cashierRole->syncPermissions([
            'view dashboard',
            'view sales_invoices', 'create sales_invoices',
            'view sales_returns', 'create sales_returns',
            'view customers',
            'view items',
            'view treasuries', 'view shifts', 'open shifts', 'close shifts',
        ]);

        // Viewer: read-only
        $viewerRole = Role::firstOrCreate(['name' => 'Viewer']);
        $viewerRole->syncPermissions([
            'view dashboard',
            'view sales_invoices', 'view purchase_invoices',
            'view sales_returns', 'view purchase_returns',
            'view customers', 'view suppliers',
            'view items', 'view stock_movements',
            'view accounts', 'view journal_entries',
            'view reports',
            'view treasuries', 'view shifts',
        ]);

        $this->command->info('Roles and permissions seeded successfully.');
    }
}
