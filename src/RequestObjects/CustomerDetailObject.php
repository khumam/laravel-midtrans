<?php

namespace Khumam\Midtrans\RequestObjects;

use Illuminate\Support\Facades\Validator;
use Khumam\Midtrans\Models\Customer;

trait CustomerDetailObject
{
    public function withCustomerDetail(array|object $customerDetails): self
    {
        $customerDetails = $this->validateCustomerDetail($customerDetails);

        $this->payload['customer_details'] = $customerDetails;

        $this->upsertCustomer($customerDetails);

        return $this;
    }

    protected function upsertCustomer(array $details): void
    {
        $billable = $this->transaction->billable;

        if (! $billable) {
            return;
        }

        Customer::updateOrCreate(
            [
                'billable_type' => get_class($billable),
                'billable_id' => $billable->getKey(),
            ],
            [
                'first_name' => $details['first_name'] ?? null,
                'last_name' => $details['last_name'] ?? null,
                'email' => $details['email'] ?? null,
                'phone' => $details['phone'] ?? null,
                'billing_first_name' => $details['billing_address']['first_name'] ?? null,
                'billing_last_name' => $details['billing_address']['last_name'] ?? null,
                'billing_phone' => $details['billing_address']['phone'] ?? null,
                'billing_address' => $details['billing_address']['address'] ?? null,
                'billing_city' => $details['billing_address']['city'] ?? null,
                'billing_postal_code' => $details['billing_address']['postal_code'] ?? null,
                'billing_country_code' => $details['billing_address']['country_code'] ?? null,
                'shipping_first_name' => $details['shipping_address']['first_name'] ?? null,
                'shipping_last_name' => $details['shipping_address']['last_name'] ?? null,
                'shipping_phone' => $details['shipping_address']['phone'] ?? null,
                'shipping_address' => $details['shipping_address']['address'] ?? null,
                'shipping_city' => $details['shipping_address']['city'] ?? null,
                'shipping_postal_code' => $details['shipping_address']['postal_code'] ?? null,
                'shipping_country_code' => $details['shipping_address']['country_code'] ?? null,
            ],
        );
    }

    protected function validateCustomerDetail(array|object $customerDetails): array
    {
        $rules = [
            "first_name" => "nullable|string",
            "last_name" => "nullable|string",
            "email" => "nullable|email",
            "phone" => "nullable|string",
            "billing_address.first_name" => "nullable|string",
            "billing_address.last_name" => "nullable|string",
            "billing_address.phone" => "nullable|string",
            "billing_address.address" => "nullable|string",
            "billing_address.city" => "nullable|string",
            "billing_address.postal_code" => "nullable|string",
            "billing_address.country_code" => "nullable|string",
            "shipping_address.first_name" => "nullable|string",
            "shipping_address.last_name" => "nullable|string",
            "shipping_address.phone" => "nullable|string",
            "shipping_address.address" => "nullable|string",
            "shipping_address.city" => "nullable|string",
            "shipping_address.postal_code" => "nullable|string",
            "shipping_address.country_code" => "nullable|string",
        ];

        $validator = Validator::make($customerDetails, $rules);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        return $validator->validated();
    }
}
