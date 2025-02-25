<?php

use Homeful\Payment\Exceptions\MaxCycleBreached;
use Illuminate\Validation\ValidationException;
use Homeful\Payment\Data\PaymentData;
use Homeful\Payment\Enums\Cycle;
use Homeful\Payment\Class\Term;
use Homeful\Payment\Payment;

dataset('PMT simulation', function () {
    return [
        fn () => ['principal' => 850000,  'term' => 30, 'interest_rate' => 6.25 / 100, 'guess_monthly_amortization' => 5234.0, 'guess_income_requirement' => 17446.67],
        fn () => ['principal' => 850000,  'term' => 20, 'interest_rate' => 6.25 / 100, 'guess_monthly_amortization' => 6213.0, 'guess_income_requirement' => 20710.0],
        fn () => ['principal' => 850000,  'term' => 15, 'interest_rate' => 6.25 / 100, 'guess_monthly_amortization' => 7288.0, 'guess_income_requirement' => 24293.34],
        fn () => ['principal' => 3420000, 'term' => 25, 'interest_rate' => 7 / 100,     'guess_monthly_amortization' => 24172.0, 'guess_income_requirement' => 80573.34],
        fn () => ['principal' => 2900000, 'term' => 30, 'interest_rate' => 6.75 / 100,  'guess_monthly_amortization' => 18809.0, 'guess_income_requirement' => 62696.67],
        fn () => ['principal' => 2450000, 'term' => 20, 'interest_rate' => 6.35 / 100,  'guess_monthly_amortization' => 18051.0, 'guess_income_requirement' => 60170.0],

        fn () => ['principal' => (2500000 * (1 + 0.085)) * 0.95, 'term' => 20, 'interest_rate' => 7 / 100, 'guess_monthly_amortization' => 19978.0, 'guess_income_requirement' => 66593.34],
        fn () => ['principal' => (2500000 * (1 + 0.085)) * 0.95, 'term' => 25, 'interest_rate' => 7 / 100, 'guess_monthly_amortization' => 18213.0, 'guess_income_requirement' => 60710.00],
        fn () => ['principal' => (2500000 * (1 + 0.085)) * 0.95, 'term' => 30, 'interest_rate' => 7 / 100, 'guess_monthly_amortization' => 17144.0, 'guess_income_requirement' => 57146.67],
        fn () => ['principal' => (4500000 * (1 + 0.085)) * 0.95, 'term' => 20, 'interest_rate' => 7 / 100, 'guess_monthly_amortization' => 35961.0, 'guess_income_requirement' => 119870.0],
        fn () => ['principal' => (4500000 * (1 + 0.085)) * 0.95, 'term' => 25, 'interest_rate' => 7 / 100, 'guess_monthly_amortization' => 32783.0, 'guess_income_requirement' => 109276.67],
        fn () => ['principal' => (4500000 * (1 + 0.085)) * 0.95, 'term' => 30, 'interest_rate' => 7 / 100, 'guess_monthly_amortization' => 30859.0, 'guess_income_requirement' => 102863.34],
    ];
});

it('has a principal property defaults to zero', function () {
    $payment = new Payment;
    expect($payment->getPrincipal()->inclusive()->compareTo(0))->toBe(0);
    $payment->setPrincipal(1000);
    expect($payment->getPrincipal()->inclusive()->compareTo(1000))->toBe(0);
});

it('has a term property', function () {
    $payment = new Payment;
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

it('has max years to pay', function () {
    $payment = new Payment;
    $payment->setTerm(new Term(config('payment.max_years_to_pay') + 1, Cycle::Yearly));
})->expectException(MaxCycleBreached::class);

it('has max months to pay', function () {
    $payment = new Payment;
    $payment->setTerm(new Term(config('payment.max_months_to_pay') + 1, Cycle::Monthly));
})->expectException(MaxCycleBreached::class);

it('has interest rate property, default is zero', function () {
    $payment = new Payment;
    expect($payment->getInterestRate())->toBe(0.0);
    $payment->setInterestRate(7 / 100);
    expect($payment->getInterestRate())->toBe(0.07);
});

it('has max interest rate ', function () {
    $payment = new Payment;
    $payment->setInterestRate((100 + 1) / 100);
})->expectException(ValidationException::class);

it('has default percent disposable income', function () {
    $payment = new Payment;
    expect($payment->getPercentDisposableIncomeRequirement())->toBe(config('payment.default_percent_disposable_income'));
});

it('can calculate PMT and income requirement - yearly', function (array $attribs) {
    $payment = (new Payment)
        ->setPrincipal($attribs['principal'])
        ->setTerm(new Term($attribs['term']))
        ->setInterestRate($attribs['interest_rate']);
    expect($payment->getMonthlyAmortization()->inclusive()->compareTo($attribs['guess_monthly_amortization']))->toBe(0);
    expect($payment->getIncomeRequirement()->compareTo($attribs['guess_income_requirement']))->toBe(0);
})->with('PMT simulation');

it('can calculate PMT - monthly', function () {
    $payment = (new Payment)
        ->setPrincipal(120000)
        ->setTerm(new Term(24, Cycle::Monthly))
        ->setInterestRate(0);
    expect($payment->getMonthlyAmortization()->inclusive()->compareTo(5000))->toBe(0);
});

it('has data', function () {
    $payment = (new Payment)
        ->setPrincipal(850000.0)
        ->setTerm(new Term(20))
        ->setInterestRate(6.25 / 100);
    $data = PaymentData::fromObject($payment);
    expect($data->principal)->toBe(850000.0);
    expect($data->term)->toBe(20);
    expect($data->cycle)->toBe('Yearly');
    expect($data->interest_rate)->toBe(6.25 / 100);
    expect($data->cycle)->toBe('Yearly');
    expect($data->monthly_amortization)->toBe(6213.0);
    expect($data->income_requirement)->toBe(20710.0);
});


use Homeful\Common\Classes\AddOnFeeToPayment as DeductibleFeeFromPayment;
use Homeful\Common\Classes\AddOnFeeToPayment;

// Test: Payment without any fees.
test('monthly amortization without fees', function () {
    $payment = (new Payment)
        ->setPrincipal(120000) // 120,000 PHP principal
        ->setTerm(new Term(24, Cycle::Monthly)) // 24 months term
        ->setInterestRate(0); // Zero interest

    $monthlyPayment = $payment->getMonthlyAmortization();
    // Expected: 120,000 / 24 = 5,000 PHP
    expect($monthlyPayment->inclusive()->getAmount()->toFloat())->toBe(5000.0);
});

// Test: Payment with add-on fees only.
test('monthly amortization includes add-on fees', function () {
    $payment = (new Payment)
        ->setPrincipal(120000)
        ->setTerm(new Term(24, Cycle::Monthly))
        ->setInterestRate(0);

    // Base monthly payment: 5,000 PHP.
    $basePayment = $payment->getMonthlyAmortization();
    $baseAmount = $basePayment->inclusive()->getAmount()->toFloat();
    expect($baseAmount)->toBe(5000.0);

    // Add add-on fees totaling 300 PHP.
    $fee1 = new AddOnFeeToPayment('fire insurance', 100, false);
    $fee2 = new AddOnFeeToPayment('mortgage redemption insurance', 200, false);
    $payment->addAddOnFeeToPayment($fee1);
    $payment->addAddOnFeeToPayment($fee2);

    $finalPayment = $payment->getMonthlyAmortization();
    $finalAmount = $finalPayment->inclusive()->getAmount()->toFloat();

    // Final payment should equal base + 300 = 5,300 PHP.
    expect($finalAmount)->toBe(5300.0);
});

// Test: Payment with deductible fees only.
test('monthly amortization includes deductible fees', function () {
    $payment = (new Payment)
        ->setPrincipal(240000)
        ->setTerm(new Term(24, Cycle::Monthly))
        ->setInterestRate(0);

    $basePayment = $payment->getMonthlyAmortization();
    $baseAmount = $basePayment->inclusive()->getAmount()->toFloat();
    expect($baseAmount)->toBe(10000.0);

    // Add a deductible fee of 50 PHP.
    $deductibleFee = new DeductibleFeeFromPayment('late fee', 125, deductible: true);
    $payment->addDeductibleFee($deductibleFee);

    $finalPayment = $payment->getMonthlyAmortization();
    $finalAmount = $finalPayment->inclusive()->getAmount()->toFloat();

    // Final payment should equal base - 50 = 4,950 PHP.
    expect($finalAmount)->toBe(9875.0);
});

// Test: Payment with both add-on and deductible fees.
test('monthly amortization includes both add-on and deductible fees', function () {
    $payment = (new Payment)
        ->setPrincipal(120000)
        ->setTerm(new Term(24, Cycle::Monthly))
        ->setInterestRate(0);

    // Base monthly payment: 5,000 PHP.
    $basePayment = $payment->getMonthlyAmortization();
    $baseAmount = $basePayment->inclusive()->getAmount()->toFloat();
    expect($baseAmount)->toBe(5000.0);

    // Add add-on fees totaling 300 PHP.
    $fee1 = new AddOnFeeToPayment('fire insurance', 100, false);
    $fee2 = new AddOnFeeToPayment('mortgage redemption insurance', 200, false);
    $payment->addAddOnFeeToPayment($fee1);
    $payment->addAddOnFeeToPayment($fee2);

    // Add a deductible fee of 50 PHP.
    $deductibleFee = new DeductibleFeeFromPayment('late fee', 50, deductible: true);
    $payment->addDeductibleFee($deductibleFee);

    $finalPayment = $payment->getMonthlyAmortization();
    $finalAmount = $finalPayment->inclusive()->getAmount()->toFloat();

    // Expected final monthly payment = 5000 + 300 - 50 = 5250 PHP.
    expect($finalAmount)->toBe(5250.0);
});
