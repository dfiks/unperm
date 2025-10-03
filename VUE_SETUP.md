# 🚀 Vue 3 SPA Setup для UnPerm

## Установка

### 1. Установите npm зависимости в пакете

```bash
cd vendor/dfiks/unperm
npm install
```

### 2. Запустите dev server (для разработки)

```bash
npm run dev
```

### 3. Или соберите для production

```bash
npm run build
```

## Использование в проекте

### Вариант А: Development (с hot reload)

1. В пакете запустите:
```bash
cd vendor/dfiks/unperm
npm run dev
```

2. В вашем `resources/views` создайте роут:
```php
// routes/web.php
Route::get('/unperm/{any?}', function () {
    return view('unperm::spa');
})->where('any', '.*');
```

3. Откройте: `http://your-app.local/unperm`

### Вариант Б: Production (скомпилированные assets)

1. Соберите assets в пакете:
```bash
cd vendor/dfiks/unperm
npm run build
```

2. Опубликуйте assets в ваш проект:
```bash
php artisan vendor:publish --tag=unperm-assets --force
```

3. В `vite.config.js` вашего проекта добавьте:
```js
export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        // UnPerm assets
        'vendor/unperm/resources/css/app.css',
        'vendor/unperm/resources/js/app.js',
      ],
      refresh: true,
    }),
  ],
})
```

### Вариант В: Standalone режим (рекомендуется)

UnPerm использует свой собственный Vite, не трогая ваш основной проект:

1. В пакете запустите Vite:
```bash
cd vendor/dfiks/unperm
npm run dev
```

2. Роут уже зарегистрирован автоматически: `/unperm`

3. Assets загружаются автоматически через `@vite` в blade

## API Endpoints

Все API endpoints доступны по префиксу `/unperm/api`:

- `GET /unperm/api/actions` - список actions
- `POST /unperm/api/actions` - создать action
- `GET /unperm/api/roles` - список roles
- `GET /unperm/api/users` - список users
- etc.

## Структура проекта

```
vendor/dfiks/unperm/
├── resources/
│   ├── js/
│   │   ├── App.vue              # Главный компонент
│   │   ├── app.js               # Entry point
│   │   ├── router/
│   │   │   └── index.js         # Vue Router
│   │   ├── stores/
│   │   │   └── notification.js  # Pinia store
│   │   ├── api/
│   │   │   └── client.js        # Axios client
│   │   ├── components/
│   │   │   └── layout/
│   │   │       ├── Sidebar.vue
│   │   │       └── Notifications.vue
│   │   └── views/
│   │       ├── Dashboard.vue
│   │       ├── Actions.vue
│   │       ├── Roles.vue
│   │       └── ...
│   ├── css/
│   │   └── app.css              # Tailwind styles
│   └── views/
│       └── spa.blade.php        # SPA entry blade
├── package.json
├── vite.config.js
└── tailwind.config.js
```

## Разработка

### Добавить новую страницу

1. Создайте компонент:
```bash
touch vendor/dfiks/unperm/resources/js/views/MyPage.vue
```

2. Добавьте роут в `resources/js/router/index.js`:
```js
{
  path: '/my-page',
  name: 'MyPage',
  component: () => import('../views/MyPage.vue'),
  meta: { title: 'My Page' }
}
```

3. Добавьте в Sidebar (опционально)

### Использовать API

```vue
<script setup>
import { ref, onMounted } from 'vue'
import client from '@/api/client'

const data = ref([])

onMounted(async () => {
  const response = await client.get('/actions')
  data.value = response.data
})
</script>
```

### Показать уведомление

```vue
<script setup>
import { useNotificationStore } from '@/stores/notification'

const notification = useNotificationStore()

const save = async () => {
  try {
    await client.post('/actions', form)
    notification.success('Сохранено!')
  } catch (error) {
    notification.error('Ошибка!')
  }
}
</script>
```

## Troubleshooting

### Vite не запускается

```bash
# Очистите кеш
rm -rf node_modules package-lock.json
npm install
```

### Assets не загружаются

Убедитесь что:
1. Vite запущен: `npm run dev`
2. В .env есть: `VITE_DEV_SERVER_URL=http://localhost:5173`
3. Роут `/unperm` доступен

### Hot reload не работает

Проверьте что в `vite.config.js` правильный HMR host:
```js
server: {
  hmr: {
    host: 'localhost',
  },
}
```

## Production Deployment

1. Соберите assets:
```bash
cd vendor/dfiks/unperm
npm run build
```

2. Assets автоматически попадут в `public/build`

3. В production `@vite` автоматически использует скомпилированные файлы

## Дополнительно

### Использовать свой Tailwind

Если у вас уже есть Tailwind в проекте, UnPerm использует свою отдельную сборку и не конфликтует.

### Интеграция с Laravel Sanctum

Для API authentication добавьте middleware:

```php
Route::middleware(['api', 'auth:sanctum'])->group(function () {
    Route::prefix('unperm/api')->group(function () {
        // API routes
    });
});
```

Enjoy! 🎉

