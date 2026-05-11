<?php

declare(strict_types=1);

return [
    'pages' => [
        'credits' => [
            'navigation_label' => 'Credits',
            'title' => 'Credits',
            'columns' => [
                'holder' => 'Employee',
                'status' => 'Status',
                'owner' => 'Purchased by',
                'transferred_at' => 'Distributed at',
            ],
        ],
        'edit_tenant' => [
            'label' => 'Company Settings',
            'members_heading' => 'Active Members List',
            'invite_member' => 'Invite Member',
            'deactivate' => 'Deactivate',
            'activate' => 'Activate',
            'status' => 'Status',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'member_name' => 'Name',
            'member_role' => 'Role',
            'form_name' => 'Company Name',
            'form_tax_id' => 'Tax ID',
            'form_integration_access_key' => 'Integration Access Key',
        ],
    ],
    'actions' => [
        'create_and_attach' => [
            'name' => 'Name',
            'password' => 'Password',
            'details' => 'Details',
            'cpf' => 'CPF',
            'rg' => 'RG',
            'phone' => 'Phone',
        ],
        'secret_key_rotation' => [
            'label' => 'Generate new key',
            'new_key_generated' => 'New key generated: ',
        ],
        'seats_counter' => [
            'label' => 'Seats: %s/%s',
        ],
        'logo' => [
            'label' => 'Company Logo',
            'notification' => 'Company logo changed succesfully.',
        ],
        'purchase_credits' => [
            'label' => 'Purchase Credits',
            'quantity' => 'Quantity',
        ],
        'distribute_equally' => [
            'label' => 'Equal Distribution',
        ],
        'distribute_manually' => [
            'label' => 'Distribute Credits',
            'employee' => 'Employee',
            'quantity' => 'Quantity',
        ],
        'transfer_credit' => [
            'label' => 'Transfer',
            'employee' => 'Transfer to',
        ],
    ],
];
