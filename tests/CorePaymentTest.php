<?php

use Brick\Math\RoundingMode;
use Homeful\Common\Classes\AddOnFeeToPayment;
use Homeful\Payment\Payment;
use Homeful\Payment\Class\Term;
use Homeful\Payment\Data\PaymentData;
use Homeful\Payment\Enums\Cycle;
use Homeful\Payment\Exceptions\MaxCycleBreached;
use Illuminate\Validation\ValidationException;
use Brick\Money\Money;
use Whitecube\Price\Price;

test('principal property defaults to zero and can be set', function () {
    $payment = new Payment();
    expect($payment->getPrincipal()->inclusive()->compareTo(0))->toBe(0);
    $payment->setPrincipal(1000);
    expect($payment->getPrincipal()->inclusive()->compareTo(1000))->toBe(0);
});

test('term property works as expected', function () {
    $payment = new Payment();
    $payment->setTerm(new Term(20));
    expect($payment->getTerm()->value)->toBe(20);
    expect($payment->getTerm()->cycle)->toBe(Cycle::Yearly);
    expect($payment->getTerm()->yearsToPay())->toBe(20);
    expect($payment->getTerm()->monthsToPay())->toBe(20 * 12);

    $payment->setTerm(new Term(12, Cycle::Monthly));
    expect($payment->getTerm()->value)->toBe(12);
    expect($payment->getTerm()->cycle)->toBe(Cycle::Monthly);
    expect($payment->getTerm()->monthsToPay())->toBe(12);
    expect($payment->getTerm()->yearsToPay())->toBe(1);
});

test('exceeding max years to pay throws exception', function () {
    $payment = new Payment();
    $payment->setTerm(new Term(config('payment.max_years_to_pay') + 1, Cycle::Yearly));
})->expectException(MaxCycleBreached::class);

test('exceeding max months to pay throws exception', function () {
    $payment = new Payment();
    $payment->setTerm(new Term(config('payment.max_months_to_pay') + 1, Cycle::Monthly));
})->expectException(MaxCycleBreached::class);

test('interest rate property works correctly', function () {
    $payment = new Payment();
    expect($payment->getInterestRate())->toBe(0.0);
    $payment->setInterestRate(7 / 100);
    expect($payment->getInterestRate())->toBe(0.07);
});

test('exceeding max interest rate throws validation exception', function () {
    $payment = new Payment();
    $payment->setInterestRate((100 + 1) / 100);
})->expectException(ValidationException::class);

dataset('PMT simulation', function () {
    return [
        fn () => ['principal' => 850000,  'term' => 30, 'interest_rate' => 6.25 / 100, 'guess_monthly_amortization' => 5234.0, 'guess_income_requirement' => 17446.67],
        // Additional simulation data...
    ];
});

test('calculate PMT and income requirement (yearly)', function (array $attribs) {
    $payment = (new Payment)
        ->setPrincipal($attribs['principal'])
        ->setTerm(new Term($attribs['term']))
        ->setInterestRate($attribs['interest_rate']);
    expect($payment->getMonthlyAmortization()->inclusive()->compareTo($attribs['guess_monthly_amortization']))->toBe(0);
    expect($payment->getIncomeRequirement()->compareTo($attribs['guess_income_requirement']))->toBe(0);
})->with('PMT simulation');

test('calculate PMT for monthly cycle with zero interest', function () {
    $payment = (new Payment)
        ->setPrincipal(120000)
        ->setTerm(new Term(24, Cycle::Monthly))
        ->setInterestRate(0);
    expect($payment->getMonthlyAmortization()->inclusive()->compareTo(5000))->toBe(0);
});

test('payment data conversion', function () {
    $payment = (new Payment)
        ->setPrincipal(850000.0)
        ->setTerm(new Term(20))
        ->setInterestRate(6.25 / 100);
    $data = PaymentData::fromObject($payment);
    expect($data->principal)->toBe(850000.0);
    expect($data->term)->toBe(20);
    expect($data->cycle)->toBe('Yearly');
    expect($data->interest_rate)->toBe(6.25 / 100);
    expect($data->monthly_amortization)->toBe(6213.0);
    expect($data->income_requirement)->toBe(20710.0);
});

test('payment has add on fees', function () {
    $payment = (new Payment)
        ->setPrincipal(850000.0)
        ->setTerm(new Term(20))
        ->setInterestRate(6.25 / 100);
    $fire_insurance = new AddOnFeeToPayment('fire insurance', 100, false);
    $mortgage_redemption_insurance = new AddOnFeeToPayment('mortgage redemption insurance', 200, false);
    $payment->addAddOnFeeToPayment($mortgage_redemption_insurance);
    $payment->addAddOnFeeToPayment($fire_insurance);
    $total = $payment->getTotalAddOnFeesToPayment();

    expect($total->inclusive()->getAmount()->toFloat())->toBe(300.0);
});

test('getMonthlyAmortization includes add-on fees with zero interest', function () {
    // Create a new Payment instance
    $payment = new Payment();

    // Set principal to 120,000 PHP
    $payment->setPrincipal(120000);

    // Set term to 24 months (using a Monthly cycle)
    $term = new Term(24, Cycle::Monthly);
    $payment->setTerm($term);

    // Set interest rate to zero (no interest)
    $payment->setInterestRate(0);

    // Without any add-on fees, the monthly payment should be 120000 / 24 = 5000 PHP.
    $basePayment = $payment->getMonthlyAmortization();
    $baseValue = $basePayment->inclusive()->getAmount()->toFloat();
    expect($baseValue)->toBe(5000.0);

    // Add two add-on fees: one for 100 PHP and another for 200 PHP (total add-on fees = 300)
    $addOnFee1 = new AddOnFeeToPayment('fire insurance', 100, false);
    $addOnFee2 = new AddOnFeeToPayment('mortgage redemption insurance', 200, false);
    $payment->addAddOnFeeToPayment($addOnFee1);
    $payment->addAddOnFeeToPayment($addOnFee2);

    // Now the monthly payment should be base payment + total add-on fees = 5000 + 300 = 5300 PHP.
    $finalPayment = $payment->getMonthlyAmortization();
    $finalValue = $finalPayment->inclusive()->getAmount()->toFloat();
    expect($finalValue)->toBe(5300.0);

    // Additionally, check that the add-on fees modifier exists and equals 300.
    $modifiers = $finalPayment->getVatModifiers(false); // Use the proper method to get modifiers

    $modifier = collect($modifiers)->first(fn(\Whitecube\Price\Modifier $mod) => $mod->type() === 'Total Add-On Fees');
    expect($modifier)->not->toBeNull();
});

test('monthly amortization includes add-on fees with 6.2% interest', function () {
    // Create a Payment instance with a principal amount of 120,000 PHP,
    // a term of 24 months (monthly cycle), and an interest rate of 6.2%
    $payment = (new Payment)
        ->setPrincipal(750000)  // Principal: 750,000 PHP
        ->setTerm(new Term(29, Cycle::Yearly))  // Term: 29 years
        ->setInterestRate(6.25 / 100);  // Interest rate: 6.25%

    // Retrieve the base monthly payment (without fees) using the PMT formula.
    $baseMonthly = $payment->getMonthlyAmortization();
    $baseAmount = $baseMonthly->inclusive()->getAmount()->toFloat();
    expect($baseAmount)->toBe(4673.0);

    // Add two add-on fees: 100 PHP and 200 PHP (total add-on fees = 300 PHP)
    $fire_insurance = round((750000 * 0.00212584)/12, 2);
    expect($fire_insurance)->toBe(132.87);
    $fee1 = new AddOnFeeToPayment('fire insurance', new Price(Money::of($fire_insurance, 'PHP', roundingMode: RoundingMode::UP)), false);
    $mortgage_redemption_insurance = (750000 / 1000) * 0.225;
    expect($mortgage_redemption_insurance)->toBe(168.75);
    $fee2 = new AddOnFeeToPayment('mortgage redemption insurance', (750000 / 1000) * 0.225, false);

    $payment->addAddOnFeeToPayment($fee1);
    $payment->addAddOnFeeToPayment($fee2);

    // Retrieve the final monthly payment after fees are added.
    $finalMonthly = $payment->getMonthlyAmortization();
    $finalAmount = $finalMonthly->inclusive()->getAmount()->toFloat();

    // Retrieve total add-on fees as a Price object.
    $totalFees = $payment->getTotalAddOnFeesToPayment();
    $totalFeeAmount = $totalFees->inclusive()->getAmount()->toFloat();
    expect($totalFeeAmount)->toBe( 301.62);
    expect($totalFeeAmount)->toBe( $fire_insurance + $mortgage_redemption_insurance);

    // Assert that the final monthly payment equals the base payment plus total add-on fees.
    expect($finalAmount)->toBe($baseAmount + $totalFeeAmount);
    expect($finalAmount)->toBe(4974.62);
});

use Homeful\Common\Classes\AddOnFeeToPayment as DeductibleFeeFromPayment;
use Homeful\Payment\Traits\HasDeductibleFees;
use Illuminate\Support\Collection;

// Create a dummy class that uses the HasDeductibleFees trait
beforeEach(function () {
    // Create an anonymous class that uses the trait for testing purposes.
    $this->dummyPayment = new class {
        use HasDeductibleFees;
    };
});

test('it initializes deductible fees collection lazily', function () {
    $dummy = $this->dummyPayment;
    // On first access, the collection should be instantiated.
    expect($dummy->getDeductibleFees())->toBeInstanceOf(Collection::class);
    expect($dummy->getDeductibleFees()->isEmpty())->toBeTrue();
});

test('it adds deductible fees correctly', function () {
    $dummy = $this->dummyPayment;
    $fee1 = new DeductibleFeeFromPayment('Fee 1', 100, true);
    $fee2 = new DeductibleFeeFromPayment('Fee 2', 200, true);
    $dummy->addDeductibleFee($fee1);
    $dummy->addDeductibleFee($fee2);

    $fees = $dummy->getDeductibleFees();
    expect($fees->count())->toBe(2);
});

test('it sums total deductible fees correctly', function () {
    $dummy = $this->dummyPayment;
    $fee1 = new DeductibleFeeFromPayment('Fee 1', 100,true);
    $fee2 = new DeductibleFeeFromPayment('Fee 2', 200, true);
    $dummy->addDeductibleFee($fee1);
    $dummy->addDeductibleFee($fee2);

    $total = $dummy->getTotalDeductibleFees();
    // Assuming the Price object returns the total as 300 (and our test data is in PHP)
    expect($total->inclusive()->getAmount()->toFloat())->toBe(300.0);
});
