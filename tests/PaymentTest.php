<?php

use Homeful\Payment\Class\Term;
use Homeful\Payment\Data\PaymentData;
use Homeful\Payment\Enums\Cycle;
use Homeful\Payment\Exceptions\MaxCycleBreached;
use Homeful\Payment\Payment;
use Illuminate\Validation\ValidationException;

dataset('simulation', function () {
    return [
        fn () => ['principal' => 850000, 'term' => 30, 'interest_rate' => 6.25 / 100, 'guess_monthly_amortization' => 5234.0],
        fn () => ['principal' => 850000, 'term' => 20, 'interest_rate' => 6.25 / 100, 'guess_monthly_amortization' => 6213.0],
        fn () => ['principal' => 850000, 'term' => 15, 'interest_rate' => 6.25 / 100, 'guess_monthly_amortization' => 7288.0],
        fn () => ['principal' => 3420000, 'term' => 25, 'interest_rate' => 7 / 100, 'guess_monthly_amortization' => 24172.0],
        fn () => ['principal' => 2900000, 'term' => 30, 'interest_rate' => 6.75 / 100, 'guess_monthly_amortization' => 18809.0],
        fn () => ['principal' => 2450000, 'term' => 20, 'interest_rate' => 6.35 / 100, 'guess_monthly_amortization' => 18051.0],
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

    $payment->setTerm(new Term(12, Cycle::Monthly));
    expect($payment->getTerm()->value)->toBe(12);
    expect($payment->getTerm()->cycle)->toBe(Cycle::Monthly);
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

it('can calculate PMT - yearly', function (array $attribs) {
    $payment = (new Payment)
        ->setPrincipal($attribs['principal'])
        ->setTerm(new Term($attribs['term']))
        ->setInterestRate($attribs['interest_rate']);
    expect($payment->getMonthlyAmortization()->inclusive()->compareTo($attribs['guess_monthly_amortization']))->toBe(0);
})->with('simulation');

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
});
