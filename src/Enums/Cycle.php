<?php

namespace Homeful\Payment\Enums;

enum Cycle
{
    case Monthly;
    case Yearly;

    public function ceiling(): int
    {
        return match ($this) {
            Cycle::Monthly => 24,
            Cycle::Yearly => 30,
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
