<?php

namespace Homeful\Payment\Data;

use Homeful\Payment\Payment;
use Spatie\LaravelData\Data;

class PaymentData extends Data
{
    public function __construct(
        public float $principal,
        public int $term,
        public string $cycle,
        public float $interest_rate,
        public float $monthly_amortization,
    ) {}

    public static function fromObject(Payment $payment): self
    {
        return new self(
            principal: $payment->getPrincipal()->inclusive()->getAmount()->toFloat(),
            term: $payment->getTerm()->value,
            cycle: $payment->getTerm()->cycle->name,
            interest_rate: $payment->getInterestRate(),
            monthly_amortization: $payment->getMonthlyAmortization()->inclusive()->getAmount()->toFloat()
        );
    }
}
