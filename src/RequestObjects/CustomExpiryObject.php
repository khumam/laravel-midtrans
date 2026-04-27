<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;

trait CustomExpiryObject
{
    public function withCustomExpiry(array|object $customExpiry): self
    {
        $customExpiry = $this->validateCustomExpiry($customExpiry);
        
        $this->payload['custom_expiry'] = $customExpiry;

        return $this;
    }
    
    protected function validateCustomExpiry(array|object $customExpiry): array
    {
        $rules = [
            "order_item" => "string",
            "expiry_duration" => "string",
            "unit" => "string",
        ];
        
        $validator = Validator::make($customExpiry, $rules);
        
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        
        return $validator->validated();
    }
}