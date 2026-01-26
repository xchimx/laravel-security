<?php

namespace Xchimx\LaravelSecurity\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Xchimx\LaravelSecurity\LaravelSecurity
 */
class LaravelSecurity extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Xchimx\LaravelSecurity\LaravelSecurity::class;
    }
}
