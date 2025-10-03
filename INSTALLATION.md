# 📦 Установка UnPerm Vue 3 SPA

## Быстрый старт

### 1. Установите пакет

```bash
composer require dfiks/unperm
```

### 2. Опубликуйте конфигурацию

```bash
php artisan vendor:publish --tag=unperm-config
```

### 3. Запустите миграции

```bash
php artisan migrate
```

### 4. Синхронизируйте разрешения

```bash
php artisan unperm:sync
```

### 5. Установите npm зависимости в пакете

```bash
cd vendor/dfiks/unperm
npm install
```

### 6. Запустите для разработки

```bash
# В директории пакета
cd vendor/dfiks/unperm
npm run dev
```

Или соберите для production:

```bash
cd vendor/dfiks/unperm
npm run build
```

### 7. Откройте UI

```
http://your-app.local/unperm
```

## Development Workflow

### Вариант 1: Vite Dev Server (рекомендуется для разработки)

1. В одном терминале запустите Laravel:
```bash
php artisan serve
```

2. В другом терминале запустите Vite пакета:
```bash
cd vendor/dfiks/unperm
npm run dev
```

3. Откройте: `http://localhost:8000/unperm`

Hot reload будет работать автоматически! ✨

### Вариант 2: Production Build

```bash
cd vendor/dfiks/unperm
npm run build
```

Assets автоматически попадут в `public/build` и будут загружаться в production режиме.

## Конфигурация

### config/unperm.php

```php
return [
    // Основные настройки
    'actions' => [
        'users' => [
            'view' => 'View users',
            'create' => 'Create users',
            // ...
        ],
    ],
    
    // Суперадмины
    'superadmins' => [
        'enabled' => true,
        'models' => [
            App\Models\User::class,
        ],
    ],
    
    // Кеширование
    'cache' => [
        'enabled' => true,
        'driver' => 'redis',
    ],
];
```

## Использование в вашем проекте

### Защита роутов

```php
use DFiks\UnPerm\Traits\AuthorizesPermissions;

class PostController extends Controller
{
    use AuthorizesPermissions;

    protected function permissionRules(): array
    {
        return [
            'index' => 'posts.view',
            'store' => 'posts.create',
            'update' => 'posts.edit',
        ];
    }

    public function index()
    {
        $this->can('posts.view')->throw();
        // ...
    }
}
```

### В Blade

```blade
@if(can_permission('posts.view'))
    <a href="{{ route('posts.index') }}">Posts</a>
@endif
```

### Row-Level Permissions

```php
use DFiks\UnPerm\Traits\HasResourcePermissions;

class Folder extends Model
{
    use HasResourcePermissions;
}

// Назначить права
ResourcePermission::grant($user, $folder, 'view');

// Проверить права
if ($folder->userCan($user, 'view')) {
    // ...
}
```

## Обновление

### Обновить views

```bash
php artisan vendor:publish --tag=unperm-views --force
```

### Обновить assets

```bash
cd vendor/dfiks/unperm
npm install
npm run build
```

### Пересобрать миграции

```bash
php artisan migrate:fresh
php artisan unperm:sync
```

## Production Deployment

### 1. Соберите assets

```bash
cd vendor/dfiks/unperm
npm run build
```

### 2. Убедитесь что в .env:

```env
APP_ENV=production
VITE_DEV_SERVER_URL=
```

### 3. Deploy как обычно

Assets будут автоматически загружаться из `public/build`.

## Troubleshooting

### Vue компоненты не отображаются

1. Проверьте что Vite запущен: `npm run dev`
2. Очистите кеш: `php artisan cache:clear`
3. Проверьте console в браузере на ошибки

### Assets не загружаются

```bash
cd vendor/dfiks/unperm
rm -rf node_modules package-lock.json
npm install
npm run dev
```

### Hot reload не работает

Проверьте что:
- Vite запущен в директории пакета
- Нет конфликта портов (по умолчанию 5173)
- Разрешен CORS для localhost

## Полезные команды

```bash
# Синхронизация разрешений
php artisan unperm:sync

# Пересборка bitmask
php artisan unperm:rebuild-bitmask

# Генерация IDE helper
php artisan unperm:generate-ide-helper --meta

# Анализ bitmask
php artisan unperm:analyze-bitmask

# Список моделей
php artisan unperm:list-models
```

## Документация

- [Vue Setup](VUE_SETUP.md)
- [Row-Level Permissions](ROW_LEVEL_PERMISSIONS.md)
- [Fluent API](FLUENT_API.md)
- [Permission Gate](PERMISSION_GATE_USAGE.md)

Enjoy! 🎉
