<?php

namespace Homeful\Payment;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Homeful\Payment\Class\Term;
use Illuminate\Support\Facades\Validator;
use Jarouche\Financial\PMT;
use Whitecube\Price\Price;

class Payment
{
    protected Price $principal;

    protected Term $term;

    protected float $interest_rate;

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
     * @return $this
     */
    public function setTerm(Term $term): self
    {
        $this->term = $term;

        return $this;
    }

    public function getTerm(): Term
    {
        return $this->term;
    }

    /**
     * @return $this
     */
    public function setInterestRate(float $interest_rate): self
    {
        Validator::validate(compact('interest_rate'), ['interest_rate' => [
            'required', 'numeric',  'min:0', 'max:1',
        ]]);
        $this->interest_rate = $interest_rate;

        return $this;
    }

    public function getInterestRate(): float
    {
        return $this->interest_rate ?? 0;
    }

    protected function getMonthlyInterestRate(): float
    {
        return $this->getInterestRate() / 12;
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
