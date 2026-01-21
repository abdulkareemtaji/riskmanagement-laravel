<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Risk;
use App\Models\MitigationAction;
use App\Models\RiskAssessment;
use App\Models\User;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $riskManager = User::where('email', 'manager@riskmanagement.com')->first();
        $riskOwner = User::where('email', 'owner@riskmanagement.com')->first();
        $auditor = User::where('email', 'auditor@riskmanagement.com')->first();

        if (!$riskManager || !$riskOwner || !$auditor) {
            $this->command->error('Please run AdminUserSeeder first');
            return;
        }

        // Create sample risks
        $risks = [
            [
                'title' => 'Data Breach Risk',
                'description' => 'Risk of unauthorized access to customer data due to inadequate cybersecurity measures.',
                'category' => 'operational',
                'likelihood' => 4,
                'impact' => 5,
                'status' => 'assessed',
                'owner_id' => $riskOwner->id,
                'department' => 'IT',
                'identified_date' => now()->subDays(30),
                'target_closure_date' => now()->addDays(60),
                'notes' => 'High priority risk requiring immediate attention.',
            ],
            [
                'title' => 'Market Volatility Risk',
                'description' => 'Risk of financial losses due to unpredictable market conditions affecting investment portfolio.',
                'category' => 'financial',
                'likelihood' => 3,
                'impact' => 4,
                'status' => 'mitigating',
                'owner_id' => $riskManager->id,
                'department' => 'Finance',
                'identified_date' => now()->subDays(45),
                'target_closure_date' => now()->addDays(90),
                'notes' => 'Monitoring market trends closely.',
            ],
            [
                'title' => 'Regulatory Compliance Risk',
                'description' => 'Risk of non-compliance with new GDPR regulations leading to potential fines.',
                'category' => 'compliance',
                'likelihood' => 2,
                'impact' => 4,
                'status' => 'identified',
                'owner_id' => $riskOwner->id,
                'department' => 'Legal',
                'identified_date' => now()->subDays(15),
                'target_closure_date' => now()->addDays(120),
                'notes' => 'Need to review current compliance procedures.',
            ],
            [
                'title' => 'Strategic Partnership Risk',
                'description' => 'Risk of key strategic partner terminating contract affecting business operations.',
                'category' => 'strategic',
                'likelihood' => 2,
                'impact' => 3,
                'status' => 'assessed',
                'owner_id' => $riskManager->id,
                'department' => 'Business Development',
                'identified_date' => now()->subDays(20),
                'target_closure_date' => now()->addDays(180),
                'notes' => 'Diversifying partner portfolio.',
            ],
            [
                'title' => 'Brand Reputation Risk',
                'description' => 'Risk of negative publicity affecting brand reputation and customer trust.',
                'category' => 'reputational',
                'likelihood' => 3,
                'impact' => 3,
                'status' => 'mitigating',
                'owner_id' => $riskOwner->id,
                'department' => 'Marketing',
                'identified_date' => now()->subDays(10),
                'target_closure_date' => now()->addDays(45),
                'notes' => 'Implementing crisis communication plan.',
            ],
        ];

        foreach ($risks as $riskData) {
            $risk = Risk::create($riskData);

            // Create mitigation actions for each risk
            $actions = [
                [
                    'risk_id' => $risk->id,
                    'title' => 'Implement Security Measures',
                    'description' => 'Deploy advanced firewall and intrusion detection systems.',
                    'status' => 'in_progress',
                    'assigned_to' => $riskOwner->id,
                    'due_date' => now()->addDays(30),
                    'priority' => 1,
                    'cost_estimate' => 50000.00,
                    'notes' => 'High priority security implementation.',
                ],
                [
                    'risk_id' => $risk->id,
                    'title' => 'Staff Training Program',
                    'description' => 'Conduct comprehensive security awareness training for all employees.',
                    'status' => 'planned',
                    'assigned_to' => $riskManager->id,
                    'due_date' => now()->addDays(45),
                    'priority' => 2,
                    'cost_estimate' => 15000.00,
                    'notes' => 'Quarterly training sessions planned.',
                ],
            ];

            foreach ($actions as $actionData) {
                MitigationAction::create($actionData);
            }

            // Create a risk assessment
            RiskAssessment::create([
                'risk_id' => $risk->id,
                'assessor_id' => $auditor->id,
                'likelihood_before' => $risk->likelihood + 1,
                'impact_before' => $risk->impact,
                'likelihood_after' => $risk->likelihood,
                'impact_after' => $risk->impact,
                'assessment_notes' => 'Initial risk assessment completed. Mitigation measures in place.',
                'assessment_date' => now()->subDays(5),
            ]);
        }

        $this->command->info('Sample data created successfully!');
    }
}
