<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;

trait PaymentAmountObject
{
    public function withPaymentAmount(array $paymentAmount): self
    {
        $paymentAmount = $this->validatePaymentAmount($paymentAmount);
        
        $this->payload['payment_amounts'] = $paymentAmount;

        return $this;
    }
    
    protected function validatePaymentAmount(array $paymentAmount): array
    {
        $rules = [
            "*.paid_at" => "required|string",
            "*.amount" => "required|string",
        ];
        
        $validator = Validator::make($paymentAmount, $rules);
        
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        
        return $validator->validated();
    }
}