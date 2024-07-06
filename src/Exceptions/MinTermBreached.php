<?php

namespace Homeful\Payment\Exceptions;

use Exception;

class MinTermBreached extends Exception
{
    public function __construct()
    {
        $message = 'Term value must not be less than zero.';

        parent::__construct($message);
    }
}
