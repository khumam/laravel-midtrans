<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;

trait ShopeePayObject
{
    public function withShopeePay(array|object $shopeePay): self
    {
        $shopeePay = $this->validateShopeePay($shopeePay);
        
        $this->payload['shopee_pay'] = $shopeePay;

        return $this;
    }
    
    protected function validateShopeePay(array|object $shopeePay): array
    {
        $rules = [
            "callback_url" => "nullable|string",
        ];
        
        $validator = Validator::make($shopeePay, $rules);
        
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        
        return $validator->validated();
    }
}