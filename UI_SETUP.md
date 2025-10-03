# 🎨 UI Setup Guide

## Установка UI для UnPerm

### 1. Установите Livewire (если еще не установлен)

```bash
composer require livewire/livewire
```

### 2. Опубликуйте assets пакета

```bash
php artisan vendor:publish --tag=unperm-views
```

### 3. Доступ к UI

Откройте браузер и перейдите на:

```
http://your-app.test/unperm
```

## Доступные страницы

- `/unperm` - Dashboard с общей статистикой
- `/unperm/actions` - Управление Actions
- `/unperm/roles` - Управление Roles  
- `/unperm/groups` - Управление Groups
- `/unperm/users` - Назначение разрешений пользователям

## Функционал UI

### Actions Management
✅ Создание новых actions
✅ Редактирование существующих
✅ Удаление actions
✅ Поиск по названию/slug
✅ Просмотр bitmask

### Roles Management
✅ Создание ролей
✅ Назначение actions ролям
✅ Редактирование ролей
✅ Удаление ролей
✅ Автоматический пересчет bitmask

### Groups Management
✅ Создание групп
✅ Назначение roles и actions группам
✅ Иерархическая структура разрешений
✅ Автоматический пересчет bitmask

## Защита UI

Добавьте middleware для защиты роутов в `RouteServiceProvider.php`:

```php
Route::prefix('unperm')
    ->middleware(['web', 'auth', 'can:manage-permissions'])
    ->group(base_path('vendor/dfiks/unperm/routes/web.php'));
```

## Кастомизация

### Изменение стилей

После публикации views вы можете изменить их в:
```
resources/views/vendor/unperm/
```

### Изменение layout

Отредактируйте файл `layouts/app.blade.php` под свой дизайн.

### Добавление своей логики

Создайте собственные Livewire компоненты, расширяющие базовые:

```php
use DFiks\UnPerm\Http\Livewire\ManageActions;

class CustomManageActions extends ManageActions
{
    // Ваша кастомная логика
}
```

## Troubleshooting

### UI не отображается
- Убедитесь что Livewire установлен
- Очистите кеш: `php artisan view:clear`
- Проверьте что routes зарегистрированы: `php artisan route:list | grep unperm`

### Стили не применяются
- UI использует Tailwind CSS через CDN
- Для production рекомендуется собрать свои assets

### Livewire ошибки
- Убедитесь что Livewire assets загружаются: `@livewireStyles` и `@livewireScripts`
- Проверьте CSRF токен в форме

## Production

Для production рекомендуется:
1. Собрать Tailwind CSS локально
2. Минифицировать assets
3. Добавить авторизацию
4. Включить HTTPS
5. Настроить CSP headers

Enjoy! 🎉

