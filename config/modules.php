<?php

return [
    'modules' => [
        'users' => [
            'name' => 'Users Management',
            'permissions' => ['view', 'create', 'edit', 'delete'],
        ],
        'roles' => [
            'name' => 'Roles Management',
            'permissions' => ['view', 'create', 'edit', 'delete'],
        ],
        'permissions' => [
            'name' => 'Permissions Management',
            'permissions' => ['view', 'assign'],
        ],
        'dashboard' => [
            'name' => 'Dashboard',
            'permissions' => ['view'],
        ],
    ],
];
