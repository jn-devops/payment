<?php

namespace Homeful\Payment\Exceptions;

use Homeful\Payment\Enums\Cycle;
use Exception;

class MaxCycleBreached extends Exception
{
    public function __construct(Cycle $cycle)
    {
        $message = match($cycle) {
            Cycle::Yearly => "Term cycle must not be greater than 30 years.",
            Cycle::Monthly => "Term cycle must not be greater than 24 months."
        };

        parent::__construct($message);
    }
}
