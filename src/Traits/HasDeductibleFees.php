<?php

namespace Homeful\Payment\Traits;

use Homeful\Common\Classes\AddOnFeeToPayment as DeductibleFeeFromPayment;
use Illuminate\Support\Collection;
use Whitecube\Price\Price;
use Brick\Money\Money;

/**
 * Trait HasDeductibleFees
 *
 * Provides functionality for managing deductible fees for a Payment.
 *
 * This trait enables a Payment model to accumulate multiple deductible fee items
 * (instances of DeductibleFeeFromPayment) using an internal Collection.
 * The collection is lazily initialized—if it hasn't been set when accessed, it will
 * automatically be instantiated as an empty Collection.
 *
 * ### Key Methods:
 * - **getDeductibleFees()**: Retrieves the current collection of deductible fee items.
 *   If not already set, it initializes the collection.
 * - **setDeductibleFees(Collection $deductibleFees)**: Replaces the current collection with a new one.
 * - **addDeductibleFee(DeductibleFeeFromPayment $deductibleFee)**: Adds a single deductible fee to the collection.
 * - **getTotalDeductibleFees()**: Iterates over all deductible fee items, aggregates their amounts,
 *   and returns a Price object representing the total deductible fees in the default currency.
 *
 * ### Usage Example:
 * ```php
 * // Assume your Payment model uses the HasDeductibleFees trait.
 * $payment->addDeductibleFee(new DeductibleFeeFromPayment('Late Fee', 50));
 * $payment->addDeductibleFee(new DeductibleFeeFromPayment('Service Charge', 25));
 * $total = $payment->getTotalDeductibleFees();
 * echo $total->format(); // e.g., "PHP 75.00"
 * ```
 *
 * @package Homeful\Payment\Traits
 */
trait HasDeductibleFees
{
    /**
     * A collection of deductible fee items.
     *
     * This property is lazily initialized. If it is not set when accessed,
     * it will automatically be instantiated as an empty Collection.
     *
     * @var Collection|null
     */
    protected ?Collection $deductibleFees = null;

    /**
     * Retrieve the collection of deductible fees.
     *
     * If the collection has not been initialized, it is instantiated as an empty Collection.
     *
     * @return Collection The collection containing all deductible fee items.
     */
    public function getDeductibleFees(): Collection
    {
        if ($this->deductibleFees === null) {
            $this->deductibleFees = new Collection();
        }

        return $this->deductibleFees;
    }

    /**
     * Set the deductible fees collection.
     *
     * @param Collection $deductibleFees The new collection of deductible fees.
     * @return self Returns the current instance for method chaining.
     */
    public function setDeductibleFees(Collection $deductibleFees): self
    {
        $this->deductibleFees = $deductibleFees;
        return $this;
    }

    /**
     * Add a deductible fee to the collection.
     *
     * If the collection is not yet initialized, it is automatically instantiated.
     *
     * @param DeductibleFeeFromPayment $deductibleFee The deductible fee item to be added.
     * @return self Returns the current instance for method chaining.
     */
    public function addDeductibleFee(DeductibleFeeFromPayment $deductibleFee): self
    {
        $this->getDeductibleFees()->add($deductibleFee);
        return $this;
    }

    /**
     * Calculate and return the total of all deductible fees as a Price object.
     *
     * This method iterates through the deductible fee items collection and aggregates each fee's
     * inclusive amount. Each fee's amount is applied as a modifier to a base Price of zero.
     * The final Price object represents the total deductible fees in the default currency (e.g., 'PHP').
     *
     * @return Price The total deductible fees as a Price object.
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function getTotalDeductibleFees(): Price
    {
        $totalDeductibleFees = new Price(Money::of(0, 'PHP'));
        $this->getDeductibleFees()->each(function (DeductibleFeeFromPayment $deductibleFee) use ($totalDeductibleFees) {
            $totalDeductibleFees->addModifier('deductible fee item', $deductibleFee->getAmount()->inclusive());
        });
        return $totalDeductibleFees;
    }
}
