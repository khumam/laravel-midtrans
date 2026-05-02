<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;

trait ItemDetailObject
{
    public function withItemDetail(array $itemDetails): self
    {
        $itemDetails = $this->validateItemDetail($itemDetails);
        
        $this->payload['item_details'] = $itemDetails;

        $this->transaction->items()->delete();

        foreach ($itemDetails as $item) {
            $this->transaction->items()->create([
                'item_id' => $item['id'] ?? null,
                'price' => $item['price'] ?? null,
                'quantity' => $item['quantity'] ?? null,
                'name' => $item['name'] ?? null,
                'brand' => $item['brand'] ?? null,
                'category' => $item['category'] ?? null,
                'merchant_name' => $item['merchant_name'] ?? null,
                'tenor' => $item['tenor'] ?? null,
                'code_plan' => $item['code_plan'] ?? null,
                'mid' => $item['mid'] ?? null,
                'url' => $item['url'] ?? null,
            ]);
        }

        return $this;
    }
    
    protected function validateItemDetail(array $itemDetails): array
    {
        $rules = [
            "*.id" => "nullable",
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