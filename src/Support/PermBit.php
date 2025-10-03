<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Support;

use GMP;

class PermBit
{
    private static array $cache = [];

    public static function build(array $actions): array
    {
        $result = [];
        $bitPosition = 0;

        foreach ($actions as $category => $categoryActions) {
            foreach ($categoryActions as $slug => $name) {
                $fullSlug = is_string($slug) ? "{$category}.{$slug}" : $category;
                $bitmask = gmp_init(1);
                $bitmask = gmp_mul($bitmask, gmp_pow(2, $bitPosition));

                $result[$fullSlug] = [
                    'slug' => $fullSlug,
                    'name' => is_string($name) ? $name : $slug,
                    'category' => $category,
                    'bit_position' => $bitPosition,
                    'bitmask' => gmp_strval($bitmask),
                    'bitmask_hex' => gmp_strval($bitmask, 16),
                ];

                self::$cache[$fullSlug] = $bitPosition;
                $bitPosition++;
            }
        }

        return $result;
    }

    public static function rebuild(): array
    {
        $actions = config('unperm.actions', []);

        return self::build($actions);
    }

    public static function getBitPosition(string $slug): ?int
    {
        if (isset(self::$cache[$slug])) {
            return self::$cache[$slug];
        }

        self::rebuild();

        if (isset(self::$cache[$slug])) {
            return self::$cache[$slug];
        }

        $action = \DFiks\UnPerm\Models\Action::where('slug', $slug)->first();
        if ($action && $action->bitmask !== '0') {
            $bitPosition = (int) log(gmp_intval(gmp_init($action->bitmask)), 2);
            self::$cache[$slug] = $bitPosition;

            return $bitPosition;
        }

        return null;
    }

    public static function getBitmask(string $slug): string
    {
        $bitPosition = self::getBitPosition($slug);

        if ($bitPosition === null) {
            return '0';
        }

        $bitmask = gmp_init(1);
        $bitmask = gmp_mul($bitmask, gmp_pow(2, $bitPosition));

        return gmp_strval($bitmask);
    }

    public static function combine(array $slugs): string
    {
        $result = gmp_init(0);

        foreach ($slugs as $slug) {
            $bitmask = gmp_init(self::getBitmask($slug));
            $result = gmp_or($result, $bitmask);
        }

        return gmp_strval($result);
    }

    public static function hasBit(string|GMP $bitmask, int $bitPosition): bool
    {
        if (is_string($bitmask)) {
            $bitmask = gmp_init($bitmask);
        }

        $checkBit = gmp_init(1);
        $checkBit = gmp_mul($checkBit, gmp_pow(2, $bitPosition));

        return gmp_cmp(gmp_and($bitmask, $checkBit), 0) !== 0;
    }

    public static function hasAction(string|GMP $bitmask, string $slug): bool
    {
        $bitPosition = self::getBitPosition($slug);

        if ($bitPosition !== null) {
            return self::hasBit($bitmask, $bitPosition);
        }

        $action = \DFiks\UnPerm\Models\Action::where('slug', $slug)->first();
        if (!$action || $action->bitmask === '0') {
            return false;
        }

        if (is_string($bitmask)) {
            $bitmask = gmp_init($bitmask);
        }

        $actionMask = gmp_init($action->bitmask);

        return gmp_cmp(gmp_and($bitmask, $actionMask), $actionMask) === 0;
    }

    public static function hasAllActions(string|GMP $bitmask, array $slugs): bool
    {
        foreach ($slugs as $slug) {
            if (!self::hasAction($bitmask, $slug)) {
                return false;
            }
        }

        return true;
    }

    public static function hasAnyAction(string|GMP $bitmask, array $slugs): bool
    {
        foreach ($slugs as $slug) {
            if (self::hasAction($bitmask, $slug)) {
                return true;
            }
        }

        return false;
    }

    public static function addAction(string|GMP $bitmask, string $slug): string
    {
        if (is_string($bitmask)) {
            $bitmask = gmp_init($bitmask);
        }

        $actionMask = gmp_init(self::getBitmask($slug));
        $result = gmp_or($bitmask, $actionMask);

        return gmp_strval($result);
    }

    public static function removeAction(string|GMP $bitmask, string $slug): string
    {
        if (is_string($bitmask)) {
            $bitmask = gmp_init($bitmask);
        }

        $actionMask = gmp_init(self::getBitmask($slug));
        $result = gmp_and($bitmask, gmp_com($actionMask));

        return gmp_strval($result);
    }

    public static function getActions(string|GMP $bitmask): array
    {
        if (is_string($bitmask)) {
            $bitmask = gmp_init($bitmask);
        }

        $actions = [];
        $allActions = self::rebuild();

        foreach ($allActions as $slug => $data) {
            if (self::hasBit($bitmask, $data['bit_position'])) {
                $actions[] = $slug;
            }
        }

        return $actions;
    }

    public static function toInt(string|GMP $bitmask): int
    {
        if (is_string($bitmask)) {
            $bitmask = gmp_init($bitmask);
        }

        return gmp_intval($bitmask);
    }

    public static function toString(string|GMP $bitmask): string
    {
        if ($bitmask instanceof GMP) {
            return gmp_strval($bitmask);
        }

        return $bitmask;
    }

    public static function toHex(string|GMP $bitmask): string
    {
        if (is_string($bitmask)) {
            $bitmask = gmp_init($bitmask);
        }

        return gmp_strval($bitmask, 16);
    }

    public static function toBinary(string|GMP $bitmask): string
    {
        if (is_string($bitmask)) {
            $bitmask = gmp_init($bitmask);
        }

        return gmp_strval($bitmask, 2);
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
