<?php

namespace Khumam\Midtrans\RequestObjects;

trait TaxObject
{
    public function withTax(float $taxPrice): self
    {
        $grossAmount = (float) $this->transaction->gross_amount + $taxPrice;

        $this->transaction->update([
            'gross_amount' => $grossAmount,
            'tax_amount' => $taxPrice,
        ]);

        $this->payload['transaction_details']['gross_amount'] = $grossAmount;

        $this->addTaxItem($taxPrice);

        return $this;
    }

    public function withTaxPercentage(float $percent): self
    {
        $baseAmount = (float) $this->transaction->gross_amount;
        $taxPrice = round($baseAmount * ($percent / 100), 2);
        $grossAmount = $baseAmount + $taxPrice;

        $this->transaction->update([
            'gross_amount' => $grossAmount,
            'tax_amount' => $taxPrice,
            'tax_percentage' => $percent,
        ]);

        $this->payload['transaction_details']['gross_amount'] = $grossAmount;

        $this->addTaxItem($taxPrice);

        return $this;
    }

    protected function addTaxItem(float $taxPrice): void
    {
        if (! isset($this->payload['item_details'])) {
            $this->payload['item_details'] = [];
        }

        $this->payload['item_details'][] = [
            'id' => 'tax',
            'price' => (int) round($taxPrice),
            'quantity' => 1,
            'name' => 'Tax',
        ];

        $this->transaction->items()->create([
            'item_id' => 'tax',
            'price' => (int) round($taxPrice),
            'quantity' => 1,
            'name' => 'Tax',
        ]);
    }
}
