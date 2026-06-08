<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Sales permissions
            ['name' => 'process_sales', 'display_name' => 'Process Sales', 'description' => 'Can process customer sales'],
            ['name' => 'view_own_sales', 'display_name' => 'View Own Sales', 'description' => 'Can view their own sales'],
            ['name' => 'view_all_sales', 'display_name' => 'View All Sales', 'description' => 'Can view all sales'],
            ['name' => 'process_returns', 'display_name' => 'Process Returns', 'description' => 'Can process returns and refunds'],
            ['name' => 'give_discounts', 'display_name' => 'Give Discounts', 'description' => 'Can give discounts on sales'],

            // Inventory permissions
            ['name' => 'manage_products', 'display_name' => 'Manage Products', 'description' => 'Can add/edit products'],
            ['name' => 'view_inventory', 'display_name' => 'View Inventory', 'description' => 'Can view inventory levels'],
            ['name' => 'receive_stock', 'display_name' => 'Receive Stock', 'description' => 'Can receive stock from suppliers'],
            ['name' => 'adjust_stock', 'display_name' => 'Adjust Stock', 'description' => 'Can adjust stock levels'],
            ['name' => 'perform_stock_take', 'display_name' => 'Perform Stock Take', 'description' => 'Can perform physical inventory counts'],

            // Purchase permissions
            ['name' => 'create_purchase_order', 'display_name' => 'Create Purchase Orders', 'description' => 'Can create purchase orders'],
            ['name' => 'approve_purchase_order', 'display_name' => 'Approve Purchase Orders', 'description' => 'Can approve purchase orders'],
            ['name' => 'manage_suppliers', 'display_name' => 'Manage Suppliers', 'description' => 'Can add/edit suppliers'],
            ['name' => 'record_supplier_payment', 'display_name' => 'Record Supplier Payments', 'description' => 'Can record payments to suppliers'],

            // Expense permissions
            ['name' => 'record_expense', 'display_name' => 'Record Expenses', 'description' => 'Can record business expenses'],
            ['name' => 'approve_expense', 'display_name' => 'Approve Expenses', 'description' => 'Can approve expenses'],
            ['name' => 'view_expenses', 'display_name' => 'View Expenses', 'description' => 'Can view all expenses'],

            // Shift permissions
            ['name' => 'open_shift', 'display_name' => 'Open Shift', 'description' => 'Can open a new shift'],
            ['name' => 'close_shift', 'display_name' => 'Close Shift', 'description' => 'Can close a shift'],

            // Financial reports
            ['name' => 'view_financial_reports', 'display_name' => 'View Financial Reports', 'description' => 'Can view financial reports'],
            ['name' => 'view_sales_reports', 'display_name' => 'View Sales Reports', 'description' => 'Can view sales reports'],
            ['name' => 'view_inventory_reports', 'display_name' => 'View Inventory Reports', 'description' => 'Can view inventory reports'],

            // User management
            ['name' => 'manage_users', 'display_name' => 'Manage Users', 'description' => 'Can add/edit users'],
            ['name' => 'manage_roles', 'display_name' => 'Manage Roles', 'description' => 'Can manage roles and permissions'],

            // Settings
            ['name' => 'change_settings', 'display_name' => 'Change Settings', 'description' => 'Can change system settings'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }

        // Create roles
        $owner = Role::firstOrCreate(
            ['name' => 'owner'],
            ['display_name' => 'System Owner', 'description' => 'Ultimate system authority and control']
        );

        $superAdmin = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name' => 'Super Admin', 'description' => 'Full system access']
        );

        $manager = Role::firstOrCreate(
            ['name' => 'manager'],
            ['display_name' => 'Store Manager', 'description' => 'Can manage store operations']
        );

        $cashier = Role::firstOrCreate(
            ['name' => 'cashier'],
            ['display_name' => 'Cashier', 'description' => 'Can process sales']
        );

        $inventoryManager = Role::firstOrCreate(
            ['name' => 'inventory_manager'],
            ['display_name' => 'Inventory Manager', 'description' => 'Can manage inventory']
        );

        $accountant = Role::firstOrCreate(
            ['name' => 'accountant'],
            ['display_name' => 'Accountant', 'description' => 'Can view financial reports']
        );

        // Assign all permissions to owner and super admin
        $allPermissions = Permission::all();
        $owner->permissions()->sync($allPermissions->pluck('id'));
        $superAdmin->permissions()->sync($allPermissions->pluck('id'));

        // Assign manager permissions
        $managerPermissions = Permission::whereIn('name', [
            'process_sales', 'view_all_sales', 'process_returns', 'give_discounts',
            'view_inventory', 'receive_stock', 'adjust_stock',
            'create_purchase_order', 'manage_suppliers', 'record_supplier_payment',
            'record_expense', 'approve_expense', 'view_expenses',
            'open_shift', 'close_shift',
            'view_financial_reports', 'view_sales_reports', 'view_inventory_reports',
        ])->pluck('id');
        $manager->permissions()->sync($managerPermissions);

        // Assign cashier permissions
        $cashierPermissions = Permission::whereIn('name', [
            'process_sales', 'view_own_sales', 'process_returns',
            'open_shift', 'close_shift',
        ])->pluck('id');
        $cashier->permissions()->sync($cashierPermissions);

        // Assign inventory manager permissions
        $inventoryPermissions = Permission::whereIn('name', [
            'manage_products', 'view_inventory', 'receive_stock', 'adjust_stock', 'perform_stock_take',
            'create_purchase_order', 'manage_suppliers',
            'view_inventory_reports',
        ])->pluck('id');
        $inventoryManager->permissions()->sync($inventoryPermissions);

        // Assign accountant permissions
        $accountantPermissions = Permission::whereIn('name', [
            'view_all_sales', 'view_expenses', 'view_financial_reports',
            'view_sales_reports', 'view_inventory_reports',
        ])->pluck('id');
        $accountant->permissions()->sync($accountantPermissions);
    }
}
