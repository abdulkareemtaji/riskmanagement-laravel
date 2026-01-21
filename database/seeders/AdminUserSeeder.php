<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@riskmanagement.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'department' => 'IT',
                'position' => 'System Administrator',
                'is_active' => true,
            ]
        );
        
        $admin->assignRole('Admin');

        // Create sample Risk Manager
        $riskManager = User::firstOrCreate(
            ['email' => 'manager@riskmanagement.com'],
            [
                'name' => 'Risk Manager',
                'password' => Hash::make('manager123'),
                'department' => 'Risk Management',
                'position' => 'Risk Manager',
                'is_active' => true,
            ]
        );
        
        $riskManager->assignRole('Risk Manager');

        // Create sample Risk Owner
        $riskOwner = User::firstOrCreate(
            ['email' => 'owner@riskmanagement.com'],
            [
                'name' => 'Risk Owner',
                'password' => Hash::make('owner123'),
                'department' => 'Operations',
                'position' => 'Operations Manager',
                'is_active' => true,
            ]
        );
        
        $riskOwner->assignRole('Risk Owner');

        // Create sample Auditor
        $auditor = User::firstOrCreate(
            ['email' => 'auditor@riskmanagement.com'],
            [
                'name' => 'Internal Auditor',
                'password' => Hash::make('auditor123'),
                'department' => 'Audit',
                'position' => 'Senior Auditor',
                'is_active' => true,
            ]
        );
        
        $auditor->assignRole('Auditor');
    }
}
