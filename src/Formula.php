<?php

namespace Homeful\Payment;

use Illuminate\Support\Facades\Validator;
use Homeful\Payment\Class\Term;

abstract class Formula
{
    protected Term $term;

    protected float $interest_rate;

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
}
