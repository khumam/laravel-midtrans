<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;

trait EChannelObject
{
    public function withEChannel(array|object $eChannel): self
    {
        $eChannel = $this->validateEChannel($eChannel);
        
        $this->payload['e_channel'] = $eChannel;

        return $this;
    }
    
    protected function validateEChannel(array|object $eChannel): array
    {
        $rules = [
            "bill_info1" => "required|string",
            "bill_info2" => "required|string",
            "bill_info3" => "nullable|string",
            "bill_info4" => "nullable|string",
            "bill_info5" => "nullable|string",
            "bill_info6" => "nullable|string",
            "bill_info7" => "nullable|string",
            "bill_info8" => "nullable|string",
            "bill_key" => "nullable|string",
        ];
        
        $validator = Validator::make($eChannel, $rules);
        
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        
        return $validator->validated();
    }
}