<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create permissions
        $permissions = [
            // Risk permissions
            'view-risks',
            'create-risks',
            'edit-risks',
            'delete-risks',
            'manage-all-risks',
            
            // Mitigation Action permissions
            'view-mitigation-actions',
            'create-mitigation-actions',
            'edit-mitigation-actions',
            'delete-mitigation-actions',
            'assign-mitigation-actions',
            
            // Risk Assessment permissions
            'view-risk-assessments',
            'create-risk-assessments',
            'edit-risk-assessments',
            'delete-risk-assessments',
            
            // User management permissions
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            
            // Reporting permissions
            'view-reports',
            'export-reports',
            
            // System permissions
            'manage-system',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Admin role - full access
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Risk Manager role - can manage all risks and actions
        $riskManagerRole = Role::firstOrCreate(['name' => 'Risk Manager']);
        $riskManagerRole->givePermissionTo([
            'view-risks', 'create-risks', 'edit-risks', 'manage-all-risks',
            'view-mitigation-actions', 'create-mitigation-actions', 'edit-mitigation-actions', 'assign-mitigation-actions',
            'view-risk-assessments', 'create-risk-assessments', 'edit-risk-assessments',
            'view-reports', 'export-reports',
        ]);

        // Risk Owner role - can manage own risks
        $riskOwnerRole = Role::firstOrCreate(['name' => 'Risk Owner']);
        $riskOwnerRole->givePermissionTo([
            'view-risks', 'create-risks', 'edit-risks',
            'view-mitigation-actions', 'create-mitigation-actions', 'edit-mitigation-actions',
            'view-risk-assessments', 'create-risk-assessments',
            'view-reports',
        ]);

        // Auditor role - read-only access
        $auditorRole = Role::firstOrCreate(['name' => 'Auditor']);
        $auditorRole->givePermissionTo([
            'view-risks',
            'view-mitigation-actions',
            'view-risk-assessments',
            'view-reports',
            'export-reports',
        ]);
    }
}
