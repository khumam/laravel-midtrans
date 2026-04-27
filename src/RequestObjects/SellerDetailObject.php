<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;

trait SellerDetailObject
{
    public function withSellerDetail(array|object $sellerDetails): self
    {
        $sellerDetails = $this->validateSellerDetail($sellerDetails);
        
        $this->payload['seller_details'] = $sellerDetails;

        return $this;
    }
    
    protected function validateSellerDetail(array|object $sellerDetails): array
    {
        $rules = [
            "id" => "nullable|string",
            "name" => "nullable|string",
            "email" => "nullable|email",
            "url" => "nullable|string",
            "address.first_name" => "nullable|string",
            "address.last_name" => "nullable|string",
            "address.phone" => "nullable|string",
            "address.address" => "nullable|string",
            "address.city" => "nullable|string",
            "address.postal_code" => "nullable|string",
            "address.country_code" => "nullable|string",
        ];
        
        $validator = Validator::make($sellerDetails, $rules);
        
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        
        return $validator->validated();
    }
}