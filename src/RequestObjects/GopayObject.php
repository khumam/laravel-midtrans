<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;

trait GopayObject
{
    public function withGopay(array|object $gopay): self
    {
        $gopay = $this->validateGopay($gopay);
        
        $this->payload['gopay'] = $gopay;

        return $this;
    }
    
    protected function validateGopay(array|object $gopay): array
    {
        $rules = [
            "enable_callback" => "nullable|boolean",
            "callback_url" => "nullable|string",
            "account_id" => "nullable|string",
            "payment_option_token" => "nullable|string",
            "recurring" => "nullable|boolean",
            "promotion_ids" => "nullable|array",
        ];
        
        $validator = Validator::make($gopay, $rules);
        
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        
        return $validator->validated();
    }
}