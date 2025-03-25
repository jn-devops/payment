<?php

use Homeful\Payment\PresentValue;
use Homeful\Payment\Enums\Cycle;
use Homeful\Payment\Class\Term;

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
        ->setInterestRate(7 / 100);
    expect($present_value->getDiscountedValue()->inclusive()->compareTo(1934738))->toBe(0);
});

dataset('PV simulation', function () {
    return [
        fn () => ['payment' => 50000 * 0.3,  'term' => 20, 'interest_rate' => 7 / 100, 'guess_discounted_value' => 1934738],
        fn () => ['payment' => 19978.48,  'term' => 20, 'interest_rate' => 7 / 100, 'guess_discounted_value' => 2576874.0],

        fn () => ['payment' => 7135.34, 'term' => 21, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 1000001.0],
        fn () => ['payment' => 6979.28, 'term' => 22, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 1000000.0],
        fn () => ['payment' => 6838.75, 'term' => 23, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 1000000.0],
        fn () => ['payment' => 6711.77, 'term' => 24, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 999999.0],
        fn () => ['payment' => 6596.69, 'term' => 25, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 999999.0],
        fn () => ['payment' => 6492.11, 'term' => 26, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 1000000.0],
        fn () => ['payment' => 6396.82, 'term' => 27, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 1000000.0],
        fn () => ['payment' => 6309.80, 'term' => 28, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 999999.0],
        fn () => ['payment' => 6230.18, 'term' => 29, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 1000000.0],
        fn () => ['payment' => 6157.17, 'term' => 30, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 1000000.0],

        fn () => ['payment' => 7848.87, 'term' => 21, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 1100000.0],
        fn () => ['payment' => 7677.21, 'term' => 22, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 1100000.0],
        fn () => ['payment' => 7522.62, 'term' => 23, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 1099999.0],
        fn () => ['payment' => 7382.79, 'term' => 24, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 1099976.0],
        fn () => ['payment' => 7256.36, 'term' => 25, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 1100000.0],
        fn () => ['payment' => 7141.32, 'term' => 26, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 1100000.0],
        fn () => ['payment' => 7036.50, 'term' => 27, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 1100000.0],
        fn () => ['payment' => 6940.78, 'term' => 28, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 1099999.0],
        fn () => ['payment' => 6853.19, 'term' => 29, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 1099999.0],
        fn () => ['payment' => 6772.89, 'term' => 30, 'interest_rate' => 6.25 / 100, 'guess_discounted_value' => 1100000.0],
    ];
});

it('can calculate PMT', function (array $attribs) {
    $present_value = (new PresentValue)
        ->setPayment($attribs['payment'])
        ->setTerm(new Term($attribs['term'], Cycle::Yearly))
        ->setInterestRate($attribs['interest_rate']);
//    dd($present_value->getDiscountedValue()->inclusive()->getAmount()->toFloat());
    expect($present_value->getDiscountedValue()->inclusive()->compareTo($attribs['guess_discounted_value']))->toBe(0);

})->with('PV simulation');
