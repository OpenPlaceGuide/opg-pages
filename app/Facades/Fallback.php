<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Fallback extends Facade
{
    /**
     * @see FallbackImplementation
     */
    protected static function getFacadeAccessor() { return 'fallback'; }
}
