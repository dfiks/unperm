<?php

return [
    'tables' => [
        'actions' => 'actions',
        'roles' => 'roles',
        'groups' => 'groups',
        'roles_action' => 'roles_action',
        'groups_action' => 'groups_action',
        'groups_roles' => 'groups_roles',
        'model_actions' => 'model_actions',
        'model_roles' => 'model_roles',
        'model_groups' => 'model_groups',
    ],

    'models' => [
        'action' => DFiks\UnPerm\Models\Action::class,
        'role' => DFiks\UnPerm\Models\Role::class,
        'group' => DFiks\UnPerm\Models\Group::class,
    ],

    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'prefix' => 'unperm',
    ],

    'actions' => [
        'users' => [
            'view' => 'View users',
            'create' => 'Create users',
            'edit' => 'Edit users',
            'delete' => 'Delete users',
        ],
        'posts' => [
            'view' => 'View posts',
            'create' => 'Create posts',
            'edit' => 'Edit posts',
            'delete' => 'Delete posts',
            'publish' => 'Publish posts',
        ],
        'comments' => [
            'view' => 'View comments',
            'create' => 'Create comments',
            'edit' => 'Edit comments',
            'delete' => 'Delete comments',
            'moderate' => 'Moderate comments',
        ],
    ],

    'roles' => [
        'admin' => [
            'name' => 'Administrator',
            'description' => 'Full system access',
            'actions' => ['users.*', 'posts.*', 'comments.*'],
        ],
        'editor' => [
            'name' => 'Editor',
            'description' => 'Content editor',
            'actions' => ['posts.view', 'posts.create', 'posts.edit', 'comments.moderate'],
        ],
        'viewer' => [
            'name' => 'Viewer',
            'description' => 'Read-only access',
            'actions' => ['*.view'],
        ],
    ],

    'groups' => [
        'content-team' => [
            'name' => 'Content Team',
            'description' => 'Content management team',
            'roles' => ['editor'],
            'actions' => ['comments.view'],
        ],
        'management' => [
            'name' => 'Management',
            'description' => 'Management team',
            'roles' => ['admin', 'editor'],
            'actions' => [],
        ],
    ],
];
