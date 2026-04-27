<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;

trait TransactionDetailObject
{
    public function withTransactionDetail(array $transactionDetail): self
    {
        $transactionDetail = $this->validateTransactionDetail($transactionDetail);
        
        $this->payload['transaction_details'] = $transactionDetail;

        return $this;
    }
    
    protected function validateTransactionDetail(array $transactionDetail): array
    {
        $rules = [
            "order_id" => "required|string",
            "gross_amount" => "required|numeric",
        ];
        
        $validator = Validator::make($transactionDetail, $rules);
        
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        
        return $validator->validated();
    }
}