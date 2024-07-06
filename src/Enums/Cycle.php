<?php

namespace Homeful\Payment\Enums;

enum Cycle
{
    case Monthly;
    case Yearly;

    /**
     * @TODO:
     */
    public function ceiling(): int
    {
        return match ($this) {
            Cycle::Monthly => config('payment.max_months_to_pay'),
            Cycle::Yearly => config('payment.max_years_to_pay'),
        };
    }

    public function monthsToPay(int $value): int
    {
        return match ($this) {
            Cycle::Monthly => $value,
            Cycle::Yearly => $value * 12,
        };
    }
}
