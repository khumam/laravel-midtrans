<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;

trait OverTheCounterObject
{
    public function withOverTheCounter(array $overTheCounter): self
    {
        $overTheCounter = $this->validateOverTheCounter($overTheCounter);
        
        $this->payload['over_the_counter'] = $overTheCounter;

        return $this;
    }
    
    protected function validateOverTheCounter(array $overTheCounter): array
    {
        $rules = [
            "store" => "required|string",
            "message" => "nullable|string",
            "alfamart_free_text_1" => "nullable|string",
            "alfamart_free_text_2" => "nullable|string",
            "alfamart_free_text_3" => "nullable|string",
        ];
        
        $validator = Validator::make($overTheCounter, $rules);
        
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        
        return $validator->validated();
    }
}