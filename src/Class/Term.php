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

    public function yearsToPay(int $precision = 1): mixed
    {
        $value = $this->cycle->yearsToPay($this->value, $precision);

        return $this->isWhole($value) ? (int) $value : $value;
    }

    protected function isWhole(mixed $value): bool
    {
        return fmod($value, 1) === 0.0;
    }
}
