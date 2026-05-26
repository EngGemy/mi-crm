<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // إفراغ cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Quotations
            'quotations.view_any', 'quotations.view', 'quotations.view_own',
            'quotations.create', 'quotations.update', 'quotations.update_own',
            'quotations.delete', 'quotations.send', 'quotations.approve',
            'quotations.convert', 'quotations.preview_pdf', 'quotations.download_pdf',

            // Contracts
            'contracts.view_any', 'contracts.view', 'contracts.create',
            'contracts.update', 'contracts.delete',
            'contracts.preview_pdf', 'contracts.download_pdf',

            // Customers
            'customers.view_any', 'customers.view', 'customers.create',
            'customers.update', 'customers.delete',

            // Payments
            'payments.view_any', 'payments.view', 'payments.create',
            'payments.update', 'payments.delete',

            // Products
            'products.view_any', 'products.view', 'products.create',
            'products.update', 'products.delete',

            // Settings
            'settings.view', 'settings.update',

            // Leads
            'leads.view_any', 'leads.view', 'leads.view_own',
            'leads.create', 'leads.update', 'leads.update_own',
            'leads.delete', 'leads.convert',

            // Reports
            'reports.view_sales', 'reports.view_financial', 'reports.view_operations',

            // Users
            'users.view_any', 'users.view', 'users.create', 'users.update',
            'users.delete', 'users.assign_roles',

            // Audit
            'audit.view',

            // Exhibitions
            'exhibitions.view_any', 'exhibitions.view', 'exhibitions.create',
            'exhibitions.update', 'exhibitions.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // === Super Admin ===
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // === Admin ===
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo(array_diff($permissions, [
            'users.create', 'users.delete', 'users.assign_roles',
            'settings.update',
        ]));
        $admin->givePermissionTo('audit.view');

        // === Sales Manager ===
        $salesManager = Role::firstOrCreate(['name' => 'sales_manager']);
        $salesManager->syncPermissions([]);  // reset then re-assign below
        $salesManager->givePermissionTo([
            // Quotations - الكل
            'quotations.view_any', 'quotations.view', 'quotations.create',
            'quotations.update', 'quotations.delete', 'quotations.send',
            'quotations.approve', 'quotations.convert',
            'quotations.preview_pdf', 'quotations.download_pdf',
            // Leads - الكل
            'leads.view_any', 'leads.view', 'leads.create',
            'leads.update', 'leads.delete', 'leads.convert',
            // Contracts - read only
            'contracts.view_any', 'contracts.view', 'contracts.preview_pdf',
            // Customers - الكل
            'customers.view_any', 'customers.view', 'customers.create',
            'customers.update',
            // Payments - read only
            'payments.view_any', 'payments.view',
            // Products - read only
            'products.view_any', 'products.view',
            // Reports
            'reports.view_sales',
            // Exhibitions
            'exhibitions.view_any', 'exhibitions.view', 'exhibitions.create',
            'exhibitions.update', 'exhibitions.delete',
            // Audit
            'audit.view',
        ]);

        // === Sales Rep (المهم) ===
        $salesRep = Role::firstOrCreate(['name' => 'sales_rep']);
        $salesRep->givePermissionTo([
            // Quotations - own only
            'quotations.view_own', 'quotations.create',
            'quotations.update_own', 'quotations.send',
            'quotations.preview_pdf', 'quotations.download_pdf',
            // Leads - own only
            'leads.view_own', 'leads.create',
            'leads.update_own', 'leads.convert',
            // Customers - view + create
            'customers.view_any', 'customers.view', 'customers.create',
            'customers.update',
            // Products - view only (لاختيار البنود)
            'products.view_any', 'products.view',
        ]);

        // === Accountant ===
        $accountant = Role::firstOrCreate(['name' => 'accountant']);
        $accountant->givePermissionTo([
            'quotations.view_any', 'quotations.view',
            'contracts.view_any', 'contracts.view',
            'customers.view_any', 'customers.view',
            'payments.view_any', 'payments.view', 'payments.create',
            'payments.update', 'payments.delete',
            'reports.view_financial',
        ]);
    }
}
