<?php

namespace Homeful\Payment\Class;

use Homeful\Payment\Enums\Cycle;
use Homeful\Payment\Exceptions\MaxCycleBreached;
use Homeful\Payment\Exceptions\MinTermBreached;

class Term
{
    public int $value;

    public Cycle $cycle;

    /**
     * @throws MaxCycleBreached
     * @throws MinTermBreached
     */
    public function __construct(int $value, Cycle $cycle = Cycle::Yearly)
    {
        if ($value < 0) {
            throw new MinTermBreached;
        }
        if ($value > $cycle->ceiling()) {
            throw new MaxCycleBreached($cycle);
        }
        $this->value = $value;
        $this->cycle = $cycle;
    }

    public function monthsToPay(): int
    {
        return $this->cycle->monthsToPay($this->value);
    }
}
