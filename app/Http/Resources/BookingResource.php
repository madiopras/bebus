<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'booking_id' => $this->booking_id,
            'user_id' => $this->user_id,
            'schedule_id' => $this->schedule_id,
            'booking_date' => $this->booking_date,
            'payment_status' => $this->payment_status,
            'final_price' => $this->final_price,
            'voucher_id' => $this->voucher_id,
            'specialdays_id' => $this->specialdays_id,
            'description' => $this->description,
            'created_by_id' => $this->created_by_id,
            'updated_by_id' => $this->updated_by_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
