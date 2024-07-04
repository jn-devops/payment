<?php

namespace Homeful\Payment\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Homeful\Payment\Payment
 */
class Payment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Homeful\Payment\Payment::class;
    }
}
