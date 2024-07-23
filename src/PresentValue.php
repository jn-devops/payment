<?php

namespace Homeful\Payment;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Jarouche\Financial\PV;
use Whitecube\Price\Price;

class PresentValue extends Formula
{
    protected Price $payment;

    public function getPayment(): Price
    {
        return $this->payment ?? Price::PHP(0);
    }

    /**
     * @return $this
     *
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function setPayment(Price|float $payment): self
    {
        $this->payment = ($payment instanceof Price)
            ? $payment
            : new Price(Money::of($payment, 'PHP'));

        return $this;
    }

    /**
     * @deprecated
     *
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function getMonthlyDiscountedValue(): Price
    {
        $payment = $this->getPayment()->inclusive()->getAmount()->toFloat();
        $months_to_pay = $this->getTerm()->monthsToPay();

        return with(new PV(InterestRate: $this->getMonthlyInterestRate(), periods: $months_to_pay, pmt: $payment), function ($obj) {
            $float = round($obj->evaluate());

            return new Price(Money::of($float, 'PHP', roundingMode: RoundingMode::CEILING));
        });
    }

    /**
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function getDiscountedValue(): Price
    {
        $payment = $this->getPayment()->inclusive()->getAmount()->toFloat();
        $months_to_pay = $this->getTerm()->monthsToPay();

        return with(new PV(InterestRate: $this->getMonthlyInterestRate(), periods: $months_to_pay, pmt: $payment), function ($obj) {
            $float = round($obj->evaluate());

            return new Price(Money::of($float, 'PHP', roundingMode: RoundingMode::CEILING));
        });
    }
}
