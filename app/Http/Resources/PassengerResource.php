<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PassengerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'passenger_id' => $this->passenger_id,
            'booking_id' => $this->booking_id,
            'schedule_seat_id' => $this->schedule_seat_id,
            'name' => $this->name,
            'phone_number' => $this->phone_number,
            'description' => $this->description,
            'created_by_id' => $this->created_by_id,
            'updated_by_id' => $this->updated_by_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
