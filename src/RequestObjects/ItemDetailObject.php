<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;

trait ItemDetailObject
{
    public function withItemDetail(array $itemDetails): self
    {
        $itemDetails = $this->validateItemDetail($itemDetails);
        
        $this->payload['item_details'] = $itemDetails;

        return $this;
    }
    
    protected function validateItemDetail(array $itemDetails): array
    {
        $rules = [
            "*.id" => "nullable|string",
            "*.price" => "nullable|integer",
            "*.quantity" => "nullable|integer",
            "*.name" => "nullable|string",
            "*.brand" => "nullable|string",
            "*.category" => "nullable|string",
            "*.merchant_name" => "nullable|string",
            "*.tenor" => "nullable|integer",
            "*.code_plan" => "nullable|string",
            "*.mid" => "nullable|string",
            "*.url" => "nullable|string",
        ];
        
        $validator = Validator::make($itemDetails, $rules);
        
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        
        return $validator->validated();
    }
}