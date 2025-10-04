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

    /*
    | Модели пользователей для UI управления разрешениями
    |
    | Здесь можно явно указать модели, которые используют HasPermissions trait.
    | Если не указано - система автоматически найдет все модели с этим trait.
    |
    | Пример:
    | 'user_models' => [
    |     App\Models\User::class,
    |     App\Models\Admin::class,
    |     App\Models\Customer::class,
    | ],
    */
    'user_models' => [],

    /*
    |--------------------------------------------------------------------------
    | Проверка авторизации в сервисах
    |--------------------------------------------------------------------------
    |
    | Включить/выключить автоматическую проверку прав доступа в сервисах.
    | 
    | - true: сервисы будут проверять права доступа (рекомендуется для production)
    | - false: проверка авторизации отключена (удобно для тестов и разработки)
    |
    | Можно переопределить через: UNPERM_SERVICE_AUTHORIZATION_ENABLED=true
    |
    */
    'service_authorization_enabled' => env('UNPERM_SERVICE_AUTHORIZATION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Суперадминистраторы
    |--------------------------------------------------------------------------
    |
    | Определите пользователей, которые имеют полный доступ ко всем разрешениям.
    | Поддерживаются следующие варианты:
    |
    | 1. По моделям - все пользователи этих моделей имеют полный доступ:
    |    'models' => [App\Models\User::class]
    |
    | 2. По конкретным ID - пользователи с этими ID (для любой модели):
    |    'ids' => [1, 2, 3]
    |
    | 3. По email/username:
    |    'emails' => ['admin@example.com', 'superadmin@example.com']
    |
    | 4. По методу в модели - вызывается метод в модели пользователя:
    |    'check_method' => 'isSuperAdmin' (должен вернуть bool)
    |
    | 5. По action - если пользователь имеет этот action:
    |    'action' => 'superadmin'
    |
    | 6. По кастомному callback:
    |    'callback' => fn($user) => $user->hasRole('god-mode')
    |
    */
    'superadmins' => [
        'enabled' => env('UNPERM_SUPERADMIN_ENABLED', true),
        
        // Все пользователи этих моделей - суперадмины
        'models' => [
            // App\Models\User::class,
        ],

        // Конкретные ID пользователей (работает для любой модели)
        'ids' => [
            // 1, 2, 3
        ],

        // Email-адреса суперадминов
        'emails' => [
            // 'admin@example.com',
        ],

        // Имена пользователей (username поле)
        'usernames' => [
            // 'superadmin',
        ],

        // Название метода в модели пользователя для проверки
        'check_method' => null, // например 'isSuperAdmin'

        // Action который дает полный доступ
        'action' => 'superadmin',

        // Кастомный callback для проверки
        'callback' => null,
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
