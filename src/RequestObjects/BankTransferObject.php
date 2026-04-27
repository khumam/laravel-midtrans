<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;

trait BankTransferObject
{
    public function withBankTransfer(array|object $bankTransfer): self
    {
        $bankTransfer = $this->validateBankTransfer($bankTransfer);
        
        $this->payload['bank_transfer'] = $bankTransfer;

        return $this;
    }
    
    protected function validateBankTransfer(array|object $bankTransfer): array
    {
        $rules = [
            "bank" => "nullable|string",
            "va_number" => "nullable|string",
            "free_text.inquiry" => "nullable|array",
            "free_text.payment" => "nullable|array",
            "bca.sub_company_code" => "nullable|string",
            "permata.recipient_name" => "nullable|string",
        ];
        
        $validator = Validator::make($bankTransfer, $rules);
        
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        
        return $validator->validated();
    }
}