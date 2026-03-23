<?php

declare(strict_types=1);

return [
    'navigation_group' => [
        'billing' => 'Billing',
        'administration' => 'Administration',
        'appointments' => 'Appointments',
    ],
    'appointments' => [
        'navigation_label' => 'Appointments',
        'model_label' => 'Appointment',
        'plural_model_label' => 'Appointments',
    ],
    'consultants' => [
        'navigation_label' => 'Consultants',
        'model_label' => 'Consultant',
        'plural_model_label' => 'Consultants',
    ],
    'contractual_plans' => [
        'navigation_label' => 'Contractual Plans',
        'model_label' => 'Contractual Plan',
        'plural_model_label' => 'Contractual Plans',
        'form' => [
            'name' => 'Name',
            'slug' => 'Slug',
            'description' => 'Description',
            'type' => 'Type',
            'active' => 'Active',
        ],
        'table' => [
            'type' => 'Type',
            'name' => 'Name',
            'description' => 'Description',
            'active' => 'Active',
        ],
    ],
    'plans' => [
        'navigation_label' => 'Plans',
        'model_label' => 'Plan',
        'plural_model_label' => 'Plans',
        'behavior' => [
            'title' => 'Behavior',
            'has_generic_trial' => 'Has a generic trial period',
            'yes' => 'Yes',
            'no' => 'No',
            'trial_same_for_all' => 'The trial period will be the same for all users.',
            'trial_unique_per_user' => 'The trial period will be unique for each user.',
            'allow_promotion_codes' => 'Allow Promotion Codes',
            'promotion_can_be_applied' => 'Promotion codes can be applied to this plan.',
            'no_promotion_codes' => 'No promotion codes can be applied to this plan.',
            'collect_tax_ids' => 'Collect Tax IDs',
            'tax_ids_collected' => 'Tax IDs will be collected for this plan.',
            'tax_ids_not_collected' => 'Tax IDs will not be collected for this plan.',
        ],
        'created_date' => 'Created Date',
        'last_modified_date' => 'Last Modified Date',
    ],
    'prices' => [
        'navigation_label' => 'Prices',
        'model_label' => 'Price',
        'plural_model_label' => 'Prices',
        'sections' => [
            'plan_type' => [
                'title' => 'Plan & Type',
                'description' => 'Select the plan and define how this price should be billed.',
            ],
            'pricing' => [
                'title' => 'Pricing',
                'description' => 'Set the price amount and included usage.',
            ],
            'features' => [
                'title' => 'Features',
                'description' => 'Toggle which features are included for this price.',
            ],
            'provider' => [
                'title' => 'Provider',
                'description' => 'External payment provider references.',
            ],
            'metadata' => [
                'title' => 'Metadata',
                'description' => 'Structured JSON metadata associated with this price.',
            ],
            'auditing' => [
                'title' => 'Auditing',
                'description' => 'Automatically tracked timestamps.',
            ],
        ],
        'form' => [
            'plan' => 'Plan',
            'type_helper' => 'e.g. recurring, one_time',
            'billing_scheme_helper' => 'How the billing is calculated (per_unit, tiered, etc).',
            'tiers_mode_helper' => 'If billing is tiered, choose the mode (graduated, volume).',
            'unit_amount' => 'Unit Amount (cents)',
            'monthly_appointments' => 'Monthly Appointments',
            'monthly_appointments_helper' => 'How many appointments are included per month.',
            'active_helper' => 'Whether this price can be purchased.',
            'whatsapp_enabled' => 'WhatsApp Enabled',
            'materials_enabled' => 'Materials Enabled',
            'provider_price_id' => 'Provider Price ID',
            'provider_price_id_helper' => 'Identifier of this price on the payment provider (e.g., Stripe).',
        ],
        'created_date' => 'Created Date',
        'last_modified_date' => 'Last Modified Date',
    ],
    'companies' => [
        'navigation_label' => 'Companies',
        'model_label' => 'Company',
        'plural_model_label' => 'Companies',
        'form' => [
            'owner' => 'Owner',
            'name' => 'Name',
            'slug' => 'Slug',
            'tax_id' => 'Tax ID',
        ],
        'table' => [
            'owner' => 'Owner',
            'name' => 'Name',
            'tax_id' => 'Tax ID',
            'plan' => 'Plan',
        ],
        'relation_managers' => [
            'employees' => [
                'title' => 'Members',
                'role' => 'Role',
            ],
            'contractual_plans' => [
                'title' => 'Contractual Plans',
                'form' => [
                    'plan' => 'Company Plan',
                    'seats' => 'Seats',
                    'monthly_appointments' => 'Appointments/month per employee',
                    'status' => 'Status',
                    'overlap_error' => 'There is already an active plan with overlapping validity for this company.',
                    'starts_at' => 'Validity start',
                    'ends_at' => 'Validity end',
                    'notes' => 'Notes',
                ],
                'table' => [
                    'plan' => 'Plan',
                    'seats' => 'Seats',
                    'monthly_appointments' => 'Appointments/month',
                    'status' => 'Status',
                    'starts_at' => 'Start',
                    'ends_at' => 'End',
                ],
            ],
        ],
    ],
    'users' => [
        'navigation_label' => 'Users',
        'model_label' => 'User',
        'plural_model_label' => 'Users',
        'form' => [
            'fieldset_user' => 'User',
            'fieldset_details' => 'Details',
            'name' => 'Name',
            'email' => 'Email',
            'password' => 'Password',
            'tax_id' => 'CPF',
            'document_id' => 'RG',
            'company' => 'Company',
        ],
        'table' => [
            'name' => 'Name',
            'email' => 'Email',
            'tax_id' => 'Tax ID',
            'document_id' => 'Document ID',
        ],
    ],
    'management_cluster' => [
        'navigation_label' => 'Users Management',
    ],
    'permissions' => [
        'assign_role' => 'Assign Role',
        'user_already_has_role' => 'User already has this role',
        'user_assigned_to_role' => 'User has been assigned to %s role',
    ],
    'pages' => [
        'edit_profile' => [
            'cpf' => 'CPF',
            'rg' => 'Document ID',
        ],
    ],
];
