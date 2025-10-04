<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Support;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Результат проверки прав с fluent API.
 */
class PermissionResult
{
    public function __construct(
        protected bool $allowed,
        protected string $ability,
        protected mixed $arguments = null
    ) {
    }

    /**
     * Проверить разрешено ли.
     */
    public function allowed(): bool
    {
        return $this->allowed;
    }

    /**
     * Проверить запрещено ли.
     */
    public function denied(): bool
    {
        return !$this->allowed;
    }

    /**
     * Выбросить исключение если запрещено.
     *
     * @throws AccessDeniedHttpException
     */
    public function throwDenied(?string $message = null): self
    {
        if ($this->denied()) {
            throw new AccessDeniedHttpException(
                $message ?? "Unauthorized action: {$this->ability}"
            );
        }

        return $this;
    }

    /**
     * Выбросить исключение если разрешено.
     *
     * @throws AccessDeniedHttpException
     */
    public function throwAllowed(?string $message = null): self
    {
        if ($this->allowed()) {
            throw new AccessDeniedHttpException(
                $message ?? "Action should be denied: {$this->ability}"
            );
        }

        return $this;
    }

    /**
     * Выбросить исключение (по умолчанию если запрещено).
     *
     * @throws AccessDeniedHttpException
     */
    public function throw(?string $message = null): self
    {
        return $this->throwDenied($message);
    }

    /**
     * Выполнить callback если разрешено.
     */
    public function then(callable $callback): self
    {
        if ($this->allowed()) {
            $callback();
        }

        return $this;
    }

    /**
     * Выполнить callback если запрещено.
     */
    public function else(callable $callback): self
    {
        if ($this->denied()) {
            $callback();
        }

        return $this;
    }

    /**
     * Вернуть значение в зависимости от результата.
     */
    public function value(mixed $ifAllowed, mixed $ifDenied): mixed
    {
        return $this->allowed() ? $ifAllowed : $ifDenied;
    }

    /**
     * Преобразование в bool.
     */
    public function __invoke(): bool
    {
        return $this->allowed();
    }

    /**
     * Преобразование в строку для отладки.
     */
    public function __toString(): string
    {
        $status = $this->allowed() ? 'ALLOWED' : 'DENIED';

        return "{$status}: {$this->ability}";
    }

    /**
     * Статический создатель.
     */
    public static function make(bool $allowed, string $ability, mixed $arguments = null): self
    {
        return new self($allowed, $ability, $arguments);
    }
}
