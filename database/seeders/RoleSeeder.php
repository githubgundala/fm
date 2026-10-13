<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application roles with the invoice permission matrix.
     *
     * Idempotent: safe to re-run. Permissions must exist first
     * (generated via `php artisan shield:generate --all`).
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $invoiceResources = ['Client', 'Invoice', 'Payment'];

        $toPermissions = fn (array $names): array => collect($names)
            ->unique()
            ->map(fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'])->name)
            ->all();

        $viewerPermissions = $toPermissions(
            collect($invoiceResources)
                ->flatMap(fn (string $resource): array => ["ViewAny:{$resource}", "View:{$resource}"])
                ->merge(['View:InvoiceStatsOverview', 'View:RevenueChart', 'View:OverdueInvoicesTable'])
                ->all()
        );

        $staffPermissions = $toPermissions(
            collect($viewerPermissions)
                ->merge(
                    collect($invoiceResources)->flatMap(fn (string $resource): array => [
                        "Create:{$resource}",
                        "Update:{$resource}",
                        "Replicate:{$resource}",
                    ])
                )
                ->all()
        );

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web'])
            ->syncPermissions($staffPermissions);

        Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web'])
            ->syncPermissions($viewerPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
