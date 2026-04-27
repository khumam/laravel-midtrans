<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;

trait OvoObject
{
    public function withOvo(array|object $ovo): self
    {
        $ovo = $this->validateOvo($ovo);
        
        $this->payload['ovo'] = $ovo;

        return $this;
    }
    
    protected function validateOvo(array|object $ovo): array
    {
        $rules = [
            "payer_phone_number" => "nullable|string",
        ];
        
        $validator = Validator::make($ovo, $rules);
        
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        
        return $validator->validated();
    }
}