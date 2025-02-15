<?php

namespace Homeful\Payment\Traits;

use Homeful\Common\Classes\AddOnFeeToPayment;
use Illuminate\Support\Collection;
use Homeful\Payment\Payment;
use Whitecube\Price\Price;
use Brick\Money\Money;

/**
 * Trait HasAddOnFees
 *
 * Provides functionality for managing additional fees (add-on fees) for a Payment.
 *
 * This trait enables a Payment model to accumulate multiple add-on fee items (instances of
 * AddOnFeeToPayment) using an internal Collection. The collection is lazily initialized—if it
 * hasn't been set when accessed, it will automatically be instantiated as an empty Collection.
 *
 * ### Key Methods:
 * - **getAddOnFeesToPayment()**: Retrieves the current add-on fees collection. If not already set,
 *   it will be lazily instantiated.
 * - **setAddOnFeesToPayment(Collection $addOnFeesToPayment)**: Replaces the current collection with a new one.
 * - **addAddOnFeeToPayment(AddOnFeeToPayment $addOnFeeToPayment)**: Adds a single fee to the collection.
 * - **getTotalAddOnFeesToPayment()**: Iterates over all fee items, aggregates their amounts,
 *   and returns a Price object representing the total add-on fees.
 *
 * ### Usage Example:
 * ```php
 * // In your Payment model that uses the trait:
 * $payment->addAddOnFeeToPayment($feeInstance);
 * $total = $payment->getTotalAddOnFeesToPayment();
 * echo $total->format(); // e.g., "PHP 3,000.00"
 * ```
 *
 * @package Homeful\Payment\Traits
 */
trait HasAddOnFees
{
    /**
     * A collection of add-on fee objects.
     *
     * This property is lazily initialized. If it is accessed and found to be null, it will automatically
     * be set to a new, empty Collection.
     *
     * @var Collection|null
     */
    protected ?Collection $addOnFeesToPayment = null;

    /**
     * Retrieve the collection of add-on fees.
     *
     * If the collection has not been initialized, it is instantiated as an empty Collection.
     *
     * @return Collection The collection containing all add-on fee items.
     */
    public function getAddOnFeesToPayment(): Collection
    {
        if ($this->addOnFeesToPayment === null) {
            $this->addOnFeesToPayment = new Collection();
        }
        return $this->addOnFeesToPayment;
    }

    /**
     * Set the add-on fees collection.
     *
     * @param Collection $addOnFeesToPayment The new collection of add-on fees.
     * @return Payment|HasAddOnFees Returns the current instance for method chaining.
     */
    public function setAddOnFeesToPayment(Collection $addOnFeesToPayment): self
    {
        $this->addOnFeesToPayment = $addOnFeesToPayment;
        return $this;
    }

    /**
     * Add an add-on fee to the collection.
     *
     * If the collection is not yet initialized, it will be automatically instantiated.
     *
     * @param AddOnFeeToPayment $addOnFeeToPayment The add-on fee item to be added.
     * @return Payment|HasAddOnFees Returns the current instance for method chaining.
     */
    public function addAddOnFeeToPayment(AddOnFeeToPayment $addOnFeeToPayment): self
    {
        $this->getAddOnFeesToPayment()->add($addOnFeeToPayment);
        return $this;
    }

    /**
     *
     * Calculate and return the total of all add-on fees as a Price object.
     *
     *  This method iterates through the add-on fees collection and aggregates each fee's inclusive amount.
     *  Each fee's amount is applied as a modifier to a base Price of zero. The resulting Price object
     *  represents the total add-on fees in the default currency (e.g., 'PHP').
     * @return Price
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function getTotalAddOnFeesToPayment(): Price
    {
        $totalAddOnFees = new Price(Money::of(0, 'PHP'));
        $this->getAddOnFeesToPayment()->each(function (AddOnFeeToPayment $addOnFeeToPayment) use ($totalAddOnFees) {
            $totalAddOnFees->addModifier('add on fee to payment item', $addOnFeeToPayment->getAmount()->inclusive());
        });
        return $totalAddOnFees;
    }
}
