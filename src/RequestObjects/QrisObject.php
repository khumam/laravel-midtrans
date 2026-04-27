<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;

trait QrisObject
{
    public function withQris(array|object $qris): self
    {
        $qris = $this->validateQris($qris);
        
        $this->payload['qris'] = $qris;

        return $this;
    }
    
    protected function validateQris(array|object $qris): array
    {
        $rules = [
            "acquirer" => "nullable|string",
        ];
        
        $validator = Validator::make($qris, $rules);
        
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        
        return $validator->validated();
    }
}