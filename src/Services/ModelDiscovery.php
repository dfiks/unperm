<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Services;

use DFiks\UnPerm\Traits\HasPermissions;
use Illuminate\Support\Facades\File;
use ReflectionClass;

/**
 * Сервис для автоматического обнаружения моделей с HasPermissions trait.
 */
class ModelDiscovery
{
    protected array $cache = [];

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
        $appPath = app_path('Models');

        if (!File::exists($appPath)) {
            return [];
        }

        $files = File::allFiles($appPath);

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file->getPathname());

            if (!$className || !class_exists($className)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($className);

                // Проверяем использует ли класс HasPermissions trait
                if ($this->usesTraitRecursive($reflection, HasPermissions::class)) {
                    $instance = app($className);
                    
                    $models[$className] = [
                        'class' => $className,
                        'name' => class_basename($className),
                        'table' => $instance->getTable(),
                    ];
                }
            } catch (\Throwable $e) {
                // Игнорируем ошибки при инстанцировании
                continue;
            }
        }

        $this->cache = $models;

        return $models;
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
    }
}

