<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;

trait VaNumberObject
{
    public function withVaNumber(array $vaNumber): self
    {
        $vaNumber = $this->validateVaNumber($vaNumber);
        
        $this->payload['va_number'] = $vaNumber;

        return $this;
    }
    
    protected function validateVaNumber(array $vaNumber): array
    {
        $rules = [
            "*.bank" => "required|string",
            "*.va_number" => "required|string",
        ];
        
        $validator = Validator::make($vaNumber, $rules);
        
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        
        return $validator->validated();
    }
}