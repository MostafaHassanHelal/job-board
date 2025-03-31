<?php

return [
    'attributes' => [
        'default' => [
            'name' => 'Default Attribute',
            'type' => 'string',
        ],
        'salary' => [
            'name' => 'Salary',
            'type' => 'numeric',
        ],
        'experience' => [
            'name' => 'Experience Level',
            'type' => 'enum',
            'options' => ['Entry', 'Mid', 'Senior'],
        ],
        'remote' => [
            'name' => 'Remote Work',
            'type' => 'boolean',
        ],
    ],
];