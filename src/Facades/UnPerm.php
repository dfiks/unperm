<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Facades;

use Illuminate\Support\Facades\Facade;

class UnPerm extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'unperm';
    }
}
