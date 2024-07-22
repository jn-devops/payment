<?php

namespace Homeful\Payment;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Homeful\Payment\Traits\HasIncomeRequirement;
use Jarouche\Financial\PMT;
use Whitecube\Price\Price;

class Payment extends Formula
{
    use HasIncomeRequirement;

    protected Price $principal;

    protected float $percent_disposable_income_requirement;

    /**
     * @return $this
     *
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function setPrincipal(Price|float $principal): self
    {
        $this->principal = ($principal instanceof Price)
            ? $principal
            : new Price(Money::of($principal, 'PHP'));

        return $this;
    }

    public function getPrincipal(): Price
    {
        return $this->principal ?? Price::PHP(0);
    }

    /**
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function getMonthlyAmortization(): Price
    {
        $principal = $this->getPrincipal()->inclusive()->getAmount()->toFloat();
        $months_to_pay = $this->getTerm()->monthsToPay();

        return $this->getMonthlyInterestRate() > 0
            ? with(new PMT($this->getMonthlyInterestRate(), $months_to_pay, $principal), function ($obj) {
                $float = round($obj->evaluate());

                return new Price(Money::of($float, 'PHP', roundingMode: RoundingMode::CEILING));
            })
            : new Price(Money::of($principal / $months_to_pay, 'PHP', roundingMode: RoundingMode::CEILING));
    }
}
