<?php

namespace Homeful\Payment;

use Homeful\Payment\Traits\{HasAddOnFees, HasIncomeRequirement};
use Brick\Math\RoundingMode;
use Jarouche\Financial\PMT;
use Whitecube\Price\Price;
use Brick\Money\Money;

class Payment extends Formula
{
    use HasIncomeRequirement;
    use HasAddOnFees;

    protected Price $principal;

    /**
     * Set the principal amount.
     *
     * Accepts either a Price instance or a float and stores it as a Price.
     *
     * @param Price|float $principal The principal amount in PHP.
     * @return $this
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

    /**
     * Retrieve the principal amount as a Price object.
     *
     * @return Price
     */
    public function getPrincipal(): Price
    {
        return $this->principal ?? Price::PHP(0);
    }

    /**
     * Calculate the monthly amortization payment including any add-on fees.
     *
     * This method computes the base monthly payment required to amortize the principal over the
     * specified term with the given monthly interest rate. It then retrieves any add-on fees and adds
     * them as modifiers to the base payment. The final result is returned as a Price object that
     * represents the total monthly payment (base payment plus fees).
     *
     * ### How It Works:
     * 1. **Base Payment Calculation:**
     *    - Retrieve the principal in major units (pesos) as a float.
     *    - Determine the total number of months to pay.
     *    - If a positive monthly interest rate exists, compute the payment using the PMT formula;
     *      otherwise, calculate the payment by dividing the principal by the number of months.
     *
     * 2. **Add-On Fees Integration:**
     *    - Retrieve the total add-on fees via `getTotalAddOnFeesToPayment()` (which returns a Price).
     *    - If the total add-on fees are greater than zero (i.e. the inclusive amount is greater than zero),
     *      add these fees as a modifier to the base monthly payment using the Price object's
     *      `addModifier()` method. Note that the complete Price object (using `inclusive()`) is passed as
     *      the modifier value.
     *
     * 3. **Final Result:**
     *    - The method returns the base Price object with all modifiers applied. This Price object encapsulates
     *      the total monthly payment including any additional fees.
     *
     * ### Example Usage:
     * ```php
     * $payment = (new Payment)
     *      ->setPrincipal(850000.0)
     *      ->setTerm(new Term(20))
     *      ->setInterestRate(6.25 / 100);
     *
     * // Add additional fees (e.g., fire insurance, mortgage redemption insurance)
     * $payment->addAddOnFeeToPayment(new AddOnFeeToPayment('fire insurance', 100, false));
     * $payment->addAddOnFeeToPayment(new AddOnFeeToPayment('mortgage redemption insurance', 200, false));
     *
     * // Get the total monthly payment including add-on fees.
     * $monthlyPayment = $payment->getMonthlyAmortization();
     * echo $monthlyPayment->format(); // e.g., "PHP 6,234.00"
     * ```
     *
     * ### Best Practices:
     * - **Monetary Consistency:** Storing monetary values as Price objects ensures precision and avoids floating-point errors.
     * - **Separation of Concerns:** The base monthly payment and add-on fees are computed separately and then combined, which facilitates auditing
     *   and debugging.
     * - **Rounding:** Using `RoundingMode::CEILING` ensures that fractional values are rounded up, avoiding any undercharge in payments.
     *
     * @return \Whitecube\Price\Price The monthly amortization payment including any add-on fees.
     *
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     * @throws \Exception
     */
    public function getMonthlyAmortization(): Price
    {
        // Step 1: Compute the base monthly payment.
        $principal = $this->getPrincipal()->inclusive()->getAmount()->toFloat();
        $monthsToPay = $this->getTerm()->monthsToPay();
        $monthlyInterestRate = $this->getMonthlyInterestRate();

        if ($monthlyInterestRate > 0) {
            $pmt = new PMT($monthlyInterestRate, $monthsToPay, $principal);
            $paymentValue = round($pmt->evaluate());
        } else {
            $paymentValue = $principal / $monthsToPay;
        }

        // Create a Price object for the base monthly payment.
        $baseMonthlyPayment = new Price(
            Money::of($paymentValue, 'PHP', roundingMode: RoundingMode::CEILING)
        );

        // Step 2: Retrieve the total add-on fees as a Price object.
        $totalAddOnFees = $this->getTotalAddOnFeesToPayment();

        // Step 3: If add-on fees exist, add them as a modifier.
        if ($totalAddOnFees->inclusive()->getAmount()->toFloat() > 0) {
            $baseMonthlyPayment->addModifier('Total Add-On Fees', $totalAddOnFees->inclusive());
        }

        // Step 4: Return the final monthly payment including add-on fees.
        return $baseMonthlyPayment;
    }
}
