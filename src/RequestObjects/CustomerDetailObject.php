<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;

trait CustomerDetailObject
{
    public function withCustomerDetail(array|object $customerDetails): self
    {
        $customerDetails = $this->validateCustomerDetail($customerDetails);
        
        $this->payload['customer_details'] = $customerDetails;

        return $this;
    }
    
    protected function validateCustomerDetail(array|object $customerDetails): array
    {
        $rules = [
            "first_name" => "nullable|string",
            "last_name" => "nullable|string",
            "email" => "nullable|email",
            "phone" => "nullable|string",
            "billing_address.first_name" => "nullable|string",
            "billing_address.last_name" => "nullable|string",
            "billing_address.phone" => "nullable|string",
            "billing_address.address" => "nullable|string",
            "billing_address.city" => "nullable|string",
            "billing_address.postal_code" => "nullable|string",
            "billing_address.country_code" => "nullable|string",
            "shipping_address.first_name" => "nullable|string",
            "shipping_address.last_name" => "nullable|string",
            "shipping_address.phone" => "nullable|string",
            "shipping_address.address" => "nullable|string",
            "shipping_address.city" => "nullable|string",
            "shipping_address.postal_code" => "nullable|string",
            "shipping_address.country_code" => "nullable|string",
        ];
        
        $validator = Validator::make($customerDetails, $rules);
        
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        
        return $validator->validated();
    }
}