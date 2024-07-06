<?php

namespace Homeful\Payment\Enums;

enum Cycle
{
    case Monthly;
    case Yearly;

    /**
     * @TODO:
     * @return int
     */
    public function ceiling(): int
    {
        return match ($this) {
            Cycle::Monthly => config('payment.max_months_to_pay'),
            Cycle::Yearly => config('payment.max_years_to_pay'),
        };
    }

    /**
     * @param int $value
     * @return int
     */
    public function monthsToPay(int $value): int
    {
        return match ($this) {
            Cycle::Monthly => $value,
            Cycle::Yearly => $value * 12,
        };
    }
}
