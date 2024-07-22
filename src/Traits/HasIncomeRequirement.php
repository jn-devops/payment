<?php

namespace Homeful\Payment\Traits;

use Brick\Math\RoundingMode;
use Brick\Money\Money;

trait HasIncomeRequirement
{
    /**
     * @param float $percent_disposable_income_requirement
     * @return \Homeful\Payment\Payment|HasIncomeRequirement
     */
    public function setPercentDisposableIncomeRequirement(float $percent_disposable_income_requirement): self
    {
        $this->percent_disposable_income_requirement = $percent_disposable_income_requirement;

        return $this;
    }

    /**
     * default is 30%
     * configurable in payment.php
     *
     * @return float
     */
    public function getPercentDisposableIncomeRequirement(): float
    {
        return $this->percent_disposable_income_requirement ?? config('payment.default_percent_disposable_income');
    }

    /**
     * @return Money
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function getIncomeRequirement(): Money
    {
        $multiplier = $this->getPercentDisposableIncomeRequirement();

        return $this->getMonthlyAmortization()->inclusive()
            ->dividedBy(that: $multiplier, roundingMode: RoundingMode::CEILING);
    }
}
