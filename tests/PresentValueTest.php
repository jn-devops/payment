<?php

use Homeful\Payment\Class\Term;
use Homeful\Payment\Data\PaymentData;
use Homeful\Payment\Enums\Cycle;
use Homeful\Payment\Exceptions\MaxCycleBreached;
use Homeful\Payment\PresentValue;
use Illuminate\Validation\ValidationException;
use Jarouche\Financial\PV;

it('has a payment property defaults to zero', function () {
    $present_value = new PresentValue;
    expect($present_value->getPayment()->inclusive()->compareTo(0))->toBe(0);
    $present_value->setPayment(1000);
    expect($present_value->getPayment()->inclusive()->compareTo(1000))->toBe(0);
});

it('has a term property', function () {
    $present_value = new PresentValue;
    $present_value->setTerm(new Term(20));
    expect($present_value->getTerm()->value)->toBe(20);
    expect($present_value->getTerm()->cycle)->toBe(Cycle::Yearly);

    $present_value->setTerm(new Term(12, Cycle::Monthly));
    expect($present_value->getTerm()->value)->toBe(12);
    expect($present_value->getTerm()->cycle)->toBe(Cycle::Monthly);
});

it('can calculate PMT - monthly', function () {
    $present_value = (new PresentValue)
        ->setPayment(50000 * 0.3)
        ->setTerm(new Term(20, Cycle::Yearly))
        ->setInterestRate(7/100);
    expect($present_value->getMonthlyDiscountedValue()->inclusive()->compareTo(1934738))->toBe(0);
});

dataset('PV simulation', function () {
    return [
        fn () => ['payment' => 50000 * 0.3,  'term' => 20, 'interest_rate' => 7 / 100, 'guess_discounted_value' => 1934738],
        fn () => ['payment' => 19978.48,  'term' => 20, 'interest_rate' => 7 / 100, 'guess_discounted_value' => 2576874.0],
    ];
});

it('can calculate PMT', function (array $attribs) {
    $present_value = (new PresentValue)
        ->setPayment($attribs['payment'])
        ->setTerm(new Term($attribs['term'], Cycle::Yearly))
        ->setInterestRate($attribs['interest_rate']);
    expect($present_value->getMonthlyDiscountedValue()->inclusive()->compareTo($attribs['guess_discounted_value']))->toBe(0);
})->with('PV simulation');
