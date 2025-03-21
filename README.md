
# 🏦 Payment Package

The `Homeful\Payment` package provides a robust and testable abstraction layer for mortgage-related financial computations. It encapsulates the standard PMT and PV formulas using precise monetary operations via the `whitecube/price` and `brick/money` libraries.

---

## 📦 Features

- Compute monthly amortization with interest and term (PMT formula)
- Add-on fee handling (e.g. insurance, extra charges)
- Deductible fee support (e.g. rebates, adjustments)
- Compute present value from future payment streams (PV formula)
- Income requirement estimation based on configurable disposable income percentage
- Extensive test coverage and simulation datasets

---

## ✅ Installation

Ensure you have the following packages:

```bash
composer require whitecube/price brick/money jarouche/financial
```

---

## 🧮 Usage

### 📘 Basic PMT Calculation

```php
use Homeful\Payment\Payment;
use Homeful\Payment\Class\Term;

$payment = (new Payment)
    ->setPrincipal(850000.0)
    ->setTerm(new Term(20)) // 20 years
    ->setInterestRate(6.25 / 100);

$monthly = $payment->getMonthlyAmortization();
echo $monthly->format(); // e.g. PHP 6,213.00
```

### ➕ Add-On Fees (e.g. Fire Insurance, MRI)

```php
use Homeful\Common\Classes\AddOnFeeToPayment;

$payment->addAddOnFeeToPayment(new AddOnFeeToPayment('fire insurance', 100, false));
$payment->addAddOnFeeToPayment(new AddOnFeeToPayment('mortgage redemption insurance', 200, false));
```

### ➖ Deductible Fees (e.g. Promo Deduction)

```php
use Homeful\Common\Classes\DeductibleFeeFromPayment;

$payment->addDeductibleFee(new DeductibleFeeFromPayment('promo', 125, true));
```

### 🧾 Income Requirement

```php
echo $payment->getIncomeRequirement(); // e.g. PHP 20,710.00
```

---

## 💡 Present Value Calculation

```php
use Homeful\Payment\PresentValue;
use Homeful\Payment\Class\Term;

$pv = (new PresentValue)
    ->setPayment(19978.48)
    ->setTerm(new Term(20))
    ->setInterestRate(7 / 100);

echo $pv->getDiscountedValue()->format(); // e.g. PHP 2,576,874.00
```

---

## 🧪 Testing

Unit and simulation tests available in `/tests/Payment`.

```bash
php artisan test --filter=Payment
```

Includes scenarios for:

- Edge case interest validation
- Max term validations
- Add-on and deductible modifier interactions
- Multiple amortization simulations

---

## ⚙️ Configuration

You may configure these via `config/payment.php`:

```php
return [
    'default_percent_disposable_income' => 0.30,
    'max_years_to_pay' => 30,
    'max_months_to_pay' => 360,
];
```

---

## 📂 Data Transformation

Use `PaymentData::fromObject($payment)` to extract structured values like:

- `principal`
- `term`, `cycle`
- `interest_rate`
- `monthly_amortization`
- `income_requirement`

---

## 🧩 Traits

- `HasAddOnFees`
- `HasDeductibleFees`
- `HasIncomeRequirement`

These ensure modular responsibility and are shared across payment types.

---

## 🤝 Dependencies

- [`brick/money`](https://github.com/brick/money)
- [`whitecube/price`](https://github.com/whitecube/price)
- [`jarouche/financial`](https://github.com/jarouche/financial)

---

## ✨ Example Output

```php
$payment = (new Payment)
    ->setPrincipal(2900000)
    ->setTerm(new Term(30))
    ->setInterestRate(6.75 / 100);

echo $payment->getMonthlyAmortization()->format(); // PHP 18,809.00
```

---

Behold, a new you awaits — one with precise mortgage computations and a testable financial layer.
