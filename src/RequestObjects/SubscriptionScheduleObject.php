<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;

trait SubscriptionScheduleObject
{
    public function withSubscriptionSchedule(array|object $subscriptionSchedule): self
    {
        $subscriptionSchedule = $this->validateSubscriptionSchedule($subscriptionSchedule);
        
        $this->payload['schedule'] = $subscriptionSchedule;

        return $this;
    }
    
    protected function validateSubscriptionSchedule(array|object $subscriptionSchedule): array
    {
        $rules = [
            "interval" => "required|integer",
            "interval_unit" => "required|string",
            "max_interval" => "nullable|integer",
            "start_time" => "nullable|string",
            "previous_execution_at" => "nullable|string",
            "next_execution_at" => "nullable|string",
            "gopay.account_id" => "nullable|string",
        ];
        
        $validator = Validator::make($subscriptionSchedule, $rules);
        
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        
        return $validator->validated();
    }
}