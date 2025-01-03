<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'payment_id' => $this->payment_id,
            'booking_id' => $this->booking_id,
            'payment_method' => $this->payment_method,
            'payment_date' => $this->payment_date,
            'amount' => $this->amount,
            'description' => $this->description,
            'created_by_id' => $this->created_by_id,
            'updated_by_id' => $this->updated_by_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
