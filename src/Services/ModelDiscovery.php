<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Services;

use DFiks\UnPerm\Traits\HasPermissions;
use DFiks\UnPerm\Traits\HasResourcePermissions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use Throwable;

/**
 * Сервис для автоматического обнаружения моделей с HasPermissions trait.
 */
class ModelDiscovery
{
    protected array $cache = [];
    protected array $resourceCache = [];

    /**
     * Найти все модели, использующие HasPermissions trait.
     *
     * @return array<string, array{class: string, name: string, table: string}>
     */
    public function findModelsWithPermissions(): array
    {
        if (!empty($this->cache)) {
            return $this->cache;
        }

        $models = [];

        // 1. Проверяем явно указанные модели в конфиге
        $configModels = config('unperm.user_models', []);
        foreach ($configModels as $modelClass) {
            if (class_exists($modelClass)) {
                $model = $this->checkModelHasPermissions($modelClass);
                if ($model) {
                    $models[$modelClass] = $model;
                }
            }
        }

        // 2. Ищем во всех стандартных местах
        $searchPaths = [
            app_path('Models'),           // Laravel 8+
            app_path(),                   // Laravel < 8
            base_path('app/Models'),      // На всякий случай
        ];

        foreach ($searchPaths as $path) {
            if (File::exists($path)) {
                $foundModels = $this->scanDirectory($path);
                $models = array_merge($models, $foundModels);
            }
        }

        // 3. Ищем используя composer classmap
        $composerModels = $this->findModelsFromComposer();
        $models = array_merge($models, $composerModels);

        $this->cache = $models;

        return $models;
    }

    /**
     * Сканировать директорию на наличие моделей.
     */
    protected function scanDirectory(string $path): array
    {
        $models = [];

        if (!File::exists($path)) {
            return $models;
        }

        $files = File::allFiles($path);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->getClassNameFromFile($file->getPathname());

            if (!$className || !class_exists($className)) {
                continue;
            }

            $model = $this->checkModelHasPermissions($className);
            if ($model) {
                $models[$className] = $model;
            }
        }

        return $models;
    }

    /**
     * Найти модели используя composer autoload.
     */
    protected function findModelsFromComposer(): array
    {
        $models = [];

        try {
            $composerPath = base_path('vendor/composer/autoload_classmap.php');
            if (!file_exists($composerPath)) {
                return $models;
            }

            $classMap = require $composerPath;

            foreach ($classMap as $className => $filePath) {
                // Пропускаем vendor классы и тестовые классы
                if (str_contains($className, 'vendor\\') ||
                    str_contains($className, 'Test') ||
                    str_contains($filePath, '/vendor/') ||
                    str_contains($filePath, '/tests/')) {
                    continue;
                }

                if (!class_exists($className)) {
                    continue;
                }

                $model = $this->checkModelHasPermissions($className);
                if ($model) {
                    $models[$className] = $model;
                }
            }
        } catch (Throwable $e) {
            // Игнорируем ошибки
        }

        return $models;
    }

    /**
     * Проверить модель на наличие HasPermissions trait.
     */
    protected function checkModelHasPermissions(string $className): ?array
    {
        try {
            $reflection = new ReflectionClass($className);

            // Проверяем что это Eloquent модель
            if (!$reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class)) {
                return null;
            }

            // Проверяем использует ли HasPermissions trait
            if (!$this->usesTraitRecursive($reflection, HasPermissions::class)) {
                return null;
            }

            // Создаем экземпляр БЕЗ запросов к БД
            // Используем newInstanceWithoutConstructor чтобы избежать вызова конструктора
            $instance = $reflection->newInstanceWithoutConstructor();

            // Получаем имя таблицы безопасным способом
            $table = $this->getTableName($className, $reflection);

            return [
                'class' => $className,
                'name' => class_basename($className),
                'table' => $table,
            ];
        } catch (Throwable $e) {
            // Игнорируем ошибки при инстанцировании
            return null;
        }
    }

    /**
     * Безопасно получить имя таблицы модели.
     */
    protected function getTableName(string $className, ReflectionClass $reflection): string
    {
        try {
            // Пробуем получить через свойство $table
            if ($reflection->hasProperty('table')) {
                $property = $reflection->getProperty('table');
                if ($property->isPublic() || $property->isProtected()) {
                    $property->setAccessible(true);
                    $instance = $reflection->newInstanceWithoutConstructor();
                    $table = $property->getValue($instance);
                    if ($table) {
                        return $table;
                    }
                }
            }

            // Иначе генерируем стандартное имя таблицы Laravel
            $className = class_basename($className);

            return Str::snake(Str::pluralStudly($className));
        } catch (Throwable $e) {
            // Возвращаем имя класса в snake_case как fallback
            return Str::snake(class_basename($className));
        }
    }

    /**
     * Получить модель пользователя по умолчанию.
     */
    public function getDefaultUserModel(): ?string
    {
        $models = $this->findModelsWithPermissions();

        // Приоритет: User, затем первая найденная модель
        foreach ($models as $model) {
            if ($model['name'] === 'User') {
                return $model['class'];
            }
        }

        return !empty($models) ? reset($models)['class'] : null;
    }

    /**
     * Получить имя класса из файла.
     */
    protected function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);

        // Извлекаем namespace
        if (preg_match('/namespace\s+([^;]+);/i', $content, $namespaceMatches)) {
            $namespace = $namespaceMatches[1];
        } else {
            return null;
        }

        // Извлекаем имя класса
        if (preg_match('/class\s+(\w+)/i', $content, $classMatches)) {
            $className = $classMatches[1];
        } else {
            return null;
        }

        return $namespace . '\\' . $className;
    }

    /**
     * Проверить использует ли класс trait (рекурсивно).
     */
    protected function usesTraitRecursive(ReflectionClass $class, string $traitName): bool
    {
        // Проверяем текущий класс
        $traits = $class->getTraitNames();
        if (in_array($traitName, $traits)) {
            return true;
        }

        // Проверяем родительские классы
        $parent = $class->getParentClass();
        if ($parent && $this->usesTraitRecursive($parent, $traitName)) {
            return true;
        }

        // Проверяем traits используемые в traits
        foreach ($class->getTraits() as $trait) {
            if ($this->usesTraitRecursive($trait, $traitName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Очистить кеш.
     */
    public function clearCache(): void
    {
        $this->cache = [];
        $this->resourceCache = [];
    }

    /**
     * Найти все модели с HasResourcePermissions trait.
     */
    public function findModelsWithResourcePermissions(): array
    {
        if (!empty($this->resourceCache)) {
            return $this->resourceCache;
        }

        $models = [];

        $searchPaths = [
            app_path('Models'),
            app_path(),
            base_path('app/Models'),
        ];

        foreach ($searchPaths as $path) {
            if (File::exists($path)) {
                $foundModels = $this->scanDirectoryForResources($path);
                $models = array_merge($models, $foundModels);
            }
        }

        $composerModels = $this->findResourceModelsFromComposer();
        $models = array_merge($models, $composerModels);

        $this->resourceCache = $models;

        return $models;
    }

    /**
     * Сканировать директорию на наличие моделей с HasResourcePermissions.
     */
    protected function scanDirectoryForResources(string $path): array
    {
        $models = [];
        $files = File::allFiles($path);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->getClassNameFromFile($file->getPathname());
            if (!$className) {
                continue;
            }

            $model = $this->checkModelHasResourcePermissions($className);
            if ($model) {
                $models[$className] = $model;
            }
        }

        return $models;
    }

    /**
     * Найти модели с HasResourcePermissions из composer.
     */
    protected function findResourceModelsFromComposer(): array
    {
        $models = [];
        $composerPath = base_path('vendor/composer/autoload_classmap.php');

        if (!file_exists($composerPath)) {
            return $models;
        }

        $classmap = require $composerPath;

        foreach ($classmap as $className => $filePath) {
            if (str_contains($filePath, '/vendor/') || str_contains($filePath, '/tests/')) {
                continue;
            }

            if (!class_exists($className)) {
                continue;
            }

            $model = $this->checkModelHasResourcePermissions($className);
            if ($model) {
                $models[$className] = $model;
            }
        }

        return $models;
    }

    /**
     * Проверить использует ли модель HasResourcePermissions.
     */
    protected function checkModelHasResourcePermissions(string $className): ?array
    {
        try {
            $reflection = new ReflectionClass($className);

            if (!$reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class)) {
                return null;
            }

            if (!$this->usesTraitRecursive($reflection, HasResourcePermissions::class)) {
                return null;
            }

            $instance = $reflection->newInstanceWithoutConstructor();
            $table = $this->getTableName($className, $reflection);

            // Получаем resource key
            $resourceKey = method_exists($instance, 'getResourcePermissionKey')
                ? $instance->getResourcePermissionKey()
                : Str::plural(Str::snake(class_basename($className)));

            return [
                'class' => $className,
                'name' => class_basename($className),
                'table' => $table,
                'resource_key' => $resourceKey,
            ];
        } catch (Throwable $e) {
            return null;
        }
    }
}
