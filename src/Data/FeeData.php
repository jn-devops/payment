<?php

namespace Homeful\Payment\Data;

use Homeful\Common\Classes\AmountCollectionItem;
use Spatie\LaravelData\Data;

class FeeData extends Data
{
    public function __construct(
        public string $name,
        public float  $amount,
    ){}

    public static function fromObject(AmountCollectionItem $item): self
    {
        return new self(
            name: $item->getName(),
            amount: $item->getAmount()->inclusive()->getAmount()->toFloat()
        );
    }
}
