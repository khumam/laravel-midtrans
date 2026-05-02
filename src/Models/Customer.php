<?php

namespace Khumam\Midtrans\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Customer extends Model
{
    protected $table = 'midtrans_customers';

    protected $guarded = [];

    public function billable(): MorphTo
    {
        return $this->morphTo();
    }
}
