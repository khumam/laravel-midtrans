<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;

trait CreditCardObject
{
    public function withCreditCard(array|object $creditCard): self
    {
        $creditCard = $this->validateCreditCard($creditCard);
        
        $this->payload['credit_card'] = $creditCard;

        return $this;
    }
    
    protected function validateCreditCard(array|object $creditCard): array
    {
        $rules = [
            "token_id" => "required|string",
            "bank" => "nullable|string",
            "installment_term" => "nullable|integer",
            "bins" => "nullable|array",
            "type" => "nullable|string",
            "save_token_id" => "nullable|boolean",
            "channel" => "nullable|string",
        ];
        
        $validator = Validator::make($creditCard, $rules);
        
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        
        return $validator->validated();
    }
}