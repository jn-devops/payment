<?php

use Homeful\Payment\Data\PaymentData;
use Homeful\Payment\Enums\Cycle;
use Homeful\Payment\Class\Term;
use Homeful\Payment\Payment;

it('has a principal property', function () {
    $payment = new Payment;
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

it('has interest rate property, default is zero', function () {
    $payment = new Payment;
    expect($payment->getInterestRate())->toBe(0.0);
    $payment->setInterestRate(7/100);
    expect($payment->getInterestRate())->toBe(0.07);

});

it('can calculate PMT', function () {
    $payment = (new Payment)
        ->setPrincipal(850000)
        ->setTerm(new Term(30))
        ->setInterestRate(6.25/100);
    expect($payment->getMonthlyAmortization()->inclusive()->compareTo(5234.0))->toBe(0);
    $payment = (new Payment)
        ->setPrincipal(850000)
        ->setTerm(new Term(20))
        ->setInterestRate(6.25/100);
    expect($payment->getMonthlyAmortization()->inclusive()->compareTo(6213.0))->toBe(0);
    $payment = (new Payment)
        ->setPrincipal(850000)
        ->setTerm(new Term(15))
        ->setInterestRate(6.25/100);
    expect($payment->getMonthlyAmortization()->inclusive()->compareTo(7288.0))->toBe(0);
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
        ->setInterestRate(6.25/100);
    $data = PaymentData::fromObject($payment);
    expect($data->principal)->toBe(850000.0);
    expect($data->term)->toBe(20);
    expect($data->cycle)->toBe('Yearly');
    expect($data->interest_rate)->toBe(6.25/100);
    expect($data->cycle)->toBe('Yearly');
    expect($data->monthly_amortization)->toBe(6213.0);
});
