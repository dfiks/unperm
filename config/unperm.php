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
        
        // Redis кеширование для битовых масок
        'driver' => env('UNPERM_CACHE_DRIVER', 'redis'), // redis, memcached, array
        
        // Кешировать агрегированные битовые маски пользователей
        'cache_user_bitmasks' => true,
        
        // Кешировать разреженные биты из БД
        'cache_sparse_bits' => true,
        
        // TTL для разных типов кеша
        'ttl_bitmask' => env('UNPERM_CACHE_TTL_BITMASK', 3600),
        'ttl_sparse_bits' => env('UNPERM_CACHE_TTL_SPARSE', 1800),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sparse Bitmask Storage
    |--------------------------------------------------------------------------
    |
    | Автоматическое интеллектуальное хранение битовых масок.
    | 
    | Режимы:
    | - 'auto' (рекомендуется): Автоматически выбирает оптимальный способ для каждой записи
    | - 'always': Всегда использовать разреженное хранение
    | - 'never': Никогда не использовать (обычное поле bitmask)
    |
    | В режиме 'auto' система анализирует:
    | - Размер битовой маски
    | - Количество установленных битов
    | - Выгоду от сжатия
    |
    | И автоматически выбирает лучший вариант для каждой записи отдельно.
    |
    */
    'sparse_storage_mode' => env('UNPERM_SPARSE_STORAGE_MODE', 'auto'),

    /*
    | Параметры автоматического режима
    */
    'sparse_storage_auto' => [
        // Минимальный размер bitmask для рассмотрения sparse storage (в байтах)
        'min_bitmask_size' => 100,
        
        // Максимальное количество битов для использования sparse storage
        // Если битов больше, используем обычное поле
        'max_bits_for_sparse' => 50,
        
        // Минимальная экономия места для использования sparse (в процентах)
        'min_savings_percent' => 30,
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
