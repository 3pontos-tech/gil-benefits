<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use TresPontosTech\Consultants\Models\Consultant;

class ConsultantModuleSeeder extends Seeder
{
    /**
     * Seed the consultant module with test data.
     */
    public function run(): void
    {
        $this->command->info('Seeding Consultant module test data...');

        $consultants = [
            [
                'name' => 'Dr. Sarah Johnson',
                'slug' => 'dr-sarah-johnson',
                'email' => 'sarah.johnson@consultants.test',
                'phone' => '+55 11 98888-0001',
                'short_description' => 'Senior Business Strategy Consultant',
                'biography' => 'Dr. Sarah Johnson brings over 20 years of experience in business strategy and organizational transformation. She has worked with Fortune 500 companies across various industries, helping them navigate complex business challenges and achieve sustainable growth.',
                'readme' => 'Working with Dr. Johnson:\n\n• Schedule meetings at least 48 hours in advance\n• Prepare detailed agenda and background materials\n• Be ready to discuss specific business metrics and KPIs\n• Follow-up sessions are recommended for complex projects',
                'external_id' => 'HL_CONSULTANT_001',
                'socials_urls' => json_encode([
                    'linkedin' => 'https://linkedin.com/in/dr-sarah-johnson',
                    'twitter' => 'https://twitter.com/drsarahjohnson',
                    'website' => 'https://sarahjohnson.consulting',
                ]),
            ],
            [
                'name' => 'Marcus Chen',
                'slug' => 'marcus-chen',
                'email' => 'marcus.chen@consultants.test',
                'phone' => '+55 11 98888-0002',
                'short_description' => 'Technology & Digital Transformation Expert',
                'biography' => 'Marcus Chen specializes in digital transformation and technology strategy. With a background in software engineering and an MBA in Technology Management, he helps organizations leverage technology to improve efficiency and competitiveness.',
                'readme' => 'Working with Marcus:\n\n• Technical discussions require system architecture diagrams\n• Prefer video calls for complex technical explanations\n• Provide access to relevant systems for assessment\n• Implementation roadmaps are delivered within 5 business days',
                'external_id' => 'HL_CONSULTANT_002',
                'socials_urls' => json_encode([
                    'linkedin' => 'https://linkedin.com/in/marcus-chen-tech',
                    'github' => 'https://github.com/marcuschen',
                    'medium' => 'https://medium.com/@marcuschen',
                ]),
            ],
            [
                'name' => 'Isabella Rodriguez',
                'slug' => 'isabella-rodriguez',
                'email' => 'isabella.rodriguez@consultants.test',
                'phone' => '+55 11 98888-0003',
                'short_description' => 'Human Resources & Organizational Development',
                'biography' => 'Isabella Rodriguez is a certified HR professional with expertise in organizational development, talent management, and workplace culture transformation. She has helped numerous companies build high-performing teams and improve employee engagement.',
                'readme' => 'Working with Isabella:\n\n• Employee surveys and assessments are conducted confidentially\n• Regular check-ins are scheduled throughout the engagement\n• Cultural change initiatives require 6-12 month commitments\n• All recommendations are backed by data and best practices',
                'external_id' => 'HL_CONSULTANT_003',
                'socials_urls' => json_encode([
                    'linkedin' => 'https://linkedin.com/in/isabella-rodriguez-hr',
                    'twitter' => 'https://twitter.com/isabellahrpro',
                ]),
            ],
            [
                'name' => 'David Thompson',
                'slug' => 'david-thompson',
                'email' => 'david.thompson@consultants.test',
                'phone' => '+55 11 98888-0004',
                'short_description' => 'Financial Planning & Risk Management',
                'biography' => 'David Thompson is a CPA and financial consultant with extensive experience in financial planning, risk assessment, and investment strategy. He helps businesses optimize their financial performance and manage financial risks effectively.',
                'readme' => 'Working with David:\n\n• Financial statements and records must be current and accurate\n• Risk assessments include comprehensive market analysis\n• Recommendations include implementation timelines and ROI projections\n• Quarterly reviews are recommended for ongoing engagements',
                'external_id' => 'HL_CONSULTANT_004',
                'socials_urls' => json_encode([
                    'linkedin' => 'https://linkedin.com/in/david-thompson-cpa',
                    'website' => 'https://davidthompsonfinancial.com',
                ]),
            ],
            [
                'name' => 'Ana Paula Silva',
                'slug' => 'ana-paula-silva',
                'email' => 'ana.silva@consultants.test',
                'phone' => '+55 11 98888-0005',
                'short_description' => 'Marketing & Brand Strategy Specialist',
                'biography' => 'Ana Paula Silva is a marketing strategist with a focus on brand development and digital marketing. She has worked with startups and established companies to build strong brand identities and effective marketing campaigns.',
                'readme' => 'Working with Ana Paula:\n\n• Brand audits include competitor analysis and market research\n• Creative briefs require detailed target audience profiles\n• Campaign launches include performance tracking and optimization\n• Monthly performance reviews are standard for ongoing projects',
                'external_id' => 'HL_CONSULTANT_005',
                'socials_urls' => json_encode([
                    'linkedin' => 'https://linkedin.com/in/ana-paula-silva-marketing',
                    'instagram' => 'https://instagram.com/anapaulamarketing',
                    'behance' => 'https://behance.net/anapaulasilva',
                ]),
            ],
        ];

        foreach ($consultants as $consultantData) {
            Consultant::factory()->create($consultantData);
        }

        // Create a soft-deleted consultant for testing
        Consultant::factory()->create([
            'name' => 'Former Consultant',
            'slug' => 'former-consultant',
            'email' => 'former@consultants.test',
            'phone' => '+55 11 98888-0099',
            'short_description' => 'No longer active',
            'biography' => 'This consultant is no longer active.',
            'readme' => 'This consultant is no longer available.',
            'external_id' => 'HL_CONSULTANT_DELETED',
            'socials_urls' => json_encode([]),
            'deleted_at' => now(),
        ]);

        $this->command->info('Consultant module test data created successfully!');
    }
};